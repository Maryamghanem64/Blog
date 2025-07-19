<?php
/**
 * Authentication Controller
 * Handles user authentication, registration, and session management
 */

require_once APP_ROOT . '/config/config.php';

class AuthController {
    
    public function __construct() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Handle user login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'errors' => ['Invalid request method.']];
        }
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'errors' => ['Invalid security token.']];
        }
        
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Basic validation
        if (empty($email) || empty($password)) {
            return ['success' => false, 'errors' => ['Email and password are required.']];
        }
        
        if (!validateEmail($email)) {
            return ['success' => false, 'errors' => ['Please enter a valid email address.']];
        }
        
        try {
            global $conn;
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'errors' => ['Invalid email or password.']];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'errors' => ['Invalid email or password.']];
            }
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['login_time'] = time();
            
            // Update last login
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Set remember me cookie if requested
            if ($remember) {
                $this->setRememberMeCookie($user['id']);
            }
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Login failed. Please try again.']];
        }
    }
    
    /**
     * Handle user registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'errors' => ['Invalid request method.']];
        }
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'errors' => ['Invalid security token.']];
        }
        
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        $errors = [];
        
        if (empty($username) || empty($email) || empty($password)) {
            $errors[] = 'All fields are required.';
        }

        // Use PASSWORD_MIN_LENGTH from config
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (!validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            global $conn;
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'errors' => ['This email is already registered.']];
            }
            
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'errors' => ['This username is already taken.']];
            }
            
            // Hash password and create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'user', 'active')");
            $stmt->execute([$username, $email, $hashedPassword]);
            
            $userId = $conn->lastInsertId();
            
            // Auto-login after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['user_role'] = 'user';
            $_SESSION['login_time'] = time();
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
        }
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            $this->clearRememberMeCookie();
        }
        
        // Destroy session
        session_destroy();
        
        // Clear all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    /**
     * Check if user is logged in via remember me cookie
     */
    public function checkRememberMe() {
        if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {
            try {
                global $conn;
                
                $stmt = $conn->prepare("SELECT user_id FROM user_sessions WHERE session_id = ? AND expires_at > NOW()");
                $stmt->execute([$_COOKIE['remember_token']]);
                $session = $stmt->fetch();
                
                if ($session) {
                    // Get user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
                    $stmt->execute([$session['user_id']]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'] ?? 'user';
                        $_SESSION['login_time'] = time();
                    }
                }
            } catch (Exception $e) {
                error_log("Remember me error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Set remember me cookie
     */
    private function setRememberMeCookie($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        try {
            global $conn;
            
            $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $token, $expires]);
            
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        } catch (Exception $e) {
            error_log("Set remember me error: " . $e->getMessage());
        }
    }
    
    /**
     * Clear remember me cookie
     */
    private function clearRememberMeCookie() {
        try {
            global $conn;
            
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE session_id = ?");
            $stmt->execute([$_COOKIE['remember_token']]);
            
            setcookie('remember_token', '', time() - 3600, '/');
        } catch (Exception $e) {
            error_log("Clear remember me error: " . $e->getMessage());
        }
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!isLoggedIn()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            redirect('/login.php');
        }
    }
    
    /**
     * Require admin privileges
     */
    public function requireAdmin() {
        $this->requireAuth();
        if (!isAdmin()) {
            setFlashMessage('error', 'Access denied. Admin privileges required.');
            redirect('/dashboard.php');
        }
    }
}
?> 