CREATE DATABASE image_host;

USE image_host;

CREATE TABLE images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_id VARCHAR(20) NOT NULL UNIQUE,
    original_filename VARCHAR(255) NOT NULL,
    new_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    upload_ip VARCHAR(45) NOT NULL,
    scan_status ENUM('clean', 'infected') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
