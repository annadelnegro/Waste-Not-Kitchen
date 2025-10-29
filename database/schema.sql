CREATE DATABASE IF NOT EXISTS waste_not_kitchen CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE waste_not_kitchen;

CREATE TABLE IF NOT EXISTS users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(50) NOT NULL,
	password VARCHAR(255) NOT NULL,
	role ENUM('admin', 'restaurant', 'customer', 'donor', 'needy') NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
