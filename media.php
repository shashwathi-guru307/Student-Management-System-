<?php
require_once '../config/Database.php';
require_once '../includes/utils.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!is_authenticated()) {
    respond_json(false, "Authentication required", null, 401);
}

// Ensure uploads directory exists
$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($method === 'GET' && $action === 'list') {
    // List media files
    $stmt = $db->query("
        SELECT m.id, m.title, m.file_path, m.file_type, m.file_size, m.uploaded_at, u.username as uploader_name 
        FROM media m 
        JOIN users u ON m.uploader_id = u.id 
        ORDER BY m.id DESC
    ");
    $media = $stmt->fetchAll();
    respond_json(true, "Media loaded", $media);
} 
elseif ($method === 'POST' && $action === 'upload') {
    // Upload a file
    require_role(['admin', 'teacher']);
    
    if (!isset($_FILES['file'])) {
        respond_json(false, "No file uploaded");
    }
    
    $file = $_FILES['file'];
    $title = isset($_POST['title']) ? clean_input($_POST['title']) : basename($file['name']);
    
    // Validate file type
    $allowed_types = [
        'image/jpeg', 'image/png', 'image/gif', 
        'application/pdf', 
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'video/mp4', 'video/webm'
    ];
    
    if (!in_array($file['type'], $allowed_types)) {
        respond_json(false, "Invalid file type. Only images, PDFs, Word docs, and standard web videos (mp4/webm) are allowed.");
    }
    
    // Sanitize filename and create unique path
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('media_') . '.' . $ext;
    $destination = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $file_path = 'uploads/' . $new_filename;
        
        $stmt = $db->prepare("INSERT INTO media (uploader_id, title, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $title, $file_path, $file['type'], $file['size']])) {
            respond_json(true, "File uploaded successfully", [
                "path" => $file_path
            ]);
        } else {
            // Cleanup on db failure
            unlink($destination);
            respond_json(false, "Database error");
        }
    } else {
        respond_json(false, "Failed to move uploaded file");
    }
}
elseif ($method === 'DELETE') {
    require_role(['admin', 'teacher']);
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->id)) {
        respond_json(false, "Media ID required");
    }
    
    $stmt = $db->prepare("SELECT file_path FROM media WHERE id = ?");
    $stmt->execute([$data->id]);
    $media = $stmt->fetch();
    
    if ($media) {
        $path = '../' . $media['file_path'];
        if (file_exists($path)) {
            unlink($path);
        }
        
        $del = $db->prepare("DELETE FROM media WHERE id = ?");
        $del->execute([$data->id]);
        
        respond_json(true, "File deleted");
    }
    
    respond_json(false, "File not found");
}

respond_json(false, "Invalid endpoint");
?>
