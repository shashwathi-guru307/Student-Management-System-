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

if ($method === 'GET') {
    if ($action === 'list') {
        if ($_SESSION['role'] === 'student') {
            $stmt_student = $db->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt_student->execute([$_SESSION['user_id']]);
            $student_id = $stmt_student->fetch()['id'];
            
            $stmt = $db->prepare("
                SELECT e.*, se.status, se.score, se.total_marks 
                FROM exams e 
                JOIN student_exams se ON e.id = se.exam_id 
                WHERE se.student_id = ?
                ORDER BY e.id DESC
            ");
            $stmt->execute([$student_id]);
            $exams = $stmt->fetchAll();
            
            foreach ($exams as &$exam) {
                if ($exam['status'] === 'completed') {
                    $exam['taken'] = true;
                    $exam['result'] = [
                        'score' => $exam['score'],
                        'total_marks' => $exam['total_marks']
                    ];
                } else {
                    $exam['taken'] = false;
                }
            }
            respond_json(true, "Exams retrieved", $exams);
        } else {
            // Teachers/Admins see all exams
            $stmt = $db->query("SELECT * FROM exams ORDER BY id DESC");
            $exams = $stmt->fetchAll();
            respond_json(true, "Exams retrieved", $exams);
        }
    }
    elseif ($action === 'get' && isset($_GET['id'])) {
        $exam_id = (int)$_GET['id'];
        
        // Fetch Exam
        $stmt = $db->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$exam_id]);
        $exam = $stmt->fetch();
        
        if (!$exam) {
            respond_json(false, "Exam not found", null, 404);
        }
        
        // Fetch Questions
        $stmt_q = $db->prepare("SELECT id, question_text, question_type, options, marks FROM questions WHERE exam_id = ?");
        $stmt_q->execute([$exam_id]);
        $questions = $stmt_q->fetchAll();
        
        // Parse options JSON
        foreach ($questions as &$q) {
            if ($q['options']) {
                $q['options'] = json_decode($q['options']);
            }
        }
        
        $exam['questions'] = $questions;
        respond_json(true, "Exam content loaded", $exam);
    }
}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if ($action === 'create') {
        require_role(['admin', 'teacher']);
        
        if (empty($data->title) || empty($data->duration) || empty($data->questions)) {
            respond_json(false, "Title, duration, and questions are required.");
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO exams (title, description, duration_minutes, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->title, $data->description ?? '', $data->duration, $_SESSION['user_id']]);
            $exam_id = $db->lastInsertId();
            
            // Insert Questions
            $q_stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, question_type, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($data->questions as $q) {
                $options_json = isset($q->options) ? json_encode($q->options) : null;
                $marks = isset($q->marks) ? $q->marks : 1;
                $q_stmt->execute([
                    $exam_id, 
                    $q->question_text, 
                    $q->question_type, 
                    $options_json, 
                    $q->correct_answer, 
                    $marks
                ]);
            }
            
            $db->commit();
            respond_json(true, "Exam created successfully");
        } catch (Exception $e) {
            $db->rollBack();
            respond_json(false, "Failed to create exam: " . $e->getMessage());
        }
    }
    elseif ($action === 'allocate') {
        require_role(['admin', 'teacher']);
        
        if (empty($data->exam_id)) {
            respond_json(false, "Exam ID required.");
        }
        
        $exam_id = (int)$data->exam_id;
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->query("SELECT id FROM students WHERE status = 'active'");
            $students = $stmt->fetchAll();
            
            $ins_stmt = $db->prepare("INSERT INTO student_exams (student_id, exam_id, status) VALUES (?, ?, 'pending')");
            $check_stmt = $db->prepare("SELECT id FROM student_exams WHERE student_id = ? AND exam_id = ?");
            
            $allocated_count = 0;
            foreach ($students as $stu) {
                $check_stmt->execute([$stu['id'], $exam_id]);
                if ($check_stmt->rowCount() == 0) {
                    $ins_stmt->execute([$stu['id'], $exam_id]);
                    $allocated_count++;
                }
            }
            
            $db->commit();
            respond_json(true, "Exam allocated to $allocated_count students.");
        } catch (Exception $e) {
            $db->rollBack();
            respond_json(false, "Failed to allocate exam: " . $e->getMessage());
        }
    }
    elseif ($action === 'submit') {
        require_role('student');
        
        if (empty($data->exam_id) || empty($data->answers)) {
            respond_json(false, "Invalid submission");
        }
        
        $exam_id = (int)$data->exam_id;
        
        // Fetch student_id
        $stmt_student = $db->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt_student->execute([$_SESSION['user_id']]);
        $student_info = $stmt_student->fetch();
        
        if (!$student_info) {
            respond_json(false, "Student profile not found");
        }
        
        $student_id = $student_info['id'];
        
        // Check if already taken
        $check_stmt = $db->prepare("SELECT id FROM student_exams WHERE student_id = ? AND exam_id = ?");
        $check_stmt->execute([$student_id, $exam_id]);
        if ($check_stmt->rowCount() > 0) {
            respond_json(false, "You have already taken this exam.");
        }
        
        // Fetch questions and correct answers for grading
        $stmt_q = $db->prepare("SELECT id, correct_answer, marks FROM questions WHERE exam_id = ?");
        $stmt_q->execute([$exam_id]);
        $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);
        
        $score = 0;
        $total_marks = 0;
        
        // Evaluate answers
        $q_map = [];
        foreach ($questions as $q) {
            $q_map[$q['id']] = $q;
            $total_marks += $q['marks'];
        }
        
        foreach ($data->answers as $ans) {
            $q_id = $ans->question_id;
            if (isset($q_map[$q_id])) {
                $correct = strtolower(trim($q_map[$q_id]['correct_answer']));
                $provided = strtolower(trim($ans->answer));
                
                if ($correct === $provided) {
                    $score += $q_map[$q_id]['marks'];
                }
            }
        }
        
        // Record result
        $stmt_res = $db->prepare("INSERT INTO student_exams (student_id, exam_id, score, total_marks, start_time, end_time, status) VALUES (?, ?, ?, ?, NOW(), NOW(), 'completed')");
        if ($stmt_res->execute([$student_id, $exam_id, $score, $total_marks])) {
            respond_json(true, "Exam submitted successfully", [
                "score" => $score,
                "total" => $total_marks
            ]);
        } else {
            respond_json(false, "Failed to save exam results");
        }
    }
}

respond_json(false, "Invalid endpoint");
?>
