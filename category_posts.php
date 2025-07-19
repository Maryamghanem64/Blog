<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$categoryId = $_GET['id'] ?? null;

if (!$categoryId) {
    header("Location: category.php");
    exit();
}

// Get category name
$catNameStmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
$catNameStmt->execute([$categoryId]);
$category = $catNameStmt->fetch();

if (!$category) {
    header("Location: category.php");
    exit();
}

// Get posts in this category
$stmt = $conn->prepare("SELECT posts.*, users.username 
                        FROM posts 
                        JOIN users ON posts.author_id = users.id 
                        WHERE posts.category_id = ? 
                        ORDER BY posts.created_at DESC");
$stmt->execute([$categoryId]);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Posts in <?= htmlspecialchars($category['name']) ?> - Professional Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="styles.css" rel="stylesheet">
  <script src="dark-mode.js" defer></script>
  <style>
    .category-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .category-header {
      background: var(--pistachio);
      color: var(--dark);
      padding: 2rem;
      border-radius: 20px;
      margin-bottom: 2rem;
      text-align: center;
      box-shadow: 0 10px 25px var(--shadow);
    }
    
    .category-header h1 {
      font-weight: 700;
      margin-bottom: 1rem;
      font-size: 2.5rem;
    }
    
    .category-header p {
      font-size: 1.1rem;
      opacity: 0.9;
      margin-bottom: 1.5rem;
    }
    
    .category-stats {
      display: flex;
      justify-content: space-around;
      margin-top: 1.5rem;
    }
    
    .stat-item {
      text-align: center;
      padding: 1rem;
      background: var(--light);
      border-radius: 10px;
      min-width: 100px;
    }
    
    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      font-size: 0.9rem;
      color: #6c757d;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
    }
    
    .post-card {
      background: var(--light);
      border-radius: 15px;
      box-shadow: 0 10px 25px var(--shadow);
      margin-bottom: 2rem;
      overflow: hidden;
      transition: all 0.3s ease;
      border: 1px solid rgba(167, 201, 87, 0.2);
      border-left: 4px solid var(--pistachio);
    }
    
    .post-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .post-image {
      width: 100%;
      height: 250px;
      object-fit: cover;
      border-bottom: 1px solid #e9ecef;
    }
    
    .post-content {
      padding: 1.5rem;
    }
    
    .post-title {
      color: var(--pistachio);
      font-weight: 700;
      margin-bottom: 1rem;
      font-size: 1.5rem;
      text-decoration: underline;
      transition: color 0.3s ease;
    }
    
    .post-title:hover {
      color: var(--dark);
      text-decoration: underline;
    }
    
    .post-excerpt {
      color: #6c757d;
      line-height: 1.6;
      margin-bottom: 1rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .post-meta {
      color: #6c757d;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }
    
    .post-meta i {
      margin-right: 0.5rem;
    }
    
    .category-badge {
      background: var(--pistachio);
      color: var(--dark);
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
      display: inline-block;
    }
    
    .no-posts {
      text-align: center;
      padding: 3rem;
      color: #6c757d;
    }
    
    .no-posts i {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
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
    
    @media (max-width: 768px) {
      .category-container {
        padding: 1rem;
      }
      
      .category-header h1 {
        font-size: 2rem;
      }
      
      .main-content {
        padding: 1rem;
      }
      
      .category-stats {
        flex-direction: column;
        gap: 1rem;
      }
      
      .stat-item {
        min-width: auto;
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
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="create_post.php"><i class="fas fa-plus-circle"></i>Create Post</a>
        <a href="category.php" class="active"><i class="fas fa-tags"></i>Categories</a>
        <a href="admin.php"><i class="fas fa-cog"></i>Admin Panel</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
      </div>

      <!-- Main Content -->
      <div class="col-md-9 col-lg-10 main-content">
        <div class="category-container">
          <div class="category-header">
            <h1><i class="fas fa-folder me-3"></i><?= htmlspecialchars($category['name']) ?></h1>
            <p>Explore all posts in this category</p>
            <div class="category-stats">
              <div class="stat-item">
                <div class="stat-number"><?= count($posts) ?></div>
                <div class="stat-label">Posts</div>
              </div>
              <div class="stat-item">
                <div class="stat-number"><?= count(array_unique(array_column($posts, 'author_id'))) ?></div>
                <div class="stat-label">Authors</div>
              </div>
              <div class="stat-item">
                <div class="stat-number"><?= array_sum(array_column($posts, 'views')) ?></div>
                <div class="stat-label">Views</div>
              </div>
            </div>
          </div>

          <?php if (count($posts) > 0): ?>
            <div class="row">
              <?php foreach ($posts as $post): ?>
                <div class="col-lg-6 col-md-6 mb-4">
                  <div class="post-card">
                    <?php if (!empty($post['featured_image'])): ?>
                      <img src="uploads/<?= htmlspecialchars($post['featured_image']) ?>" 
                           alt="Post Image" class="post-image">
                    <?php endif; ?>
                    
                    <div class="post-content">
                      <h4 class="post-title">
                        <a href="post.php?id=<?= $post['id'] ?>">
                          <?= htmlspecialchars($post['title']) ?>
                        </a>
                      </h4>
                      
                      <p class="post-excerpt">
                        <?= htmlspecialchars(substr($post['content'], 0, 150)) ?>...
                      </p>
                      
                      <div class="post-meta">
                        <i class="fas fa-user"></i>By <strong><?= htmlspecialchars($post['username']) ?></strong>
                        <i class="fas fa-calendar ms-3"></i><?= date('M j, Y', strtotime($post['created_at'])) ?>
                        <i class="fas fa-eye ms-3"></i><?= $post['views'] ?? 0 ?> views
                      </div>
                      
                      <div class="post-categories">
                        <span class="category-badge"><?= htmlspecialchars($category['name']) ?></span>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="no-posts">
              <i class="fas fa-folder-open"></i>
              <h3>No posts in this category</h3>
              <p>This category doesn't have any posts yet.</p>
              <a href="create_post.php" class="btn btn-dark">
                <i class="fas fa-plus me-2"></i>Create First Post
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 