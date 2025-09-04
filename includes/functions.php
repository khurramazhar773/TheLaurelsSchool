<?php
/**
 * Common Functions for The Laurels School LMS
 * 
 * This file contains utility functions used throughout the application.
 */

/**
 * Sanitize user input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is teacher
 */
function isTeacher() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher';
}

/**
 * Check if user is student
 */
function isStudent() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

/**
 * Redirect to specified URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Display success message
 */
function showSuccess($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Display error message
 */
function showError($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear flash messages
 */
function getFlashMessages() {
    $messages = [];
    
    if (isset($_SESSION['success_message'])) {
        $messages['success'] = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        $messages['error'] = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
    
    return $messages;
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Upload file
 */
function uploadFile($file, $destination) {
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $destination . '/' . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    return false;
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file type is allowed
 */
function isAllowedFileType($filename) {
    $extension = getFileExtension($filename);
    return in_array($extension, ALLOWED_EXTENSIONS);
}

/**
 * Generate pagination links
 */
function generatePagination($totalItems, $itemsPerPage, $currentPage, $baseUrl) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $pagination = '';
    
    if ($totalPages > 1) {
        $pagination .= '<div class="pagination">';
        
        // Previous button
        if ($currentPage > 1) {
            $pagination .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="page-link">Previous</a>';
        }
        
        // Page numbers
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $pagination .= '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link ' . $active . '">' . $i . '</a>';
        }
        
        // Next button
        if ($currentPage < $totalPages) {
            $pagination .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="page-link">Next</a>';
        }
        
        $pagination .= '</div>';
    }
    
    return $pagination;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = '') {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $action, $details]);
    } catch (Exception $e) {
        // Log error silently in production
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Compute grade letter from percentage using grading system:
 * 90-100 A+, 80-89 A, 70-79 B, 60-69 C, 50-59 D, 40-49 E, <40 F
 */
function getGradeFromPercentage(float $percentage): string {
    if ($percentage >= 90.0) return 'A+';
    if ($percentage >= 80.0) return 'A';
    if ($percentage >= 70.0) return 'B';
    if ($percentage >= 60.0) return 'C';
    if ($percentage >= 50.0) return 'D';
    if ($percentage >= 40.0) return 'E';
    return 'F';
}

/**
 * Helper to compute grade from obtained and max marks
 */
function getGradeFromScore(float $obtainedMarks, float $maxMarks): string {
    if ($maxMarks <= 0) return 'F';
    $percentage = ($obtainedMarks / $maxMarks) * 100.0;
    return getGradeFromPercentage($percentage);
}
?> 