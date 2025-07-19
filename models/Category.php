<?php
/**
 * Category Model
 * Handles category operations including CRUD and post relationships
 */

require_once APP_ROOT . '/config/database.php';

class Category {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Get all categories
     */
    public function getAll() {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(pc.post_id) as post_count
                FROM categories c
                LEFT JOIN post_categories pc ON c.id = pc.category_id
                LEFT JOIN posts p ON pc.post_id = p.id AND p.status = 'published'
                GROUP BY c.id
                ORDER BY c.name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all categories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get category by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get category error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new category (admin only)
     */
    public function create($name, $description = '') {
        $errors = [];
        
        // Validation
        if (empty($name) || strlen($name) < 2) {
            $errors[] = "Category name must be at least 2 characters long.";
        }
        
        if (strlen($name) > 50) {
            $errors[] = "Category name is too long. Maximum 50 characters allowed.";
        }
        
        // Check if name already exists
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Category name already exists.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO categories (name, description, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$name, $description]);
            
            return ['success' => true, 'category_id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            error_log("Create category error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to create category. Please try again.']];
        }
    }
    
    /**
     * Update category (admin only)
     */
    public function update($id, $name, $description = '') {
        $errors = [];
        
        // Validation
        if (empty($name) || strlen($name) < 2) {
            $errors[] = "Category name must be at least 2 characters long.";
        }
        
        if (strlen($name) > 50) {
            $errors[] = "Category name is too long. Maximum 50 characters allowed.";
        }
        
        // Check if name already exists (excluding current category)
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Category name already exists.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE categories 
                SET name = ?, description = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $id]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Update category error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to update category. Please try again.']];
        }
    }
    
    /**
     * Delete category (admin only)
     */
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // Delete category relationships
            $stmt = $this->db->prepare("DELETE FROM post_categories WHERE category_id = ?");
            $stmt->execute([$id]);
            
            // Delete category
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Delete category error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to delete category. Please try again.']];
        }
    }
    
    /**
     * Get popular categories
     */
    public function getPopular($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(pc.post_id) as post_count
                FROM categories c
                LEFT JOIN post_categories pc ON c.id = pc.category_id
                LEFT JOIN posts p ON pc.post_id = p.id AND p.status = 'published'
                GROUP BY c.id
                HAVING post_count > 0
                ORDER BY post_count DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get popular categories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get categories with post count
     */
    public function getWithPostCount() {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(pc.post_id) as post_count
                FROM categories c
                LEFT JOIN post_categories pc ON c.id = pc.category_id
                LEFT JOIN posts p ON pc.post_id = p.id AND p.status = 'published'
                GROUP BY c.id
                ORDER BY c.name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get categories with post count error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get category statistics (admin only)
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total categories
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories");
            $stmt->execute();
            $stats['total_categories'] = $stmt->fetchColumn();
            
            // Categories with posts
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT c.id) 
                FROM categories c
                JOIN post_categories pc ON c.id = pc.category_id
                JOIN posts p ON pc.post_id = p.id AND p.status = 'published'
            ");
            $stmt->execute();
            $stats['categories_with_posts'] = $stmt->fetchColumn();
            
            // Most used category
            $stmt = $this->db->prepare("
                SELECT c.name, COUNT(pc.post_id) as post_count
                FROM categories c
                LEFT JOIN post_categories pc ON c.id = pc.category_id
                LEFT JOIN posts p ON pc.post_id = p.id AND p.status = 'published'
                GROUP BY c.id
                ORDER BY post_count DESC
                LIMIT 1
            ");
            $stmt->execute();
            $stats['most_used_category'] = $stmt->fetch();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get category stats error: " . $e->getMessage());
            return false;
        }
    }
}
?> 