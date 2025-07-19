<?php
$host = 'localhost';         
$dbname = 'blogg_db';        
$username = 'root';      
$password = '';             

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {

    die("error with the database " . $e->getMessage());
}
?>
