<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: index.html');
    exit;
}
require_once 'config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch student info and email
$stmt = $db->prepare("
    SELECT s.*, u.email 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    // Should not happen naturally but fallback
    echo "Student record not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Student Portal</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="#dashboard" class="sidebar-link active" onclick="switchTab('dashboard')">
                    <i class='bx bxs-dashboard'></i> My Dashboard
                </a>
                <a href="#profile" class="sidebar-link" onclick="switchTab('profile')">
                    <i class='bx bx-user'></i> My Profile
                </a>
                <a href="#exams" class="sidebar-link" onclick="switchTab('exams')">
                    <i class='bx bx-task'></i> My Exams
                </a>
                <a href="materials.php" class="sidebar-link">
                    <i class='bx bx-library'></i> Course Materials
                </a>
                <a href="campus_map.php" class="sidebar-link">
                    <i class='bx bx-map-alt'></i> Campus Map
                </a>
                <a href="library_map.php" class="sidebar-link">
                    <i class='bx bx-map'></i> Library Map
                </a>
            </nav>
            <div style="padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="#" id="logout-btn" class="sidebar-link">
                    <i class='bx bx-log-out'></i> Logout
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div>
                    <h1 id="page-title">Welcome, <?php echo htmlspecialchars($student['first_name']); ?></h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Student ID: <?php echo htmlspecialchars($student['student_id_number']); ?></p>
                </div>
                <div class="user-profile">
                    <span class="badge badge-primary" style="background-color: var(--primary-color); color: white;">Student</span>
                </div>
            </header>

            <div id="tab-dashboard" class="tab-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class='bx bx-book'></i></div>
                        <div class="stat-details">
                            <h3>Course Enrolled</h3>
                            <p style="font-size: 1.25rem;"><?php echo htmlspecialchars($student['course'] ?: 'Not Assigned'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class='bx bx-check-circle'></i></div>
                        <div class="stat-details">
                            <h3>Status</h3>
                            <p style="font-size: 1.25rem; text-transform: capitalize;"><?php echo htmlspecialchars($student['status']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h2 style="font-size: 1.125rem;">Upcoming Exams</h2>
                    </div>
                    <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
                        <i class='bx bx-calendar-x' style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>No upcoming exams scheduled at this time.</p>
                    </div>
                </div>
            </div>

            <div id="tab-exams" class="tab-content hidden">
                <div class="table-container" style="padding: 2rem; text-align: center;">
                    <i class='bx bx-book-content' style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                    <h2>Available Exams</h2>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Access your assigned online examinations here.</p>
                    <a href="take_exam.php" class="btn btn-primary">Go to Exam Portal</a>
                </div>
            </div>

            <div id="tab-profile" class="tab-content hidden">
                <div class="table-container" style="padding: 2rem;">
                    <h2 style="font-size: 1.25rem; margin-bottom: 1.5rem;">Edit Profile</h2>
                    <div id="profile-msg" style="display: none; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;"></div>
                    <form id="profile-form" class="form-grid">
                        <input type="hidden" id="prof-id" value="<?php echo htmlspecialchars($student['id']); ?>">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" id="prof-first-name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" id="prof-last-name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" id="prof-email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>New Password (leave blank to keep current)</label>
                            <input type="password" id="prof-password">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="js/main.js"></script>
    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('active'));
            
            document.getElementById('tab-' + tabId).classList.remove('hidden');
            event.currentTarget.classList.add('active');
            
            const titles = {
                'dashboard': 'My Dashboard',
                'profile': 'My Profile',
                'exams': 'My Exams'
            };
            document.getElementById('page-title').innerHTML = tabId === 'dashboard' 
                ? 'Welcome, <?php echo htmlspecialchars($student['first_name']); ?>' 
                : titles[tabId];
        }

        document.getElementById('profile-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgDiv = document.getElementById('profile-msg');
            msgDiv.style.display = 'none';

            const payload = {
                id: document.getElementById('prof-id').value,
                first_name: document.getElementById('prof-first-name').value,
                last_name: document.getElementById('prof-last-name').value,
                email: document.getElementById('prof-email').value,
                password: document.getElementById('prof-password').value
            };

            try {
                const response = await fetch('api/students.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                
                msgDiv.style.display = 'block';
                msgDiv.textContent = result.message;
                msgDiv.style.backgroundColor = result.success ? '#d4edda' : '#f8d7da';
                msgDiv.style.color = result.success ? '#155724' : '#721c24';
                
                if (result.success && payload.password) {
                    document.getElementById('prof-password').value = '';
                }
                
                setTimeout(() => msgDiv.style.display = 'none', 3000);
            } catch (error) {
                console.error('Update error', error);
                alert('An error occurred updating the profile.');
            }
        });
    </script>
</body>
</html>
