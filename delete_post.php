<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $postId = $_POST['post_id'];
    
    // Check if user is admin or the post author
    $stmt = $conn->prepare("SELECT author_id FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if ($post) {
        // Check if user is admin or the post author
        $userStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch();
        
        if ($user['role'] === 'admin' || $post['author_id'] == $_SESSION['user_id']) {
            // Delete the post
            $deleteStmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
            if ($deleteStmt->execute([$postId])) {
                // Redirect with success message
                header("Location: home.php?message=Post deleted successfully");
                exit();
            } else {
                header("Location: home.php?error=Failed to delete post");
                exit();
            }
        } else {
            header("Location: home.php?error=You are not authorized to delete this post");
            exit();
        }
    } else {
        header("Location: home.php?error=Post not found");
        exit();
    }
} else {
    header("Location: home.php");
    exit();
}
?>
