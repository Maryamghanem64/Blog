-- Professional Blog Platform Database Setup
-- This file contains the complete database structure for the blog

-- Create database (uncomment if you need to create the database)
-- CREATE DATABASE IF NOT EXISTS blog_db;
-- USE blog_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    slug VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Posts table
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    slug VARCHAR(255) UNIQUE,
    featured_image VARCHAR(255),
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    views INT DEFAULT 0,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Post categories junction table (for many-to-many relationship)
CREATE TABLE IF NOT EXISTS post_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_category (post_id, category_id)
);

-- Insert default admin user
-- Password: admin123 (hashed with password_hash)
INSERT INTO users (username, email, password, role, status) VALUES 
('admin', 'admin@blog.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert sample categories
INSERT INTO categories (name, description, slug) VALUES 
('Technology', 'Posts about technology, programming, and digital innovations', 'technology'),
('Health', 'Health and wellness related articles', 'health'),
('Lifestyle', 'Lifestyle and personal development content', 'lifestyle'),
('Education', 'Educational content and learning resources', 'education'),
('Travel', 'Travel experiences and destination guides', 'travel'),
('Food', 'Cooking, recipes, and food culture', 'food');

-- Insert sample posts
INSERT INTO posts (author_id, title, content, slug, status, category_id) VALUES 
(1, 'Your Health Is Your Wealth', 'Taking care of your health is the most important investment you can make. A healthy body and mind are the foundation for a successful and fulfilling life. Regular exercise, proper nutrition, and adequate rest are essential components of maintaining good health. Remember, prevention is always better than cure. Start making healthy choices today for a better tomorrow.', 'your-health-is-your-wealth', 'published', 2),
(1, 'helth of heart', 'Heart health is crucial for overall well-being. The heart is one of the most important organs in our body, and taking care of it should be a top priority. Regular cardiovascular exercise, a balanced diet low in saturated fats, and avoiding smoking are key factors in maintaining a healthy heart. Regular check-ups with your doctor can help detect any potential issues early.', 'health-of-heart', 'published', 2),
(1, 'Technology Trends 2024', 'The technology landscape is constantly evolving. In 2024, we are seeing significant advancements in artificial intelligence, machine learning, and sustainable technology. These innovations are reshaping how we live and work. Stay updated with the latest trends to remain competitive in the digital age.', 'technology-trends-2024', 'published', 1),
(1, 'Digital Wellness', 'In our connected world, digital wellness has become increasingly important. Balancing technology use with mental health and real-world connections is essential. Learn how to create healthy digital habits and maintain a positive relationship with technology.', 'digital-wellness', 'published', 1),
(1, 'Mindful Living', 'Mindful living is about being present in the moment and making conscious choices. It involves practicing mindfulness, gratitude, and intentional living. This approach can lead to greater happiness, reduced stress, and improved overall well-being.', 'mindful-living', 'published', 3),
(1, 'Learning in the Digital Age', 'Education has transformed significantly with the advent of digital technology. Online learning platforms, interactive tools, and personalized learning experiences are making education more accessible and effective than ever before.', 'learning-digital-age', 'published', 4);

-- Insert sample comments
INSERT INTO comments (post_id, user_id, comment_text) VALUES 
(1, 1, 'Great article! Health should definitely be a priority for everyone.'),
(1, 1, 'I completely agree with the points made in this post.'),
(2, 1, 'Heart health is so important. Thanks for sharing these valuable insights.'),
(3, 1, 'Interesting read about technology trends. Looking forward to more content like this.');

-- Create indexes for better performance
CREATE INDEX idx_posts_author ON posts(author_id);
CREATE INDEX idx_posts_category ON posts(category_id);
CREATE INDEX idx_posts_status ON posts(status);
CREATE INDEX idx_posts_created ON posts(created_at);
CREATE INDEX idx_comments_post ON comments(post_id);
CREATE INDEX idx_comments_user ON comments(user_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_categories_slug ON categories(slug);

-- Show table structure
SHOW TABLES;

-- Show sample data
SELECT 'Users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'Categories', COUNT(*) FROM categories
UNION ALL
SELECT 'Posts', COUNT(*) FROM posts
UNION ALL
SELECT 'Comments', COUNT(*) FROM comments; 