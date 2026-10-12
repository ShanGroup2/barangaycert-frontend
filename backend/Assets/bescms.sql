-- Create database if not exists
CREATE DATABASE IF NOT EXISTS BeSCMS;
USE BeSCMS;

-- Table: residents
CREATE TABLE residents (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    birthdate DATE,
    address TEXT,
    contact_number VARCHAR(20),
    gender ENUM('male','female') DEFAULT NULL,
    registered_voter ENUM('yes','no') DEFAULT 'no',
    added_by VARCHAR(100),
    last_updated_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: users
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','resident','bhw') DEFAULT 'resident',
    verification_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    verification_doc VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- Table: certificate_requests
CREATE TABLE certificate_requests (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    certificate_type VARCHAR(100) NOT NULL,
    purpose TEXT NOT NULL,
    quantity INT(11) DEFAULT 1,
    status ENUM('pending','approved','completed','cancelled','rejected') DEFAULT 'pending',
    admin_notes TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Provision the first admin through a trusted process with a unique credential.