<?php
require_once '../config/Database.php';
require_once '../includes/utils.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data && !empty($_POST)) {
        // Fallback for form-data
        $data = (object)$_POST;
    }

    if ($action === 'register') {
        if (empty($data->username) || empty($data->email) || empty($data->password)) {
            respond_json(false, "Please fill in all required fields.", null, 400);
        }

        $username = clean_input($data->username);
        $email = clean_input($data->email);
        $password = trim($data->password);

        // Check if username or email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            respond_json(false, "Username or email already exists.", null, 409);
        }

        // Insert new user
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $role = 'student'; // Default role

        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $password_hash, $role])) {
            // Auto create student record
            $user_id = $db->lastInsertId();
            $student_id_num = 'STU' . date('Y') . sprintf('%04d', $user_id);
            $stmt_student = $db->prepare("INSERT INTO students (user_id, student_id_number, first_name, last_name, enrollment_date) VALUES (?, ?, ?, ?, CURDATE())");
            $stmt_student->execute([$user_id, $student_id_num, $username, 'Student']);

            respond_json(true, "Registration successful! You can now login.", ["user_id" => $user_id], 201);
        } else {
            respond_json(false, "Registration failed.", null, 500);
        }
    } 
    elseif ($action === 'login') {
        if (empty($data->username) || empty($data->password)) {
            respond_json(false, "Please provide username and password.", null, 400);
        }

        $username = clean_input($data->username);
        $password = trim($data->password);

        $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                respond_json(true, "Login successful", ["role" => $user['role']]);
            } else {
                respond_json(false, "Invalid credentials.", null, 401);
            }
        } else {
            respond_json(false, "Invalid credentials.", null, 401);
        }
    }
} 
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'logout') {
        session_destroy();
        respond_json(true, "Logged out successfully.");
    }
    
    if ($action === 'check') {
        if (is_authenticated()) {
            respond_json(true, "Authenticated", ["role" => $_SESSION['role'], "username" => $_SESSION['username']]);
        } else {
            respond_json(false, "Not authenticated", null, 401);
        }
    }
}

respond_json(false, "Invalid action or request method.", null, 400);
?>
