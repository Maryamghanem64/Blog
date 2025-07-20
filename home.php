<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Initialize posts variable
$posts = [];

// Pagination
$perPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

if (isset($_GET['search'])) {
    $keyword = '%' . $_GET['search'] . '%';
    // Count total for pagination
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM posts 
                               JOIN users ON posts.author_id = users.id 
                               WHERE posts.title LIKE ? OR posts.content LIKE ?");
    $countStmt->execute([$keyword, $keyword]);
    $totalPosts = $countStmt->fetchColumn();
    
    // Get paginated results
    $stmt = $conn->prepare("SELECT posts.*, users.username FROM posts 
                          JOIN users ON posts.author_id = users.id 
                          WHERE posts.title LIKE ? OR posts.content LIKE ? 
                          ORDER BY created_at DESC
                          LIMIT $perPage OFFSET $offset");
    $stmt->execute([$keyword, $keyword]);
} else {
    // Count total for pagination
    $totalPosts = $conn->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    
    // Get paginated results
    $stmt = $conn->query("SELECT posts.*, users.username FROM posts 
                        JOIN users ON posts.author_id = users.id 
                        ORDER by created_at DESC
                        LIMIT $perPage OFFSET $offset");
}
$posts = $stmt->fetchAll();
$totalPages = ceil($totalPosts / $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Our Stories - Professional Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="styles.css" rel="stylesheet">
  <script src="dark-mode.js" defer></script>
  <style>
    .home-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .home-header {
      text-align: center;
      margin-bottom: 3rem;
      padding: 2rem;
      background: var(--pistachio);
      border-radius: 20px;
      color: var(--dark);
    }
    
    .home-header h1 {
      font-weight: 700;
      margin-bottom: 1rem;
      font-size: 3rem;
    }
    
    .home-header p {
      font-size: 1.2rem;
      opacity: 0.9;
    }
    
    .search-container {
      background-color: var(--light);
      padding: 2rem;
      border-radius: 15px;
      margin-bottom: 2rem;
      box-shadow: 0 5px 15px var(--shadow);
      border-left: 4px solid var(--pistachio);
    }
    
    .search-input {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 0.75rem 1rem;
      transition: all 0.3s ease;
    }
    
    .search-input:focus {
      border-color: var(--pistachio);
      box-shadow: 0 0 0 0.2rem rgba(167, 201, 87, 0.25);
    }
    
    .btn-search {
      background: var(--pistachio);
      border: none;
      border-radius: 10px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      color: var(--dark);
    }
    
    .btn-search:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(167, 201, 87, 0.3);
      background-color: var(--dark);
      color: var(--light);
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
      color: #a7c957 !important;
      font-weight: 700;
      margin-bottom: 1rem;
      font-size: 1.5rem;
      text-decoration: underline;
      transition: color 0.3s ease;
    }
    
    .post-title:hover {
      color: #2e2e2e !important;
      text-decoration: underline;
    }
    
    .post-title a {
      color: #a7c957 !important;
      text-decoration: none;
    }
    
    .post-title a:hover {
      color: #2e2e2e !important;
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
      background: #a7c957;
      color: #2e2e2e;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
      display: inline-block;
    }
    
    .pagination-container {
      text-align: center;
      margin-top: 3rem;
    }
    
    .pagination {
      display: inline-flex;
      gap: 0.5rem;
    }
    
    .pagination a {
      padding: 0.75rem 1rem;
      background: var(--light);
      color: var(--dark);
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
      border: 1px solid #dee2e6;
    }
    
    .pagination a:hover {
      background: #a7c957;
      color: #2e2e2e;
      transform: translateY(-2px);
    }
    
    .pagination a.active {
      background: #a7c957;
      color: #2e2e2e;
      border-color: #a7c957;
    }
    
    .sidebar {
      background: #a7c957;
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
    
    .alert {
      border-radius: 10px;
      border: none;
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
    }
    
    .alert-success {
      background: #a7c957;
      color: #2e2e2e;
    }
    
    .alert-danger {
      background: #ff6b6b;
      color: white;
    }
    
    @media (max-width: 768px) {
      .home-container {
        padding: 1rem;
      }
      
      .home-header h1 {
        font-size: 2rem;
      }
      
      .main-content {
        padding: 1rem;
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
        <a href="home.php" class="active"><i class="fas fa-home"></i>Home</a>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="create_post.php"><i class="fas fa-plus-circle"></i>Create Post</a>
        <a href="category.php"><i class="fas fa-tags"></i>Categories</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
      </div>

      <!-- Main Content -->
      <div class="col-md-9 col-lg-10 main-content">
        <div class="home-container">
          <div class="home-header animate-fade-in-down">
            <h1 class="animate-slide-in-left stagger-1"><i class="fas fa-book-open me-3"></i>Our Stories</h1>
            <p class="animate-slide-in-left stagger-2">Discover amazing stories from our community of writers</p>
          </div>

          <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_GET['message']) ?>
            </div>
          <?php endif; ?>

          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_GET['error']) ?>
            </div>
          <?php endif; ?>

          <!-- Search Form -->
          <div class="search-container animate-fade-in-up stagger-3">
            <form method="GET" action="home.php">
              <div class="row">
                <div class="col-md-8">
                  <input type="text" name="search" class="form-control search-input" 
                         placeholder="Search for stories..." 
                         value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <button class="btn btn-search w-100" type="submit">
                    <i class="fas fa-search me-2"></i>Search Stories
                  </button>
                </div>
              </div>
            </form>
          </div>

          <?php if (count($posts) > 0): ?>
            <div class="row">
              <?php foreach ($posts as $index => $post): ?>
                <?php
                  // Get categories for this post
                  try {
                      $tableCheck = $conn->query("SHOW TABLES LIKE 'post_categories'");
                      if($tableCheck->rowCount() > 0) {
                          $catStmt = $conn->prepare("SELECT c.name FROM categories c
                                                    JOIN post_categories pc ON c.id = pc.category_id
                                                    WHERE pc.post_id = ?");
                      } else {
                          $catStmt = $conn->prepare("SELECT name FROM categories 
                                                    WHERE id IN (SELECT category_id FROM posts WHERE id = ?)");
                      }
                      if($catStmt->execute([$post['id']])) {
                          $postCategories = $catStmt->fetchAll();
                      } else {
                          $postCategories = [];
                      }
                  } catch(PDOException $e) {
                      $postCategories = [];
                  }
                ?>
                <div class="col-lg-6 col-md-6 mb-4">
                  <div class="post-card animate-fade-in-up" style="animation-delay: <?= ($index * 0.1) ?>s;">
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
                      
                      <?php if (!empty($postCategories)): ?> 
                        <div class="post-categories">
                          <?php foreach ($postCategories as $category): ?>
                            <span class="category-badge"><?= htmlspecialchars($category['name']) ?></span>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
              <div class="pagination-container">
                <div class="pagination">
                  <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?><?= isset($_GET['search']) ? '&search='.$_GET['search'] : '' ?>">
                      <i class="fas fa-chevron-left"></i>
                    </a>
                  <?php endif; ?>

                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search='.$_GET['search'] : '' ?>" 
                       <?= $i === $page ? 'class="active"' : '' ?>>
                      <?= $i ?>
                    </a>
                  <?php endfor; ?>

                  <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?><?= isset($_GET['search']) ? '&search='.$_GET['search'] : '' ?>">
                      <i class="fas fa-chevron-right"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="no-posts">
              <i class="fas fa-search"></i>
              <h3>No stories found</h3>
              <p>Try adjusting your search terms or browse all stories.</p>
              <a href="home.php" class="btn btn-search">
                <i class="fas fa-home me-2"></i>View All Stories
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
