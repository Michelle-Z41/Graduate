-- Create database if not exists
CREATE DATABASE IF NOT EXISTS l10n_text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE l10n_text;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Texts table
CREATE TABLE IF NOT EXISTS texts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    text_key VARCHAR(255) UNIQUE,
    original_text TEXT NOT NULL,
    optimized_text TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Text optimizations table
CREATE TABLE IF NOT EXISTS text_optimizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text_id INT NOT NULL,
    optimized_text TEXT NOT NULL,
    similarity_score DECIMAL(5,2),
    user_approved BOOLEAN DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (text_id) REFERENCES texts(id) ON DELETE CASCADE
) ENGINE=InnoDB; 