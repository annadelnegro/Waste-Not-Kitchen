<?php
// Non-secret settings (e.g., DB name, host). Keep passwords out of this file.
// Database connection for MAMP (PDO)
$host = 'localhost';
$db   = 'waste_not_kitchen';
$user = 'root';
$pass = 'root';
$port = 8889;
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    throw new Exception("DB Connection failed: " . $e->getMessage());
}
