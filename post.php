
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

if (!isset($_GET['id'])) {
    echo "Post ID is missing!";
    exit();
}

$post_id = $_GET['id'];

// Fix the query to use author_id instead of user_id
$stmt = $conn->prepare("SELECT posts.*, users.username FROM posts 
                        JOIN users ON posts.author_id = users.id 
                        WHERE posts.id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    echo "Post not found!";
    exit();
}

// Update view count
$updateViews = $conn->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
$updateViews->execute([$post_id]);

$comment_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $comment_error = "Invalid security token. Please refresh the page and try again.";
    } else {
        $new_comment = trim($_POST['comment']);
        if (strlen($new_comment) < 3) {
            $comment_error = "Comment must be at least 3 characters long.";
        } elseif (!empty($new_comment)) {
            $insert_stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
            $insert_stmt->execute([$post_id, $_SESSION['user_id'], $new_comment]);
            header("Location: post.php?id=$post_id");
            exit();
        } else {
            $comment_error = "Comment cannot be empty.";
        }
    }
}

$comment_stmt = $conn->prepare("SELECT comments.id, comments.post_id, comments.user_id, comments.comment_text, comments.created_at, users.username FROM comments 
                                JOIN users ON comments.user_id = users.id 
                                WHERE comments.post_id = ? ORDER BY comments.created_at DESC");
$comment_stmt->execute([$post_id]);
$comments = $comment_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($post['title']) ?> - Professional Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="styles.css" rel="stylesheet">
  <script src="dark-mode.js" defer></script>
  <style>
    .post-container {
      max-width: 900px;
      margin: 2rem auto;
      padding: 2rem;
      background: var(--beige);
      border-radius: 20px;
      box-shadow: 0 15px 35px var(--shadow);
      border: 1px solid rgba(167, 201, 87, 0.2);
    }
    
    .post-header {
      text-align: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 3px solid var(--pistachio);
    }
    
    .post-header h1 {
      color: var(--pistachio);
      font-weight: 700;
      margin-bottom: 1rem;
      text-decoration: underline;
    }
    
    .post-meta {
      color: #6c757d;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }
    
    .post-image {
      border-radius: 15px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      margin: 1.5rem 0;
    }
    
    .post-content {
      line-height: 1.8;
      color: var(--dark);
      font-size: 1.1rem;
      margin: 1.5rem 0;
    }
    
    .post-actions {
      margin: 2rem 0;
      padding: 1rem;
      background-color: var(--light);
      border-radius: 10px;
      border-left: 4px solid var(--pistachio);
    }
    
    .btn-edit {
      background: var(--pistachio);
      border: none;
      border-radius: 8px;
      padding: 0.5rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      color: var(--dark);
    }
    
    .btn-edit:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(167, 201, 87, 0.3);
      background-color: var(--dark);
      color: var(--light);
    }
    
    .btn-delete {
      background: #dc3545;
      border: none;
      border-radius: 8px;
      padding: 0.5rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      color: white;
    }
    
    .btn-delete:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
      background-color: #c82333;
    }
    
    .comments-section {
      margin-top: 3rem;
      padding-top: 2rem;
      border-top: 2px solid #dee2e6;
    }
    
    .comments-section h3 {
      color: var(--dark);
      font-weight: 700;
      margin-bottom: 1.5rem;
    }
    
    .comment-form {
      background-color: var(--light);
      padding: 1.5rem;
      border-radius: 15px;
      margin-bottom: 2rem;
      border: 1px solid #dee2e6;
    }
    
    .comment-form textarea {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 1rem;
      transition: all 0.3s ease;
    }
    
    .comment-form textarea:focus {
      border-color: var(--pistachio);
      box-shadow: 0 0 0 0.2rem rgba(167, 201, 87, 0.25);
    }
    
    .btn-comment {
      background: var(--pistachio);
      border: none;
      border-radius: 8px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      transition: all 0.3s ease;
      color: var(--dark);
    }
    
    .btn-comment:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(167, 201, 87, 0.3);
      background-color: var(--dark);
      color: var(--light);
    }
    
    .comment {
      background-color: var(--light);
      padding: 1.5rem;
      border-radius: 15px;
      margin-bottom: 1rem;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      border-left: 4px solid var(--pistachio);
    }
    
    .comment-content {
      color: var(--dark);
      line-height: 1.6;
      margin-bottom: 0.5rem;
    }
    
    .comment-meta {
      color: #6c757d;
      font-size: 0.9rem;
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
    }
    
    @media (max-width: 768px) {
      .post-container {
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
        <a href="create_post.php"><i class="fas fa-plus-circle"></i>Create Post</a>
        <a href="category.php"><i class="fas fa-tags"></i>Categories</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
      </div>

      <!-- Main Content -->
      <div class="col-md-9 col-lg-10 main-content">
        <div class="post-container">
          <div class="post-header animate-fade-in-down">
            <h1 class="animate-slide-in-left stagger-1"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="post-meta animate-slide-in-left stagger-2">
              <i class="fas fa-user me-2"></i>By <strong><?= htmlspecialchars($post['username']) ?></strong>
              <i class="fas fa-calendar me-2 ms-3"></i><?= date('F j, Y', strtotime($post['created_at'])) ?>
              <i class="fas fa-eye me-2 ms-3"></i><?= $post['views'] ?? 0 ?> views
            </div>
          </div>

          <?php if (!empty($post['featured_image'])): ?>
            <div class="text-center animate-fade-in-up stagger-3">
              <img src="uploads/<?= htmlspecialchars($post['featured_image']) ?>" 
                   alt="Post Image" class="img-fluid post-image" style="max-height: 400px;">
            </div>
          <?php endif; ?>

          <div class="post-content animate-fade-in-up stagger-4">
            <?= nl2br(htmlspecialchars($post['content'])) ?>
          </div>

          <?php if ($_SESSION['user_id'] == $post['author_id']): ?>
            <div class="post-actions">
              <h5><i class="fas fa-cog me-2"></i>Post Actions</h5>
              <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-edit btn-sm me-2">
                <i class="fas fa-edit me-1"></i>Edit Post
              </a>
              <form action="delete_post.php" method="POST" style="display:inline;">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <button type="submit" class="btn btn-delete btn-sm" 
                        onclick="return confirm('Are you sure you want to delete this post?');">
                  <i class="fas fa-trash me-1"></i>Delete Post
                </button>
              </form>
            </div>
          <?php endif; ?>

          <div class="comments-section animate-fade-in-up" style="animation-delay: 0.5s;">
            <h3 class="animate-slide-in-left stagger-1"><i class="fas fa-comments me-2"></i>Comments (<?= count($comments) ?>)</h3>
            
            <!-- Comment Form -->
            <div class="comment-form">
              <h5><i class="fas fa-comment me-2"></i>Add a Comment</h5>
              <?php if ($comment_error): ?>
                <div class="alert alert-danger">
                  <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($comment_error) ?>
                </div>
              <?php endif; ?>
              
              <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                <div class="mb-3">
                  <textarea class="form-control" name="comment" rows="4" 
                            placeholder="Write your comment here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-comment">
                  <i class="fas fa-paper-plane me-2"></i>Post Comment
                </button>
              </form>
            </div>

            <!-- Comments List -->
            <?php if (count($comments) > 0): ?>
              <?php foreach ($comments as $comment): ?>
                <div class="comment">
                  <div class="comment-content">
                    <?= nl2br(htmlspecialchars($comment['comment_text'])) ?>
                  </div>
                  <div class="comment-meta">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($comment['username']) ?>
                    <i class="fas fa-clock me-2 ms-3"></i><?= date('F j, Y g:i A', strtotime($comment['created_at'])) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-center text-muted">
                <i class="fas fa-comment-slash fa-3x mb-3"></i>
                <p>No comments yet. Be the first to comment!</p>
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
