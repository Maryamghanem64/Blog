<?php
/**
 * Database Configuration
 * Enhanced database connection with security and error handling
 */

// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'blogg_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// PDO options for security and performance
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $db = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
    
    // Set timezone for database connection
    $db->exec("SET time_zone = '+00:00'");
    
    // Create global connection variable for backward compatibility
    $conn = $db;
    
} catch (PDOException $e) {
    // Log error details
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show user-friendly error message
    if (defined('APP_ENV') && APP_ENV === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed. Please try again later.");
    }
}

// Function to get database connection
function getDB() {
    global $db;
    return $db;
}

// Function to execute prepared statement safely
function executeQuery($sql, $params = []) {
    global $db;
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw $e;
    }
}

// Function to get single row
function getRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

// Function to get multiple rows
function getRows($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

// Function to get single value
function getValue($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

// Function to insert and get last insert ID
function insertAndGetId($sql, $params = []) {
    global $db;
    $stmt = executeQuery($sql, $params);
    return $db->lastInsertId();
}

// Function to update/delete and get affected rows
function executeUpdate($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}
?> 