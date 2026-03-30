<?php
session_start();

// Utility for sending JSON responses
function respond_json($success, $message, $data = null, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Security function to clean input
function clean_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = clean_input($value);
        }
        return $data;
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Function to require specific role
function require_role($role) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        respond_json(false, "Unauthorized access. Please login.", null, 401);
    }
    
    if (is_array($role)) {
        if (!in_array($_SESSION['role'], $role)) {
            respond_json(false, "Forbidden. Insufficient permissions.", null, 403);
        }
    } else {
        if ($_SESSION['role'] !== $role) {
            respond_json(false, "Forbidden. Insufficient permissions.", null, 403);
        }
    }
}

// Check auth without dying
function is_authenticated() {
    return isset($_SESSION['user_id']);
}
?>
