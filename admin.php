<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Check if user is admin
$userStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();

if ($user['role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

// Handle post deletion
if (isset($_POST['delete_post'])) {
    $postId = $_POST['post_id'];
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt->execute([$postId])) {
        $success = "Post deleted successfully!";
    } else {
        $error = "Failed to delete post.";
    }
}

// Get statistics
$postCount = $conn->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$commentCount = $conn->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$categoryCount = $conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Get recent posts
$recentPosts = $conn->query("SELECT posts.*, users.username FROM posts 
                            JOIN users ON posts.author_id = users.id 
                            ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Get all posts for management
$allPosts = $conn->query("SELECT posts.*, users.username, categories.name as category_name 
                          FROM posts 
                          JOIN users ON posts.author_id = users.id 
                          LEFT JOIN categories ON posts.category_id = categories.id 
                          ORDER BY created_at DESC")->fetchAll();

// Get all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY username")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Professional Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="styles.css" rel="stylesheet">
  <script src="dark-mode.js" defer></script>
  <style>
    .admin-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .admin-header {
      background: var(--pistachio);
      color: var(--dark);
      padding: 2rem;
      border-radius: 20px;
      margin-bottom: 2rem;
      text-align: center;
      box-shadow: 0 10px 25px var(--shadow);
    }
    
    .admin-header h1 {
      font-weight: 700;
      margin-bottom: 1rem;
      font-size: 2.5rem;
    }
    
    .admin-header p {
      font-size: 1.1rem;
      opacity: 0.9;
    }
    
    .admin-stat-card {
      background: var(--light);
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 10px 25px var(--shadow);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border-left: 4px solid var(--pistachio);
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .admin-stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px var(--shadow);
    }
    
    .admin-stat-icon {
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
    
    .admin-stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: var(--dark);
    }
    
    .admin-stat-label {
      color: #6c757d;
      font-size: 1rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
    }
    
    .admin-panel {
      background: var(--light);
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 10px 25px var(--shadow);
      margin-bottom: 2rem;
      border-left: 4px solid var(--pistachio);
    }
    
    .admin-panel h3 {
      color: var(--dark);
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .admin-table {
      background: var(--light);
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px var(--shadow);
    }
    
    .admin-table th {
      background: var(--pistachio);
      color: var(--dark);
      font-weight: 600;
      border: none;
      padding: 1rem;
    }
    
    .admin-table td {
      border: none;
      border-bottom: 1px solid #e9ecef;
      padding: 1rem;
      vertical-align: middle;
    }
    
    .admin-table tr:hover {
      background-color: rgba(167, 201, 87, 0.1);
    }
    
    .btn-admin {
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      border: none;
      margin-right: 0.5rem;
    }
    
    .btn-edit {
      background: var(--pistachio);
      color: var(--dark);
    }
    
    .btn-edit:hover {
      background: var(--dark);
      color: var(--light);
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
    
    .btn-view {
      background: var(--pistachio);
      color: white;
    }
    
    .btn-view:hover {
      background: var(--primary-dark);
      color: white;
      transform: translateY(-2px);
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
    
    .alert {
      border-radius: 10px;
      border: none;
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
    }
    
    .alert-success {
      background: var(--pistachio);
      color: var(--dark);
    }
    
    .alert-danger {
      background: #ff6b6b;
      color: white;
    }
    
    .nav-tabs {
      border-bottom: 2px solid var(--pistachio);
      margin-bottom: 2rem;
    }
    
    .nav-tabs .nav-link {
      border: none;
      color: #6c757d;
      font-weight: 600;
      padding: 1rem 2rem;
      border-radius: 10px 10px 0 0;
      margin-right: 0.5rem;
    }
    
    .nav-tabs .nav-link.active {
      background: var(--pistachio);
      color: var(--dark);
      border: none;
    }
    
    .nav-tabs .nav-link:hover {
      border: none;
      background: rgba(167, 201, 87, 0.2);
      color: var(--dark);
    }
    
    @media (max-width: 768px) {
      .admin-container {
        padding: 1rem;
      }
      
      .admin-header h1 {
        font-size: 2rem;
      }
      
      .main-content {
        padding: 1rem;
      }
      
      .admin-table {
        font-size: 0.9rem;
      }
      
      .btn-admin {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
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
        <a href="category.php"><i class="fas fa-tags"></i>Categories</a>
        <a href="admin.php" class="active"><i class="fas fa-cog"></i>Admin Panel</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
      </div>

      <!-- Main Content -->
      <div class="col-md-9 col-lg-10 main-content">
        <div class="admin-container">
          <div class="admin-header">
            <h1><i class="fas fa-cog me-3"></i>Admin Panel</h1>
            <p>Manage your blog content, users, and settings</p>
          </div>

          <?php if (isset($success)): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
          <?php endif; ?>

          <?php if (isset($error)): ?>
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <!-- Statistics -->
          <div class="row">
            <div class="col-lg-3 col-md-6">
              <div class="admin-stat-card">
                <div class="admin-stat-icon">
                  <i class="fas fa-file-alt"></i>
                </div>
                <div class="admin-stat-number"><?= $postCount ?></div>
                <div class="admin-stat-label">Total Posts</div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="admin-stat-card">
                <div class="admin-stat-icon">
                  <i class="fas fa-comments"></i>
                </div>
                <div class="admin-stat-number"><?= $commentCount ?></div>
                <div class="admin-stat-label">Total Comments</div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="admin-stat-card">
                <div class="admin-stat-icon">
                  <i class="fas fa-users"></i>
                </div>
                <div class="admin-stat-number"><?= $userCount ?></div>
                <div class="admin-stat-label">Total Users</div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="admin-stat-card">
                <div class="admin-stat-icon">
                  <i class="fas fa-tags"></i>
                </div>
                <div class="admin-stat-number"><?= $categoryCount ?></div>
                <div class="admin-stat-label">Categories</div>
              </div>
            </div>
          </div>

          <!-- Management Tabs -->
          <div class="admin-panel">
            <ul class="nav nav-tabs" id="adminTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">
                  <i class="fas fa-file-alt me-2"></i>Posts Management
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                  <i class="fas fa-tags me-2"></i>Categories
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                  <i class="fas fa-users me-2"></i>Users
                </button>
              </li>
            </ul>

            <div class="tab-content" id="adminTabsContent">
              <!-- Posts Management Tab -->
              <div class="tab-pane fade show active" id="posts" role="tabpanel">
                <h3><i class="fas fa-file-alt me-2"></i>Posts Management</h3>
                <div class="table-responsive">
                  <table class="table admin-table">
                    <thead>
                      <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($allPosts as $post): ?>
                        <tr>
                          <td>
                            <strong><?= htmlspecialchars($post['title']) ?></strong>
                          </td>
                          <td><?= htmlspecialchars($post['username']) ?></td>
                          <td>
                            <?php if ($post['category_name']): ?>
                              <span class="badge bg-secondary"><?= htmlspecialchars($post['category_name']) ?></span>
                            <?php else: ?>
                              <span class="text-muted">No category</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($post['status'] === 'published'): ?>
                              <span class="badge bg-success">Published</span>
                            <?php else: ?>
                              <span class="badge bg-warning">Draft</span>
                            <?php endif; ?>
                          </td>
                          <td><?= date('M j, Y', strtotime($post['created_at'])) ?></td>
                          <td>
                            <a href="post.php?id=<?= $post['id'] ?>" class="btn-admin btn-view">
                              <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn-admin btn-edit">
                              <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post?')">
                              <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                              <button type="submit" name="delete_post" class="btn-admin btn-delete">
                                <i class="fas fa-trash"></i>
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Categories Tab -->
              <div class="tab-pane fade" id="categories" role="tabpanel">
                <h3><i class="fas fa-tags me-2"></i>Categories Management</h3>
                <div class="table-responsive">
                  <table class="table admin-table">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Posts Count</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($categories as $category): ?>
                        <?php
                          $postCountStmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ?");
                          $postCountStmt->execute([$category['id']]);
                          $categoryPostCount = $postCountStmt->fetchColumn();
                        ?>
                        <tr>
                          <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                          <td><?= htmlspecialchars($category['description'] ?? 'No description') ?></td>
                          <td><span class="badge" style="background-color: var(--pistachio); color: var(--dark);"><?= $categoryPostCount ?></span></td>
                          <td>
                            <a href="category.php?id=<?= $category['id'] ?>" class="btn-admin btn-view">
                              <i class="fas fa-eye"></i>
                            </a>
                            <a href="#" class="btn-admin btn-edit">
                              <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn-admin btn-delete">
                              <i class="fas fa-trash"></i>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Users Tab -->
              <div class="tab-pane fade" id="users" role="tabpanel">
                <h3><i class="fas fa-users me-2"></i>Users Management</h3>
                <div class="table-responsive">
                  <table class="table admin-table">
                    <thead>
                      <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($users as $user): ?>
                        <tr>
                          <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                          <td><?= htmlspecialchars($user['email']) ?></td>
                          <td>
                            <?php if ($user['role'] === 'admin'): ?>
                              <span class="badge bg-danger">Admin</span>
                            <?php else: ?>
                              <span class="badge bg-secondary">User</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($user['status'] === 'active'): ?>
                              <span class="badge bg-success">Active</span>
                            <?php else: ?>
                              <span class="badge bg-warning">Inactive</span>
                            <?php endif; ?>
                          </td>
                          <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                          <td>
                            <a href="#" class="btn-admin btn-edit">
                              <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn-admin btn-delete">
                              <i class="fas fa-trash"></i>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html> 