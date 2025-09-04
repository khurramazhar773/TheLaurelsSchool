-- The Laurels School LMS - Students Table Structure
-- This table contains all student registration and enrollment information

-- First, create the users table if it doesn't exist (for admin/teacher accounts)
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

-- Now create the students table
CREATE TABLE students (
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
    parent_signature VARCHAR(255) DEFAULT NULL, -- File path for signature image
    
    -- Required Documents
    passport_photo VARCHAR(255) DEFAULT NULL, -- File path for passport size photograph
    school_leaving_certificate VARCHAR(255) DEFAULT NULL, -- File path for school leaving certificate
    recent_exam_results VARCHAR(255) DEFAULT NULL, -- File path for recent examination results
    father_nic_copy VARCHAR(255) DEFAULT NULL, -- File path for father's NIC photocopy
    mother_nic_copy VARCHAR(255) DEFAULT NULL, -- File path for mother's NIC photocopy
    guardian_nic_copy VARCHAR(255) DEFAULT NULL, -- File path for guardian's NIC photocopy (if applicable)
    birth_certificate VARCHAR(255) DEFAULT NULL, -- File path for birth certificate
    
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

-- Create indexes for better performance
CREATE INDEX idx_students_name ON students(first_name, middle_name, last_name);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_students_verified ON students(is_verified);
CREATE INDEX idx_students_created ON students(created_at); 