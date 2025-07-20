<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    include 'db.php';

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: home.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Professional Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <style>
    :root {
      --pistachio: #a7c957;
      --primary-dark: #8fb84a;
      --dark: #2e2e2e;
      --light: #ffffff;
      --shadow: rgba(0,0,0,0.1);
    }
    
    /* Professional Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes slideInLeft {
      from {
        opacity: 0;
        transform: translateX(-50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    
    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }
    
    @keyframes shimmer {
      0% {
        background-position: -200px 0;
      }
      100% {
        background-position: calc(200px + 100%) 0;
      }
    }
    
    @keyframes float {
      0%, 100% {
        transform: translateY(0px);
      }
      50% {
        transform: translateY(-10px);
      }
    }
    
    body {
      background: linear-gradient(135deg, #a7c957 0%, #8fb84a 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .login-container {
      max-width: 450px;
      width: 100%;
      padding: 2rem;
    }
    
    .login-card {
      background: #ffffff;
      border-radius: 20px;
      padding: 3rem 2rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      border: 1px solid rgba(167, 201, 87, 0.2);
      border-left: 4px solid #a7c957;
      animation: fadeInUp 0.8s ease-out;
      position: relative;
      overflow: hidden;
    }
    
    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(167, 201, 87, 0.1), transparent);
      animation: shimmer 2s infinite;
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .login-header h1 {
      color: #000000;
      font-weight: 700;
      margin-bottom: 0.5rem;
      font-size: 2.5rem;
      animation: slideInLeft 1s ease-out 0.2s both;
    }
    
    .login-header p {
      color: #2e2e2e;
      font-size: 1.1rem;
      opacity: 0.8;
    }
    
    .form-floating {
      margin-bottom: 1.5rem;
      animation: fadeInUp 0.6s ease-out both;
    }
    
    .form-floating:nth-child(1) {
      animation-delay: 0.4s;
    }
    
    .form-floating:nth-child(2) {
      animation-delay: 0.6s;
    }
    
    .form-control {
      border: 2px solid #a7c957;
      border-radius: 10px;
      padding: 1rem 0.75rem;
      font-size: 1rem;
      transition: all 0.3s ease;
      background-color: #ffffff;
      color: #2e2e2e;
    }
    
    .form-control:focus {
      border-color: #a7c957;
      box-shadow: 0 0 0 0.2rem rgba(167, 201, 87, 0.25);
      background-color: #ffffff;
    }
    
    .form-floating label {
      color: #a7c957;
      font-weight: 600;
    }
    
    .btn-login {
      background: #a7c957;
      border: none;
      border-radius: 10px;
      padding: 1rem 2rem;
      font-weight: 600;
      font-size: 1.1rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      width: 100%;
      margin-bottom: 1.5rem;
      color: #2e2e2e;
      animation: fadeInUp 0.8s ease-out 0.8s both;
      position: relative;
      overflow: hidden;
    }
    
    .btn-login::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }
    
    .btn-login:hover::before {
      left: 100%;
    }
    
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(167, 201, 87, 0.3);
      background-color: #8fb84a;
      color: #ffffff;
      animation: pulse 0.3s ease-in-out;
    }
    
    .register-link {
      text-align: center;
      color: #2e2e2e;
      animation: fadeInUp 0.6s ease-out 1s both;
    }
    
    .register-link a {
      color: #a7c957;
      text-decoration: none;
      font-weight: 700;
      transition: all 0.3s ease;
      position: relative;
    }
    
    .register-link a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -2px;
      left: 0;
      background-color: #a7c957;
      transition: width 0.3s ease;
    }
    
    .register-link a:hover {
      color: #2e2e2e;
      transform: translateY(-2px);
    }
    
    .register-link a:hover::after {
      width: 100%;
    }
    
    .alert {
      border-radius: 10px;
      border: none;
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
      animation: slideInLeft 0.6s ease-out;
    }
    
    .alert-danger {
      background: #a7c957;
      color: #2e2e2e;
      border: 2px solid #8fb84a;
      position: relative;
      overflow: hidden;
    }
    
    .alert-danger::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: #8fb84a;
      animation: float 2s ease-in-out infinite;
    }
    
    .input-group-text {
      background: #a7c957;
      border: 2px solid #a7c957;
      border-right: none;
      color: #2e2e2e;
    }
    
    .input-group .form-control {
      border-left: none;
    }
    
    .input-group .form-control:focus + .input-group-text {
      border-color: #a7c957;
    }
    
    @media (max-width: 576px) {
      .login-container {
        padding: 1rem;
      }
      
      .login-card {
        padding: 2rem 1.5rem;
      }
      
      .login-header h1 {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1><i class="fas fa-user-circle me-3"></i>Welcome Back</h1>
        <p>Sign in to your account to continue</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-floating">
          <input type="email" class="form-control" id="email" name="email" 
                 placeholder="name@example.com" required autocomplete="username"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          <label for="email">
            <i class="fas fa-envelope me-2"></i>Email Address
          </label>
        </div>
        
        <div class="form-floating">
          <input type="password" class="form-control" id="password" name="password" 
                 placeholder="Password" required autocomplete="current-password">
          <label for="password">
            <i class="fas fa-lock me-2"></i>Password
          </label>
        </div>

        <button type="submit" name="login" class="btn btn-login">
          <i class="fas fa-sign-in-alt me-2"></i>Sign In
        </button>
      </form>

      <div class="register-link">
        <p>Don't have an account? 
          <a href="register.php">
            <i class="fas fa-user-plus me-1"></i>Create Account
          </a>
        </p>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
