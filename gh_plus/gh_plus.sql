-- GH+ Government Hospital Plus Database
-- Run this in phpMyAdmin or MySQL CLI: mysql -u root -p < gh_plus.sql

CREATE DATABASE IF NOT EXISTS gh_plus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gh_plus;

-- ============================================================
-- USERS TABLE (all roles)
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nic VARCHAR(12) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient','doctor','admin') NOT NULL DEFAULT 'patient',
    email VARCHAR(150),
    phone VARCHAR(15),
    date_of_birth DATE,
    gender ENUM('male','female','other'),
    city VARCHAR(100),
    blood_type ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-'),
    profile_photo VARCHAR(255) DEFAULT 'default.png',
    status ENUM('pending','active','inactive','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- PATIENT DETAILS
-- ============================================================
CREATE TABLE patient_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    diseases TEXT COMMENT 'comma-separated disease list',
    description TEXT COMMENT 'allergies / additional notes',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- STAFF DETAILS
-- ============================================================
CREATE TABLE staff_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    position ENUM('Doctor','Nurse','Surgeon','Radiologist','Lab Technician','Pharmacist','Other Medical Staff') NOT NULL,
    slmc_no VARCHAR(50),
    hospital VARCHAR(200),
    department VARCHAR(100),
    description TEXT COMMENT 'qualifications / credentials',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- MEDICAL RECORDS
-- ============================================================
CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    visit_date DATE NOT NULL,
    hospital VARCHAR(200),
    diagnosis TEXT NOT NULL,
    prescription TEXT,
    notes_for_patient TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- MEDICAL DOCUMENTS (files attached to records)
-- ============================================================
CREATE TABLE medical_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image','pdf','audio','video','other') DEFAULT 'other',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES medical_records(id) ON DELETE CASCADE
);

-- ============================================================
-- SUPPORT MESSAGES
-- ============================================================
CREATE TABLE support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    category ENUM('Technical','Medical Query','Complaint','Other') DEFAULT 'Other',
    status ENUM('open','resolved') DEFAULT 'open',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- DEFAULT ADMIN ACCOUNT
-- Password: Admin@1234
-- ============================================================
INSERT INTO users (nic, full_name, password, role, email, status)
VALUES (
    '000000000V',
    'System Administrator',
    '$2y$10$abcdefghijklmnopqrstuOwEDU7sq/qsEYPlTR3eL0rDWnZBExzF.',
    'admin',
    'admin@ghplus.lk',
    'active'
);
-- Password above = Admin@1234
-- If login still fails, run setup.php in your browser to regenerate the admin account.
-- Change the password immediately after first login.
