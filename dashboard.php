<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Get statistics
$postCount = $conn->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$commentCount = $conn->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$categoryCount = $conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Get recent posts
$recentPosts = $conn->query("SELECT posts.*, users.username FROM posts 
                            JOIN users ON posts.author_id = users.id 
                            ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Get user's posts
$userPosts = $conn->prepare("SELECT * FROM posts WHERE author_id = ? ORDER BY created_at DESC LIMIT 5");
$userPosts->execute([$_SESSION['user_id']]);
$userPosts = $userPosts->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Professional Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="styles.css" rel="stylesheet">
  <script src="dark-mode.js" defer></script>
  <style>
    .dashboard-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .dashboard-header {
      text-align: center;
      margin-bottom: 3rem;
      padding: 2rem;
      background: var(--pistachio);
      border-radius: 20px;
      color: var(--dark);
    }
    
    .dashboard-header h1 {
      font-weight: 700;
      margin-bottom: 1rem;
      font-size: 2.5rem;
    }
    
    .dashboard-header p {
      font-size: 1.1rem;
      opacity: 0.9;
    }
    
    .stat-card {
      background: var(--light);
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 10px 25px var(--shadow);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border-left: 4px solid var(--pistachio);
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px var(--shadow);
    }
    
    .stat-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: var(--dark);
      background: var(--pistachio);
      margin: 0 auto 1rem;
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: var(--dark);
    }
    
    .stat-label {
      color: #6c757d;
      font-size: 1rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
    }
    
    .quick-actions {
      background: var(--light);
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 10px 25px var(--shadow);
      margin-bottom: 2rem;
      border-left: 4px solid var(--pistachio);
    }
    
    .quick-actions h3 {
      color: var(--dark);
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .action-btn {
      display: flex;
      align-items: center;
      padding: 1.5rem;
      border: 2px solid #e9ecef;
      border-radius: 10px;
      text-decoration: none;
      color: var(--dark);
      transition: all 0.3s ease;
      margin-bottom: 1rem;
      background: var(--light);
    }
    
    .action-btn:hover {
      border-color: var(--pistachio);
      color: var(--pistachio);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(167, 201, 87, 0.2);
      text-decoration: none;
    }
    
    .action-btn i {
      font-size: 1.5rem;
      margin-right: 1rem;
      width: 40px;
      text-align: center;
    }
    
    .recent-posts {
      background: var(--light);
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 10px 25px var(--shadow);
      margin-bottom: 2rem;
      border-left: 4px solid var(--pistachio);
    }
    
    .recent-posts h3 {
      color: var(--dark);
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .post-item {
      padding: 1rem;
      border-bottom: 1px solid #e9ecef;
      transition: all 0.3s ease;
    }
    
    .post-item:last-child {
      border-bottom: none;
    }
    
    .post-item:hover {
      background-color: rgba(167, 201, 87, 0.1);
      border-radius: 8px;
    }
    
    .post-title {
      color: var(--pistachio);
      font-weight: 600;
      text-decoration: underline;
      transition: color 0.3s ease;
    }
    
    .post-title:hover {
      color: var(--dark);
      text-decoration: underline;
    }
    
    .post-meta {
      color: #6c757d;
      font-size: 0.9rem;
      margin-top: 0.5rem;
    }
    
    .sidebar {
      background: var(--pistachio);
      min-height: 100vh;
      padding: 1rem;
    }
    
    .sidebar h4 {
      color: var(--dark);
      font-weight: 700;
      margin-bottom: 2rem;
    }
    
    .sidebar a {
      color: var(--dark);
      text-decoration: none;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      margin-bottom: 0.5rem;
      transition: all 0.3s ease;
      display: block;
    }
    
    .sidebar a:hover {
      color: var(--light);
      background-color: var(--dark);
      transform: translateX(5px);
    }
    
    .sidebar a i {
      margin-right: 0.5rem;
      width: 20px;
    }
    
    .main-content {
      padding: 2rem;
      background-color: var(--beige);
      min-height: 100vh;
    }
    
    .welcome-message {
      background: var(--light);
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 10px 25px var(--shadow);
      margin-bottom: 2rem;
      border-left: 4px solid var(--pistachio);
      text-align: center;
    }
    
    .welcome-message h2 {
      color: var(--dark);
      font-weight: 700;
      margin-bottom: 1rem;
    }
    
    .welcome-message p {
      color: #6c757d;
      font-size: 1.1rem;
    }
    
    @media (max-width: 768px) {
      .dashboard-container {
        padding: 1rem;
      }
      
      .dashboard-header h1 {
        font-size: 2rem;
      }
      
      .main-content {
        padding: 1rem;
      }
      
      .stat-number {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-3 col-lg-2 sidebar">
        <h4 class="text-center"><i class="fas fa-blog me-2"></i>My Blog</h4>
        <a href="home.php"><i class="fas fa-home"></i>Home</a>
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="create_post.php"><i class="fas fa-plus-circle"></i>Create Post</a>
        <a href="category.php"><i class="fas fa-tags"></i>Categories</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
      </div>

      <!-- Main Content -->
      <div class="col-md-9 col-lg-10 main-content">
        <div class="dashboard-container">
          <div class="dashboard-header animate-fade-in-down">
            <h1 class="animate-slide-in-left stagger-1"><i class="fas fa-tachometer-alt me-3"></i>Dashboard</h1>
            <p class="animate-slide-in-left stagger-2">Welcome back! Here's what's happening with your blog.</p>
          </div>

          <!-- Welcome Message -->
          <div class="welcome-message animate-fade-in-up stagger-3">
            <h2 class="animate-slide-in-left stagger-1"><i class="fas fa-user-circle me-2"></i>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</h2>
            <p class="animate-slide-in-left stagger-2">Manage your blog content and track your progress from this dashboard.</p>
          </div>

          <!-- Statistics -->
          <div class="row">
            <div class="col-lg-3 col-md-6">
              <div class="stat-card animate-fade-in-up stagger-4">
                <div class="stat-icon animate-pulse">
                  <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-number"><?= $postCount ?></div>
                <div class="stat-label">Total Posts</div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="stat-card animate-fade-in-up stagger-5">
                <div class="stat-icon animate-pulse">
                  <i class="fas fa-comments"></i>
                </div>
                <div class="stat-number"><?= $commentCount ?></div>
                <div class="stat-label">Total Comments</div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="stat-card animate-fade-in-up stagger-6">
                <div class="stat-icon animate-pulse">
                  <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $userCount ?></div>
                <div class="stat-label">Total Users</div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="stat-card animate-fade-in-up" style="animation-delay: 0.7s;">
                <div class="stat-icon animate-pulse">
                  <i class="fas fa-tags"></i>
                </div>
                <div class="stat-number"><?= $categoryCount ?></div>
                <div class="stat-label">Categories</div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="quick-actions animate-fade-in-up" style="animation-delay: 0.8s;">
            <h3 class="animate-slide-in-left stagger-1"><i class="fas fa-bolt me-2"></i>Quick Actions</h3>
            <div class="row">
              <div class="col-md-6">
                <a href="create_post.php" class="action-btn">
                  <i class="fas fa-plus-circle"></i>
                  <div>
                    <strong>Create New Post</strong>
                    <div class="text-muted">Write and publish a new blog post</div>
                  </div>
                </a>
              </div>
              <div class="col-md-6">
                <a href="category.php" class="action-btn">
                  <i class="fas fa-tags"></i>
                  <div>
                    <strong>Manage Categories</strong>
                    <div class="text-muted">Organize your posts with categories</div>
                  </div>
                </a>
              </div>
              <div class="col-md-6">
                <a href="home.php" class="action-btn">
                  <i class="fas fa-eye"></i>
                  <div>
                    <strong>View Blog</strong>
                    <div class="text-muted">See how your blog looks to visitors</div>
                  </div>
                </a>
              </div>
              <div class="col-md-6">
                <a href="admin.php" class="action-btn">
                  <i class="fas fa-cog"></i>
                  <div>
                    <strong>Admin Panel</strong>
                    <div class="text-muted">Advanced settings and management</div>
                  </div>
                </a>
              </div>
            </div>
          </div>

          <!-- Recent Posts -->
          <div class="recent-posts animate-fade-in-up" style="animation-delay: 1s;">
            <h3 class="animate-slide-in-left stagger-1"><i class="fas fa-clock me-2"></i>Recent Posts</h3>
            <?php if (count($recentPosts) > 0): ?>
              <?php foreach ($recentPosts as $post): ?>
                <div class="post-item">
                  <a href="post.php?id=<?= $post['id'] ?>" class="post-title">
                    <?= htmlspecialchars($post['title']) ?>
                  </a>
                  <div class="post-meta">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($post['username']) ?>
                    <i class="fas fa-calendar me-2 ms-3"></i><?= date('M j, Y', strtotime($post['created_at'])) ?>
                    <i class="fas fa-eye me-2 ms-3"></i><?= $post['views'] ?? 0 ?> views
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-center text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No posts yet. Create your first post!</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 