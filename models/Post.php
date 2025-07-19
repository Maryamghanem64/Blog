<?php
/**
 * Post Model
 * Handles blog post operations including CRUD, search, and categorization
 */

require_once APP_ROOT . '/config/database.php';

class Post {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Create a new post
     */
    public function create($userId, $data) {
        $errors = [];
        
        // Validation
        if (empty($data['title']) || strlen($data['title']) < 5) {
            $errors[] = "Title must be at least 5 characters long.";
        }
        
        if (empty($data['content']) || strlen($data['content']) < 20) {
            $errors[] = "Content must be at least 20 characters long.";
        }
        
        if (empty($data['category_id'])) {
            $errors[] = "Please select a category.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Generate slug
            $slug = $this->generateUniqueSlug($data['title']);
            
            // Insert post
            $stmt = $this->db->prepare("
                INSERT INTO posts (user_id, title, content, slug, image, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'published', NOW())
            ");
            $stmt->execute([
                $userId,
                $data['title'],
                $data['content'],
                $slug,
                $data['image'] ?? null
            ]);
            
            $postId = $this->db->lastInsertId();
            
            // Insert category relationship
            $stmt = $this->db->prepare("
                INSERT INTO post_categories (post_id, category_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$postId, $data['category_id']]);
            
            $this->db->commit();
            
            return ['success' => true, 'post_id' => $postId, 'slug' => $slug];
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Create post error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to create post. Please try again.']];
        }
    }
    
    /**
     * Update an existing post
     */
    public function update($postId, $userId, $data) {
        // Check if user owns the post or is admin
        $post = $this->getById($postId);
        if (!$post || ($post['user_id'] != $userId && !isAdmin())) {
            return ['success' => false, 'errors' => ['You are not authorized to edit this post.']];
        }
        
        $errors = [];
        
        // Validation
        if (empty($data['title']) || strlen($data['title']) < 5) {
            $errors[] = "Title must be at least 5 characters long.";
        }
        
        if (empty($data['content']) || strlen($data['content']) < 20) {
            $errors[] = "Content must be at least 20 characters long.";
        }
        
        if (empty($data['category_id'])) {
            $errors[] = "Please select a category.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Update post
            $stmt = $this->db->prepare("
                UPDATE posts 
                SET title = ?, content = ?, image = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([
                $data['title'],
                $data['content'],
                $data['image'] ?? $post['image'],
                $postId
            ]);
            
            // Update category relationship
            $stmt = $this->db->prepare("DELETE FROM post_categories WHERE post_id = ?");
            $stmt->execute([$postId]);
            
            $stmt = $this->db->prepare("
                INSERT INTO post_categories (post_id, category_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$postId, $data['category_id']]);
            
            $this->db->commit();
            
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Update post error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to update post. Please try again.']];
        }
    }
    
    /**
     * Delete a post
     */
    public function delete($postId, $userId) {
        // Check if user owns the post or is admin
        $post = $this->getById($postId);
        if (!$post || ($post['user_id'] != $userId && !isAdmin())) {
            return ['success' => false, 'errors' => ['You are not authorized to delete this post.']];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Delete comments
            $stmt = $this->db->prepare("DELETE FROM comments WHERE post_id = ?");
            $stmt->execute([$postId]);
            
            // Delete category relationships
            $stmt = $this->db->prepare("DELETE FROM post_categories WHERE post_id = ?");
            $stmt->execute([$postId]);
            
            // Delete post
            $stmt = $this->db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            
            $this->db->commit();
            
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Delete post error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to delete post. Please try again.']];
        }
    }
    
    /**
     * Get post by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username, u.email,
                       GROUP_CONCAT(c.name) as categories
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN post_categories pc ON p.id = pc.post_id
                LEFT JOIN categories c ON pc.category_id = c.id
                WHERE p.id = ? AND p.status = 'published'
                GROUP BY p.id
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get post error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get post by slug
     */
    public function getBySlug($slug) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username, u.email,
                       GROUP_CONCAT(c.name) as categories
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN post_categories pc ON p.id = pc.post_id
                LEFT JOIN categories c ON pc.category_id = c.id
                WHERE p.slug = ? AND p.status = 'published'
                GROUP BY p.id
            ");
            $stmt->execute([$slug]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get post by slug error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all posts with pagination
     */
    public function getAll($page = 1, $limit = POSTS_PER_PAGE, $categoryId = null, $search = null) {
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = ["p.status = 'published'"];
            $params = [];
            
            if ($categoryId) {
                $whereConditions[] = "pc.category_id = ?";
                $params[] = $categoryId;
            }
            
            if ($search) {
                $whereConditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get posts
            $sql = "
                SELECT p.*, u.username,
                       GROUP_CONCAT(c.name) as categories,
                       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN post_categories pc ON p.id = pc.post_id
                LEFT JOIN categories c ON pc.category_id = c.id
                WHERE $whereClause
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $posts = $stmt->fetchAll();
            
            // Get total count
            $countSql = "
                SELECT COUNT(DISTINCT p.id) 
                FROM posts p
                LEFT JOIN post_categories pc ON p.id = pc.post_id
                WHERE $whereClause
            ";
            
            $stmt = $this->db->prepare($countSql);
            $stmt->execute(array_slice($params, 0, -2));
            $total = $stmt->fetchColumn();
            
            return [
                'posts' => $posts,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            error_log("Get all posts error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get posts by user
     */
    public function getByUser($userId, $page = 1, $limit = POSTS_PER_PAGE) {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->db->prepare("
                SELECT p.*, GROUP_CONCAT(c.name) as categories,
                       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                FROM posts p
                LEFT JOIN post_categories pc ON p.id = pc.post_id
                LEFT JOIN categories c ON pc.category_id = c.id
                WHERE p.user_id = ? AND p.status = 'published'
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            $posts = $stmt->fetchAll();
            
            // Get total count
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM posts 
                WHERE user_id = ? AND status = 'published'
            ");
            $stmt->execute([$userId]);
            $total = $stmt->fetchColumn();
            
            return [
                'posts' => $posts,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            error_log("Get posts by user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get popular posts
     */
    public function getPopular($limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username,
                       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.status = 'published'
                ORDER BY p.views DESC, p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get popular posts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent posts
     */
    public function getRecent($limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username,
                       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.status = 'published'
                ORDER BY p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get recent posts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Increment view count
     */
    public function incrementViews($postId) {
        try {
            $stmt = $this->db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
            $stmt->execute([$postId]);
            return true;
        } catch (PDOException $e) {
            error_log("Increment views error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search posts
     */
    public function search($query, $page = 1, $limit = POSTS_PER_PAGE) {
        return $this->getAll($page, $limit, null, $query);
    }
    
    /**
     * Get posts by category
     */
    public function getByCategory($categoryId, $page = 1, $limit = POSTS_PER_PAGE) {
        return $this->getAll($page, $limit, $categoryId);
    }
    
    /**
     * Generate unique slug
     */
    private function generateUniqueSlug($title) {
        $slug = generateSlug($title);
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Check if slug exists
     */
    private function slugExists($slug) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM posts WHERE slug = ?");
            $stmt->execute([$slug]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Check slug exists error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get post statistics (admin only)
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total posts
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published'");
            $stmt->execute();
            $stats['total_posts'] = $stmt->fetchColumn();
            
            // Total views
            $stmt = $this->db->prepare("SELECT SUM(views) FROM posts WHERE status = 'published'");
            $stmt->execute();
            $stats['total_views'] = $stmt->fetchColumn() ?: 0;
            
            // Posts this month
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM posts 
                WHERE status = 'published' 
                AND MONTH(created_at) = MONTH(NOW()) 
                AND YEAR(created_at) = YEAR(NOW())
            ");
            $stmt->execute();
            $stats['posts_this_month'] = $stmt->fetchColumn();
            
            // Most viewed post
            $stmt = $this->db->prepare("
                SELECT title, views FROM posts 
                WHERE status = 'published' 
                ORDER BY views DESC 
                LIMIT 1
            ");
            $stmt->execute();
            $stats['most_viewed'] = $stmt->fetch();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get post stats error: " . $e->getMessage());
            return false;
        }
    }
}
?> 