<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$postId = $_GET['id'] ?? null;
if (!$postId) {
    header("Location: home.php");
    exit();
}

// Get post data
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND author_id = ?");
$stmt->execute([$postId, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: home.php");
    exit();
}

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$error = '';
$success = '';

if (isset($_POST['update_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = $_POST['category_id'] ?? null;
    $status = $_POST['status'];
    
    // Validation
    if (empty($title) || empty($content)) {
        $error = "Title and content are required.";
    } else {
        // Handle image upload
        $featured_image = $post['featured_image']; // Keep existing image by default
        
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_info = pathinfo($_FILES['featured_image']['name']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_info['extension'];
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                    $featured_image = $new_filename;
                }
            }
        }
        
        // Update post
        $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, category_id = ?, status = ?, featured_image = ?, updated_at = NOW() WHERE id = ? AND author_id = ?");
        
        if ($stmt->execute([$title, $content, $category_id, $status, $featured_image, $postId, $_SESSION['user_id']])) {
            $success = "Post updated successfully!";
            // Refresh post data
            $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND author_id = ?");
            $stmt->execute([$postId, $_SESSION['user_id']]);
            $post = $stmt->fetch();
        } else {
            $error = "Failed to update post. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Post - Professional Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="styles.css" rel="stylesheet">
  <script src="dark-mode.js" defer></script>
  <style>
    .edit-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .edit-header {
      background: var(--pistachio);
      color: var(--dark);
      padding: 2rem;
      border-radius: 20px;
      margin-bottom: 2rem;
      text-align: center;
      box-shadow: 0 10px 25px var(--shadow);
    }
    
    .edit-header h1 {
      font-weight: 700;
      margin-bottom: 1rem;
      font-size: 2.5rem;
    }
    
    .edit-header p {
      font-size: 1.1rem;
      opacity: 0.9;
    }
    
    .edit-form {
      background: var(--light);
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 10px 25px var(--shadow);
      border-left: 4px solid var(--pistachio);
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    .form-label {
      color: var(--dark);
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .form-control {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 1rem;
      font-size: 1rem;
      transition: all 0.3s ease;
      background-color: var(--light);
      color: var(--dark);
    }
    
    .form-control:focus {
      border-color: var(--pistachio);
      box-shadow: 0 0 0 0.2rem rgba(167, 201, 87, 0.25);
      background-color: var(--light);
    }
    
    .form-select {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 1rem;
      font-size: 1rem;
      transition: all 0.3s ease;
      background-color: var(--light);
      color: var(--dark);
    }
    
    .form-select:focus {
      border-color: var(--pistachio);
      box-shadow: 0 0 0 0.2rem rgba(167, 201, 87, 0.25);
      background-color: var(--light);
    }
    
    .btn-update {
      background: var(--pistachio);
      border: none;
      border-radius: 10px;
      padding: 1rem 2rem;
      font-weight: 600;
      font-size: 1.1rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      color: var(--dark);
      margin-right: 1rem;
    }
    
    .btn-update:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(167, 201, 87, 0.3);
      background-color: var(--dark);
      color: var(--light);
    }
    
    .btn-cancel {
      background: #6c757d;
      border: none;
      border-radius: 10px;
      padding: 1rem 2rem;
      font-weight: 600;
      font-size: 1.1rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      color: white;
      text-decoration: none;
    }
    
    .btn-cancel:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(108, 117, 125, 0.3);
      background-color: #5a6268;
      color: white;
      text-decoration: none;
    }
    
    .current-image {
      max-width: 200px;
      border-radius: 10px;
      margin: 1rem 0;
      border: 2px solid var(--pistachio);
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
    
    @media (max-width: 768px) {
      .edit-container {
        padding: 1rem;
      }
      
      .edit-header h1 {
        font-size: 2rem;
      }
      
      .main-content {
        padding: 1rem;
      }
      
      .edit-form {
        padding: 1.5rem;
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
        <a href="admin.php"><i class="fas fa-cog"></i>Admin Panel</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
      </div>

      <!-- Main Content -->
      <div class="col-md-9 col-lg-10 main-content">
        <div class="edit-container">
          <div class="edit-header">
            <h1><i class="fas fa-edit me-3"></i>Edit Post</h1>
            <p>Update your blog post content and settings</p>
          </div>

          <?php if ($success): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <div class="edit-form">
            <form method="POST" enctype="multipart/form-data">
              <div class="form-group">
                <label for="title" class="form-label">Post Title</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?= htmlspecialchars($post['title']) ?>" required>
              </div>

              <div class="form-group">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($post['content']) ?></textarea>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                      <option value="">Select Category</option>
                      <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $post['category_id'] == $category['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($category['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                      <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                      <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="featured_image" class="form-label">Featured Image</label>
                <?php if (!empty($post['featured_image'])): ?>
                  <div>
                    <p class="text-muted">Current image:</p>
                    <img src="uploads/<?= htmlspecialchars($post['featured_image']) ?>" 
                         alt="Current featured image" class="current-image">
                  </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="featured_image" name="featured_image" 
                       accept="image/*">
                <small class="text-muted">Leave empty to keep the current image</small>
              </div>

              <div class="form-actions">
                <button type="submit" name="update_post" class="btn-update">
                  <i class="fas fa-save me-2"></i>Update Post
                </button>
                <a href="post.php?id=<?= $post['id'] ?>" class="btn-cancel">
                  <i class="fas fa-times me-2"></i>Cancel
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
