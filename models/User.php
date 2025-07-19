<?php
/**
 * User Model
 * Handles user authentication, registration, and management
 */

require_once APP_ROOT . '/config/database.php';

class User {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Register a new user
     */
    public function register($username, $email, $password, $confirmPassword) {
        $errors = [];
        
        // Validation
        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        }
        
        if (empty($email) || !validateEmail($email)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long.";
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }
        
        // Check if username already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username already exists.";
        }
        
        // Check if email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already registered.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, role, created_at) 
                VALUES (?, ?, ?, 'user', NOW())
            ");
            $stmt->execute([$username, $email, $hashedPassword]);
            
            return ['success' => true, 'user_id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            error_log("User registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
        }
    }
    
    /**
     * Authenticate user login
     */
    public function login($email, $password) {
        // Check for login attempts
        if ($this->isAccountLocked($email)) {
            return ['success' => false, 'errors' => ['Account is temporarily locked. Please try again later.']];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, password, role, status 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->recordLoginAttempt($email, false);
                return ['success' => false, 'errors' => ['Invalid email or password.']];
            }
            
            if ($user['status'] !== 'active') {
                return ['success' => false, 'errors' => ['Account is not active. Please contact administrator.']];
            }
            
            if (!password_verify($password, $user['password'])) {
                $this->recordLoginAttempt($email, false);
                return ['success' => false, 'errors' => ['Invalid email or password.']];
            }
            
            // Successful login
            $this->recordLoginAttempt($email, true);
            $this->updateLastLogin($user['id']);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            return ['success' => true, 'user' => $user];
        } catch (PDOException $e) {
            error_log("User login error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Login failed. Please try again.']];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, role, status, created_at, last_login 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, role, status, created_at, last_login 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $updates = [];
            $params = [];
            
            if (!empty($data['username'])) {
                $updates[] = "username = ?";
                $params[] = $data['username'];
            }
            
            if (!empty($data['email'])) {
                $updates[] = "email = ?";
                $params[] = $data['email'];
            }
            
            if (!empty($data['bio'])) {
                $updates[] = "bio = ?";
                $params[] = $data['bio'];
            }
            
            if (empty($updates)) {
                return ['success' => false, 'errors' => ['No data to update.']];
            }
            
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Update failed. Please try again.']];
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'errors' => ['Current password is incorrect.']];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Password change failed. Please try again.']];
        }
    }
    
    /**
     * Get all users (admin only)
     */
    public function getAllUsers($page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->db->prepare("
                SELECT id, username, email, role, status, created_at, last_login,
                       (SELECT COUNT(*) FROM posts WHERE user_id = users.id) as post_count
                FROM users 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $users = $stmt->fetchAll();
            
            // Get total count
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users");
            $stmt->execute();
            $total = $stmt->fetchColumn();
            
            return [
                'users' => $users,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user status (admin only)
     */
    public function updateUserStatus($userId, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $userId]);
            return true;
        } catch (PDOException $e) {
            error_log("Update user status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete user (admin only)
     */
    public function deleteUser($userId) {
        try {
            $this->db->beginTransaction();
            
            // Delete user's posts
            $stmt = $this->db->prepare("DELETE FROM posts WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Delete user's comments
            $stmt = $this->db->prepare("DELETE FROM comments WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Delete user
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if account is locked due to failed login attempts
     */
    private function isAccountLocked($email) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE email = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$email, LOGIN_LOCKOUT_TIME]);
            $result = $stmt->fetch();
            
            return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
        } catch (PDOException $e) {
            error_log("Check account lock error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record login attempt
     */
    private function recordLoginAttempt($email, $success) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (email, success, ip_address, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$email, $success ? 1 : 0, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (PDOException $e) {
            error_log("Record login attempt error: " . $e->getMessage());
        }
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return true;
    }
}
?> 