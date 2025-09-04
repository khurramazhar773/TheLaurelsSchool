-- The Laurels School LMS - Complete Database Setup
-- Run this script to create the database and all tables

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS the_laurels_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE the_laurels_school;

-- Create users table first (for admin accounts)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create students table
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Basic Personal Information
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,
    sex ENUM('Male', 'Female') NOT NULL,
    date_of_birth DATE NOT NULL,
    birth_place VARCHAR(100) NOT NULL,
    religion VARCHAR(50) DEFAULT NULL,
    
    -- Student Address
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    
    -- Father's Information
    father_name VARCHAR(100) NOT NULL,
    father_occupation VARCHAR(100) DEFAULT NULL,
    father_address TEXT DEFAULT NULL,
    father_position VARCHAR(100) DEFAULT NULL,
    father_work_phone VARCHAR(20) DEFAULT NULL,
    father_cell VARCHAR(20) DEFAULT NULL,
    
    -- Mother's Information
    mother_name VARCHAR(100) NOT NULL,
    mother_occupation VARCHAR(100) DEFAULT NULL,
    mother_address TEXT DEFAULT NULL,
    mother_position VARCHAR(100) DEFAULT NULL,
    mother_work_phone VARCHAR(20) DEFAULT NULL,
    mother_cell VARCHAR(20) DEFAULT NULL,
    
    -- Guardian Information (if child lives with someone other than parents)
    guardian_name VARCHAR(100) DEFAULT NULL,
    guardian_relation VARCHAR(50) DEFAULT NULL,
    guardian_phone VARCHAR(20) DEFAULT NULL,
    guardian_address TEXT DEFAULT NULL,
    
    -- Emergency Contact
    emergency_contact_name VARCHAR(100) NOT NULL,
    emergency_contact_phone VARCHAR(20) NOT NULL,
    
    -- Previous School Information
    last_school_attended VARCHAR(200) DEFAULT NULL,
    last_school_year VARCHAR(10) DEFAULT NULL,
    last_school_grade VARCHAR(20) DEFAULT NULL,
    last_school_address TEXT DEFAULT NULL,
    
    -- Medical History - Checkboxes for conditions
    has_asthma BOOLEAN DEFAULT FALSE,
    has_allergies BOOLEAN DEFAULT FALSE,
    has_heart_disease BOOLEAN DEFAULT FALSE,
    has_convulsions BOOLEAN DEFAULT FALSE,
    has_diabetes BOOLEAN DEFAULT FALSE,
    has_cancer BOOLEAN DEFAULT FALSE,
    has_tuberculosis BOOLEAN DEFAULT FALSE,
    has_epilepsy BOOLEAN DEFAULT FALSE,
    has_hearing_problems BOOLEAN DEFAULT FALSE,
    has_speech_problems BOOLEAN DEFAULT FALSE,
    has_orthopedic_problems BOOLEAN DEFAULT FALSE,
    has_other_problems BOOLEAN DEFAULT FALSE,
    other_problems_description TEXT DEFAULT NULL,
    
    -- Medical History - Additional Information
    major_operations_injuries TEXT DEFAULT NULL,
    regular_medication TEXT DEFAULT NULL,
    
    -- Family Physician
    family_physician_name VARCHAR(100) DEFAULT NULL,
    family_physician_phone VARCHAR(20) DEFAULT NULL,
    
    -- How did you come to know about The Laurels School
    heard_through_newspapers BOOLEAN DEFAULT FALSE,
    heard_through_advertisements BOOLEAN DEFAULT FALSE,
    heard_through_friends BOOLEAN DEFAULT FALSE,
    heard_through_relatives BOOLEAN DEFAULT FALSE,
    heard_through_other BOOLEAN DEFAULT FALSE,
    heard_through_other_description TEXT DEFAULT NULL,
    
    -- Application Details
    application_date DATE NOT NULL,
    parent_signature VARCHAR(255) DEFAULT NULL,
    
    -- Required Documents
    passport_photo VARCHAR(255) DEFAULT NULL,
    school_leaving_certificate VARCHAR(255) DEFAULT NULL,
    recent_exam_results VARCHAR(255) DEFAULT NULL,
    father_nic_copy VARCHAR(255) DEFAULT NULL,
    mother_nic_copy VARCHAR(255) DEFAULT NULL,
    guardian_nic_copy VARCHAR(255) DEFAULT NULL,
    birth_certificate VARCHAR(255) DEFAULT NULL,
    
    -- Verification
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by INT DEFAULT NULL,
    verified_at TIMESTAMP NULL,
    verification_notes TEXT DEFAULT NULL,
    
    -- System Fields
    status ENUM('pending', 'active', 'inactive', 'withdrawn', 'completed', 'suspended', 'expelled', 'transferred', 'graduated', 'on_leave') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create activity_logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_students_name ON students(first_name, middle_name, last_name);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_students_verified ON students(is_verified);
CREATE INDEX idx_students_created ON students(created_at);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_created ON activity_logs(created_at);

-- Insert default admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, password, role, status) VALUES 
('Admin', 'User', 'admin@laurelsschool.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert sample teacher user (password: teacher123)
INSERT INTO users (first_name, last_name, email, password, role, status) VALUES 
('John', 'Smith', 'teacher@laurelsschool.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active');

-- Show success message
SELECT 'Database setup completed successfully!' as message;
SELECT 'Admin login: admin@laurelsschool.com / admin123' as admin_credentials;
SELECT 'Teacher login: teacher@laurelsschool.com / teacher123' as teacher_credentials; 