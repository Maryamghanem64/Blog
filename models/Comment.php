<?php
/**
 * Comment Model
 * Handles comment operations including CRUD and moderation
 */

require_once APP_ROOT . '/config/database.php';

class Comment {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Add a new comment
     */
    public function add($postId, $userId, $content) {
        $errors = [];
        
        // Validation
        if (empty($content) || strlen($content) < 3) {
            $errors[] = "Comment must be at least 3 characters long.";
        }
        
        if (strlen($content) > 1000) {
            $errors[] = "Comment is too long. Maximum 1000 characters allowed.";
        }
        
        // Check if post exists and is published
        $stmt = $this->db->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published'");
        $stmt->execute([$postId]);
        if ($stmt->rowCount() == 0) {
            $errors[] = "Post not found or not available for comments.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO comments (post_id, user_id, content, status, created_at) 
                VALUES (?, ?, ?, 'approved', NOW())
            ");
            $stmt->execute([$postId, $userId, $content]);
            
            return ['success' => true, 'comment_id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            error_log("Add comment error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to add comment. Please try again.']];
        }
    }
    
    /**
     * Get comments for a post
     */
    public function getByPost($postId, $page = 1, $limit = COMMENTS_PER_PAGE) {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->db->prepare("
                SELECT c.*, u.username, u.email
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ? AND c.status = 'approved'
                ORDER BY c.created_at ASC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$postId, $limit, $offset]);
            $comments = $stmt->fetchAll();
            
            // Get total count
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM comments 
                WHERE post_id = ? AND status = 'approved'
            ");
            $stmt->execute([$postId]);
            $total = $stmt->fetchColumn();
            
            return [
                'comments' => $comments,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            error_log("Get comments error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get comment by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.username, u.email
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get comment error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update comment
     */
    public function update($commentId, $userId, $content) {
        // Check if user owns the comment or is admin
        $comment = $this->getById($commentId);
        if (!$comment || ($comment['user_id'] != $userId && !isAdmin())) {
            return ['success' => false, 'errors' => ['You are not authorized to edit this comment.']];
        }
        
        $errors = [];
        
        // Validation
        if (empty($content) || strlen($content) < 3) {
            $errors[] = "Comment must be at least 3 characters long.";
        }
        
        if (strlen($content) > 1000) {
            $errors[] = "Comment is too long. Maximum 1000 characters allowed.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE comments 
                SET content = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$content, $commentId]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Update comment error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to update comment. Please try again.']];
        }
    }
    
    /**
     * Delete comment
     */
    public function delete($commentId, $userId) {
        // Check if user owns the comment or is admin
        $comment = $this->getById($commentId);
        if (!$comment || ($comment['user_id'] != $userId && !isAdmin())) {
            return ['success' => false, 'errors' => ['You are not authorized to delete this comment.']];
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Delete comment error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to delete comment. Please try again.']];
        }
    }
    
    /**
     * Get all comments (admin only)
     */
    public function getAll($page = 1, $limit = 50, $status = null) {
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = ["1=1"];
            $params = [];
            
            if ($status) {
                $whereConditions[] = "c.status = ?";
                $params[] = $status;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $stmt = $this->db->prepare("
                SELECT c.*, u.username, u.email, p.title as post_title
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN posts p ON c.post_id = p.id
                WHERE $whereClause
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $comments = $stmt->fetchAll();
            
            // Get total count
            $countSql = "
                SELECT COUNT(*) 
                FROM comments c
                WHERE $whereClause
            ";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute(array_slice($params, 0, -2));
            $total = $stmt->fetchColumn();
            
            return [
                'comments' => $comments,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            error_log("Get all comments error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update comment status (admin only)
     */
    public function updateStatus($commentId, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE comments SET status = ? WHERE id = ?");
            $stmt->execute([$status, $commentId]);
            return true;
        } catch (PDOException $e) {
            error_log("Update comment status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get comment statistics (admin only)
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total comments
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM comments");
            $stmt->execute();
            $stats['total_comments'] = $stmt->fetchColumn();
            
            // Approved comments
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM comments WHERE status = 'approved'");
            $stmt->execute();
            $stats['approved_comments'] = $stmt->fetchColumn();
            
            // Pending comments
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM comments WHERE status = 'pending'");
            $stmt->execute();
            $stats['pending_comments'] = $stmt->fetchColumn();
            
            // Comments this month
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM comments 
                WHERE MONTH(created_at) = MONTH(NOW()) 
                AND YEAR(created_at) = YEAR(NOW())
            ");
            $stmt->execute();
            $stats['comments_this_month'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get comment stats error: " . $e->getMessage());
            return false;
        }
    }
}
?> 