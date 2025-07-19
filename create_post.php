<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// CSRF Protection functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please refresh the page and try again.";
    } else {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $category_id = $_POST['category_id'] ?? null;
        $user_id = $_SESSION['user_id'];

        // Handle file upload
        $featured_image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = basename($_FILES['image']['name']);
            $fileSize = $_FILES['image']['size'];
            $fileType = $_FILES['image']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $featured_image = $newFileName;
                } else {
                    $error = "There was an error moving the uploaded file.";
                }
            } else {
                $error = "Upload failed. Allowed file types: " . implode(', ', $allowedfileExtensions);
            }
        }

        if (empty($title) || empty($content) || empty($category_id)) {
            $error = "All fields are required.";
        } elseif (strlen($title) < 5) {
            $error = "Title must be at least 5 characters long.";
        } elseif (strlen($content) < 20) {
            $error = "Content must be at least 20 characters long.";
        } elseif (!$error) {
            try {
                // Start transaction
                $conn->beginTransaction();

                // Generate slug
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
                $slugCheck = $conn->prepare("SELECT id FROM posts WHERE slug = ?");
                $originalSlug = $slug;
                $counter = 1;
                while (true) {
                    $slugCheck->execute([$slug]);
                    if ($slugCheck->rowCount() === 0) break;
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                // Insert post (match schema: author_id, slug, featured_image, status)
                $stmt = $conn->prepare("INSERT INTO posts (author_id, title, content, slug, featured_image, status, category_id) VALUES (?, ?, ?, ?, ?, 'published', ?)");
                $stmt->execute([$user_id, $title, $content, $slug, $featured_image, $category_id]);
                $post_id = $conn->lastInsertId();

                // Commit transaction
                $conn->commit();
                $success = "Post created successfully!";
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = "Failed to create post. Please try again.";
            }
        }
    }
}

// fetch categories
$catStmt = $conn->query("SELECT id, name FROM categories");
$categories = $catStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create New Post - Professional Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="styles.css" rel="stylesheet">
  <script src="dark-mode.js" defer></script>
  <style>
    .create-post-container {
      max-width: 900px;
      margin: 2rem auto;
      padding: 2rem;
      background: var(--beige);
      border-radius: 20px;
      box-shadow: 0 15px 35px var(--shadow);
      border: 1px solid rgba(167, 201, 87, 0.2);
    }
    
    .create-post-header {
      text-align: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 3px solid var(--pistachio);
    }
    
    .create-post-header h1 {
      color: var(--dark);
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    .form-label {
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 0.75rem 1rem;
      transition: all 0.3s ease;
      background-color: var(--light);
      color: var(--dark);
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--pistachio);
      box-shadow: 0 0 0 0.2rem rgba(167, 201, 87, 0.25);
    }
    
    .btn-create-post {
      background: var(--pistachio);
      border: none;
      border-radius: 10px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      color: var(--dark);
    }
    
    .btn-create-post:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(167, 201, 87, 0.3);
      background-color: var(--dark);
      color: var(--light);
    }
    
    .alert {
      border-radius: 10px;
      border: none;
      padding: 1rem 1.5rem;
    }
    
    .file-upload-wrapper {
      position: relative;
      display: inline-block;
      width: 100%;
    }
    
    .file-upload-wrapper input[type=file] {
      position: absolute;
      left: -9999px;
    }
    
    .file-upload-label {
      display: block;
      padding: 1rem;
      border: 2px dashed #dee2e6;
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      background-color: var(--light);
    }
    
    .file-upload-label:hover {
      border-color: var(--pistachio);
      background-color: rgba(167, 201, 87, 0.1);
    }
    
    .file-upload-label i {
      font-size: 2rem;
      color: #6c757d;
      margin-bottom: 0.5rem;
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
      .create-post-container {
        margin: 1rem;
        padding: 1.5rem;
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
        <a href="home.php"><i class="fas fa-home"></i>Home</a>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="create_post.php" class="active"><i class="fas fa-plus-circle"></i>Create Post</a>
        <a href="category.php"><i class="fas fa-tags"></i>Categories</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
      </div>

      <!-- Main Content -->
      <div class="col-md-9 col-lg-10 main-content">
        <div class="create-post-container">
          <div class="create-post-header animate-fade-in-down">
            <h1 class="animate-slide-in-left stagger-1"><i class="fas fa-edit me-2"></i>Create New Post</h1>
            <p class="animate-slide-in-left stagger-2 text-muted">Share your thoughts with the world</p>
          </div>

          <?php if ($error): ?>
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <?php if ($success): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
            
            <div class="row">
              <div class="col-md-8">
                <div class="mb-3">
                  <label for="title" class="form-label">
                    <i class="fas fa-heading me-2"></i>Post Title
                  </label>
                  <input type="text" class="form-control" id="title" name="title" required 
                         placeholder="Enter your post title..." value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="mb-3">
                  <label for="content" class="form-label">
                    <i class="fas fa-align-left me-2"></i>Post Content
                  </label>
                  <textarea class="form-control" id="content" name="content" rows="12" required 
                            placeholder="Write your post content here..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </div>
              </div>

              <div class="col-md-4">
                <div class="mb-3">
                  <label for="category_id" class="form-label">
                    <i class="fas fa-tag me-2"></i>Category
                  </label>
                  <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                      <option value="<?= $category['id'] ?>" <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label">
                    <i class="fas fa-image me-2"></i>Featured Image
                  </label>
                  <div class="file-upload-wrapper">
                    <label for="image" class="file-upload-label">
                      <i class="fas fa-cloud-upload-alt"></i>
                      <div>Click to upload image</div>
                      <small class="text-muted">JPG, PNG, GIF up to 5MB</small>
                    </label>
                    <input type="file" id="image" name="image" accept="image/*">
                  </div>
                </div>

                <div class="d-grid">
                  <button type="submit" class="btn btn-create-post btn-lg">
                    <i class="fas fa-paper-plane me-2"></i>Publish Post
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // File upload preview
    document.getElementById('image').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const label = document.querySelector('.file-upload-label');
        label.innerHTML = `
          <i class="fas fa-check-circle text-success"></i>
          <div>${file.name}</div>
          <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
        `;
      }
    });
  </script>
</body>
</html>
