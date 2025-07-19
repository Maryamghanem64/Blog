<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Get all categories with post counts
$categories = $conn->query("SELECT categories.*, COUNT(posts.id) as post_count 
                           FROM categories 
                           LEFT JOIN posts ON categories.id = posts.category_id 
                           GROUP BY categories.id 
                           ORDER BY categories.name")->fetchAll();

// Get recent posts for the sidebar
$recentPosts = $conn->query("SELECT posts.*, users.username FROM posts 
                            JOIN users ON posts.author_id = users.id 
                            ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Categories - Professional Blog</title>
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
    
    .category-card {
      background: var(--light);
      border-radius: 15px;
      box-shadow: 0 10px 25px var(--shadow);
      margin-bottom: 2rem;
      overflow: hidden;
      transition: all 0.3s ease;
      border: 1px solid rgba(167, 201, 87, 0.2);
      border-left: 4px solid var(--pistachio);
    }
    
    .category-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .category-content {
      padding: 2rem;
    }
    
    .category-title {
      color: #a7c957 !important;
      font-weight: 700;
      margin-bottom: 1rem;
      font-size: 1.8rem;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    
    .category-title:hover {
      color: #2e2e2e !important;
    }
    
    .category-title a {
      color: #a7c957 !important;
      text-decoration: none;
    }
    
    .category-title a:hover {
      color: #2e2e2e !important;
      text-decoration: underline;
    }
    
    .category-description {
      color: #6c757d;
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }
    
    .category-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }
    
    .category-stats {
      display: flex;
      gap: 1rem;
    }
    
    .stat-item {
      text-align: center;
      padding: 0.5rem 1rem;
      background: #a7c957;
      border-radius: 8px;
      color: #2e2e2e;
      font-weight: 600;
    }
    
    .category-actions {
      display: flex;
      gap: 0.5rem;
    }
    
    .btn-category {
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      border: none;
      font-size: 0.9rem;
    }
    
    .btn-view {
      background: #a7c957;
      color: #2e2e2e;
    }
    
    .btn-view:hover {
      background: #2e2e2e;
      color: #ffffff;
      transform: translateY(-2px);
    }
    
    .btn-edit {
      background: #a7c957;
      color: white;
    }
    
    .btn-edit:hover {
      background: #8fb84a;
      color: white;
      transform: translateY(-2px);
    }
    
    .btn-delete {
      background: #dc3545;
      color: white;
    }
    
    .btn-delete:hover {
      background: #c82333;
      color: white;
      transform: translateY(-2px);
    }
    
    .no-categories {
      text-align: center;
      padding: 3rem;
      color: #6c757d;
    }
    
    .no-categories i {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    .sidebar {
      background: #a7c957;
      min-height: 100vh;
      padding: 1rem;
    }
    
    .sidebar h4 {
      color: #2e2e2e;
      font-weight: 700;
      margin-bottom: 2rem;
    }
    
    .sidebar a {
      color: #2e2e2e;
      text-decoration: none;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      margin-bottom: 0.5rem;
      transition: all 0.3s ease;
      display: block;
    }
    
    .sidebar a:hover {
      color: #ffffff;
      background-color: #2e2e2e;
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
    
    .recent-posts {
      background: #ffffff;
      border-radius: 15px;
      padding: 1.5rem;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
      border-left: 4px solid #a7c957;
    }
    
    .recent-posts h5 {
      color: #2e2e2e;
      font-weight: 700;
      margin-bottom: 1rem;
    }
    
    .recent-post-item {
      padding: 0.75rem 0;
      border-bottom: 1px solid #e9ecef;
    }
    
    .recent-post-item:last-child {
      border-bottom: none;
    }
    
    .recent-post-title {
      color: #a7c957 !important;
      text-decoration: underline;
      font-weight: 600;
      transition: color 0.3s ease;
    }
    
    .recent-post-title:hover {
      color: #2e2e2e !important;
      text-decoration: underline;
    }
    
    .recent-post-meta {
      color: #6c757d;
      font-size: 0.8rem;
      margin-top: 0.25rem;
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
      
      .category-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
      
      .category-stats {
        flex-wrap: wrap;
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
          <div class="category-header animate-fade-in-down">
            <h1 class="animate-slide-in-left stagger-1"><i class="fas fa-tags me-3"></i>Categories</h1>
            <p class="animate-slide-in-left stagger-2">Browse and manage all blog categories</p>
          </div>

          <?php if (count($categories) > 0): ?>
            <div class="row">
              <?php foreach ($categories as $index => $category): ?>
                <div class="col-lg-6 col-md-6 mb-4">
                  <div class="category-card animate-fade-in-up" style="animation-delay: <?= ($index * 0.1) ?>s;">
                    <div class="category-content">
                      <h3 class="category-title">
                        <a href="category_posts.php?id=<?= $category['id'] ?>">
                          <i class="fas fa-folder me-2"></i><?= htmlspecialchars($category['name']) ?>
                        </a>
                      </h3>
                      
                      <p class="category-description">
                        <?= htmlspecialchars($category['description'] ?? 'No description available') ?>
                      </p>
                      
                      <div class="category-meta">
                        <div class="category-stats">
                          <div class="stat-item">
                            <i class="fas fa-file-alt me-1"></i><?= $category['post_count'] ?> Posts
                          </div>
                        </div>
                        
                        <div class="category-actions">
                          <a href="category_posts.php?id=<?= $category['id'] ?>" class="btn-category btn-view">
                            <i class="fas fa-eye"></i>View Posts
                          </a>
                          <?php if ($_SESSION['user_id'] == 1): // Admin only ?>
                            <a href="edit_category.php?id=<?= $category['id'] ?>" class="btn-category btn-edit">
                              <i class="fas fa-edit"></i>Edit
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?')">
                              <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                              <button type="submit" name="delete_category" class="btn-category btn-delete">
                                <i class="fas fa-trash"></i>Delete
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="no-categories">
              <i class="fas fa-folder-open"></i>
              <h3>No categories found</h3>
              <p>No categories have been created yet.</p>
              <?php if ($_SESSION['user_id'] == 1): // Admin only ?>
                <a href="create_category.php" class="btn btn-dark">
                  <i class="fas fa-plus me-2"></i>Create First Category
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
