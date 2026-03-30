# Modern Student Management System

A complete, production-ready Student Management System built with a three-tier architecture using PHP, MySQL, HTML5, CSS3, and JavaScript.

## Features
- **Secure Authentication**: Role-based access control (Admin, Teacher, Student) using protected sessions and bcrypt password hashing.
- **Admin Dashboard**: Comprehensive overview of student metrics, complete CRUD operations for student records.
- **Online Examination Module**: Complete exam builder for educators. Students can take timed exams with shuffled questions and automated grading.
- **Course Library**: Secure file upload system for sharing images, PDFs, Word documents, and videos among students.
- **Modern UI**: Fully responsive, clean, minimalist design utilizing vibrant teals and purples with high-contrast accessibility.

## Requirements
- PHP 7.4 or higher
- MySQL/MariaDB
- XAMPP/WAMP (for local development)

## Installation Guide

1. **Database Setup**
   - Open your MySQL administration tool (like phpMyAdmin or MySQL Workbench).
   - Create a new database named `student_management_system`.
   - Import the `database/schema.sql` script to generate all required tables and relationships.
   - The script automatically inserts a default administrator account:
     - Username: `admin`
     - Password: `admin123`

2. **Configuration**
   - Open `config/Database.php` in a text editor.
   - Verify the database credentials:
     ```php
     private $host = "localhost";
     private $db_name = "student_management_system";
     private $username = "root";  // Default for XAMPP
     private $password = "";      // Default for XAMPP
     ```
   - Update `username` and `password` if your database uses different credentials.

3. **Running the Application**
   - Ensure the application folder is placed inside your web server's document root (e.g. `C:\xampp\htdocs\miniwe` for XAMPP).
   - Start the Apache and MySQL modules.
   - Access the application in your browser: `http://localhost/miniwe/index.html`.
   - Note: Make sure the `uploads/` directory at the root has write permissions so course materials can be uploaded successfully.

## Security Features Implemented
- Prepared Statements (PDO) to completely prevent SQL Injection.
- `password_hash()` utilizing BCRYPT to safely secure user passwords.
- Real-time client-side JS and robust server-side PHP form validation.
- Secure, token-based role segregation (Admins vs Students functionality encapsulation).
- Cross-Site Scripting (XSS) prevention through HTML output sanitization.
