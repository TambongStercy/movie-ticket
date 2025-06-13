<?php
define('DB_SERVER', getenv('DB_HOST'));
define('DB_USERNAME', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME'));

// Using PDO for better security (prevents SQL injection with prepared statements)
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>