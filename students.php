<?php
require_once '../config/Database.php';
require_once '../includes/utils.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_role(['admin', 'student']);

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$current_role = $_SESSION['role'];
$current_user_id = $_SESSION['user_id'];

if ($method === 'GET') {
    if ($current_role !== 'admin' && $action !== 'profile') {
        respond_json(false, "Forbidden", null, 403);
    }
    if ($action === 'stats') {
        // Get dashboard stats
        $stats = [
            'total_enrolled' => 0,
            'active_students' => 0,
            'graduated' => 0
        ];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM students");
        $stats['total_enrolled'] = $stmt->fetch()['count'];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
        $stats['active_students'] = $stmt->fetch()['count'];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM students WHERE status = 'graduated'");
        $stats['graduated'] = $stmt->fetch()['count'];
        
        respond_json(true, "Stats loaded", $stats);
    } 
    elseif ($action === 'list') {
        // List all students
        $stmt = $db->query("
            SELECT s.*, u.username, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.id DESC
        ");
        $students = $stmt->fetchAll();
        respond_json(true, "Students loaded", $students);
    }
    elseif ($action === 'profile' && $current_role === 'student') {
        $stmt = $db->prepare("
            SELECT s.*, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.user_id = ?
        ");
        $stmt->execute([$current_user_id]);
        $profile = $stmt->fetch();
        respond_json(true, "Profile loaded", $profile);
    }
} 
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if ($action === 'update') {
        if (empty($data->id)) {
            respond_json(false, "Student ID required", null, 400);
        }
        
        if ($current_role === 'student') {
            $stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$current_user_id]);
            $own_student = $stmt->fetch();
            if (!$own_student || $own_student['id'] != $data->id) {
                respond_json(false, "Forbidden. Can only update own profile.", null, 403);
            }
        }
        
        // Handle update
        if (empty($data->first_name) || empty($data->last_name)) {
            respond_json(false, "First name and last name are required", null, 400);
        }
        
        $first_name = clean_input($data->first_name);
        $last_name = clean_input($data->last_name);
        
        $course_update = "";
        $status_update = "";
        $params = [$first_name, $last_name];
        
        if ($current_role === 'admin') {
            if (isset($data->course)) {
                $course_update = ", course = ?";
                $params[] = clean_input($data->course);
            }
            if (isset($data->status)) {
                $status_update = ", status = ?";
                $params[] = clean_input($data->status);
            }
        }
        
        $params[] = $data->id;
        
        try {
            $db->beginTransaction();
            
            $sql = "UPDATE students SET first_name = ?, last_name = ? {$course_update} {$status_update} WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            if (!empty($data->email) || !empty($data->password)) {
                $stmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
                $stmt->execute([$data->id]);
                $student_rec = $stmt->fetch();
                
                if ($student_rec) {
                    $user_id_to_update = $student_rec['user_id'];
                    $user_updates = [];
                    $user_params = [];
                    
                    if (!empty($data->email)) {
                        $user_updates[] = "email = ?";
                        $user_params[] = clean_input($data->email);
                    }
                    if (!empty($data->password)) {
                        $user_updates[] = "password_hash = ?";
                        $user_params[] = password_hash(trim($data->password), PASSWORD_BCRYPT);
                    }
                    
                    if (!empty($user_updates)) {
                        $user_params[] = $user_id_to_update;
                        $user_sql = "UPDATE users SET " . implode(", ", $user_updates) . " WHERE id = ?";
                        $ustmt = $db->prepare($user_sql);
                        $ustmt->execute($user_params);
                    }
                }
            }
            
            $db->commit();
            respond_json(true, "Profile updated successfully", ["id" => $data->id]);
        } catch (Exception $e) {
            $db->rollBack();
            respond_json(false, "Failed to update profile", null, 500);
        }
    } else {
        if ($current_role !== 'admin') {
            respond_json(false, "Forbidden", null, 403);
        }
        
        if (empty($data->username) || empty($data->email) || empty($data->password) || empty($data->first_name) || empty($data->last_name)) {
            respond_json(false, "Please fill in all required fields.", null, 400);
        }

        $username = clean_input($data->username);
        $email = clean_input($data->email);
        $password = trim($data->password);
        $first_name = clean_input($data->first_name);
        $last_name = clean_input($data->last_name);
        $course = isset($data->course) ? clean_input($data->course) : '';

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username or email already exists.");
            }

            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'student')");
            $stmt->execute([$username, $email, $password_hash]);
            $user_id = $db->lastInsertId();

            $student_id_num = 'STU' . date('Y') . sprintf('%04d', $user_id);
            
            $stmt = $db->prepare("INSERT INTO students (user_id, student_id_number, first_name, last_name, enrollment_date, course, status) VALUES (?, ?, ?, ?, CURDATE(), ?, 'active')");
            $stmt->execute([$user_id, $student_id_num, $first_name, $last_name, $course]);
            
            $db->commit();
            respond_json(true, "Student added successfully.", ["student_id_number" => $student_id_num], 201);
        } catch (Exception $e) {
            $db->rollBack();
            respond_json(false, $e->getMessage(), null, 400);
        }
    }
} 
elseif ($method === 'DELETE') {
    if ($current_role !== 'admin') {
        respond_json(false, "Forbidden", null, 403);
    }
    
    // Delete student
    $data = json_decode(file_get_contents("php://input"));
    if (empty($data->id)) {
        respond_json(false, "Student ID required");
    }
    
    // We can delete the user directly because CASCADE is enabled
    $stmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->execute([$data->id]);
    $student = $stmt->fetch();
    
    if ($student) {
        $del_stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($del_stmt->execute([$student['user_id']])) {
            respond_json(true, "Student deleted successfully");
        }
    }
    respond_json(false, "Failed to delete student", null, 500);
}

respond_json(false, "Invalid endpoint");
?>
