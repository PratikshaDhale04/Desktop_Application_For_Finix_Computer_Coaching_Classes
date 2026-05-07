-- Finix Computers Database Schema
-- Database: finix_computers

CREATE DATABASE IF NOT EXISTS finix_computers;
USE finix_computers;

-- Admin Login Table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses Table
CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(150) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    fee DECIMAL(10,2) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students Table
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    student_uid VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    birthdate DATE,
    admission_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Apply Course Table
CREATE TABLE IF NOT EXISTS apply_course (
    apply_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    installment INT DEFAULT 1,
    course_fee DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    final_fees DECIMAL(10,2) NOT NULL,
    course_start_date DATE,
    paid_fee DECIMAL(10,2) DEFAULT 0,
    remaining_fee DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    apply_id INT NOT NULL,
    installment_no INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_mode ENUM('cash', 'upi', 'card', 'bank_transfer') NOT NULL,
    receipt_no VARCHAR(50),
    transaction_id VARCHAR(100),
    notes TEXT,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (apply_id) REFERENCES apply_course(apply_id) ON DELETE CASCADE
);

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admins (username, password, full_name, email, phone) VALUES 
('admin', 'admin123', 'System Administrator', 'admin@finix.com', '9876543210');

-- Insert sample courses
INSERT INTO courses (course_name, duration, fee, description) VALUES 
('Python Programming', '3 Months', 15000, 'Learn Python from scratch'),
('Java Development', '6 Months', 25000, 'Complete Java training'),
('Web Development', '4 Months', 20000, 'Full stack web development'),
('Data Science', '6 Months', 30000, 'Machine Learning and AI'),
('Digital Marketing', '3 Months', 12000, 'SEO and Social Media');