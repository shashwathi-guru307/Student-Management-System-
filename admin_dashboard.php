<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Include BoxIcons for icons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Portal</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="#dashboard" class="sidebar-link active" onclick="switchTab('dashboard')">
                    <i class='bx bxs-dashboard'></i> Dashboard Overview
                </a>
                <a href="#students" class="sidebar-link" onclick="switchTab('students')">
                    <i class='bx bxs-group'></i> All Students
                </a>
                <a href="#exams" class="sidebar-link" onclick="switchTab('exams')">
                    <i class='bx bx-book-content'></i> Exam Management
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div>
                    <h1 id="page-title">Dashboard Overview</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
                <div class="user-profile">
                    <span class="badge badge-success">Admin</span>
                    <button class="btn btn-primary" onclick="openAddStudentModal()">+ Add New Student</button>
                </div>
            </header>

            <!-- Dashboard Tab -->
            <div id="tab-dashboard" class="tab-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class='bx bxs-graduation'></i></div>
                        <div class="stat-details">
                            <h3>Total Enrolled</h3>
                            <p id="stat-total">0</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class='bx bx-check-shield'></i></div>
                        <div class="stat-details">
                            <h3>Active Students</h3>
                            <p id="stat-active">0</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class='bx bxs-award'></i></div>
                        <div class="stat-details">
                            <h3>Graduated Alumni</h3>
                            <p id="stat-graduated">0</p>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h2 style="font-size: 1.125rem;">Recent Students</h2>
                        <a href="#students" onclick="switchTab('students')" style="color: var(--primary-color); text-decoration: none; font-size: 0.875rem; font-weight: 500;">View All</a>
                    </div>
                    <table id="recent-students-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="recent-students-body">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Students Tab -->
            <div id="tab-students" class="tab-content hidden">
                <div class="table-container">
                    <div class="table-header">
                        <h2 style="font-size: 1.125rem;">Student Records</h2>
                        <div style="display: flex; gap: 1rem;">
                            <input type="text" id="search-student" class="form-control" placeholder="Search students..." style="width: 250px;">
                        </div>
                    </div>
                    <table id="all-students-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="all-students-body">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Exams Tab -->
            <div id="tab-exams" class="tab-content hidden">
                <div class="table-container">
                    <div class="table-header">
                        <h2 style="font-size: 1.125rem;">Exam Management</h2>
                        <a href="create_exam.php" class="btn btn-primary">+ Create New Exam</a>
                    </div>
                    <div id="exam-alert" class="alert mt-4" style="display: none;"></div>
                    <table id="all-exams-table">
                        <thead>
                            <tr>
                                <th>Exam ID</th>
                                <th>Title</th>
                                <th>Duration</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="all-exams-body">
                            <tr><td colspan="5" class="text-center">Loading exams...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="add-student-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Add New Student</h2>
                <button class="close-btn" onclick="closeModal('add-student-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="modal-alert" class="alert"></div>
                <form id="add-student-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" id="add-fname" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" id="add-lname" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="add-username" class="form-control" required minlength="4">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="add-email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="add-password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Course Name</label>
                        <select id="add-course" class="form-control" required>
                            <option value="" disabled selected>Select a Course</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Business Administration">Business Administration</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Mathematics">Mathematics</option>
                        </select>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('add-student-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="add-student-btn">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="edit-student-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Edit Student</h2>
                <button class="close-btn" onclick="closeModal('edit-student-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="edit-modal-alert" class="alert"></div>
                <form id="edit-student-form">
                    <input type="hidden" id="edit-id">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" id="edit-fname" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" id="edit-lname" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="edit-email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password (leave blank to keep current)</label>
                        <input type="password" id="edit-password" class="form-control">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Course Name</label>
                            <select id="edit-course" class="form-control" required>
                                <option value="" disabled selected>Select a Course</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Mathematics">Mathematics</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select id="edit-status" class="form-control">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="graduated">Graduated</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('edit-student-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="edit-student-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Tab switching logic
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('active'));
            
            document.getElementById('tab-' + tabId).classList.remove('hidden');
            event.currentTarget.classList.add('active');
            
            const titles = {
                'dashboard': 'Dashboard Overview',
                'students': 'All Students',
                'exams': 'Exam Management'
            };
            document.getElementById('page-title').textContent = titles[tabId];
            
            if (tabId === 'dashboard') loadStats();
            if (tabId === 'students' || tabId === 'dashboard') loadStudents();
            if (tabId === 'exams') loadExams();
        }

        let globalStudentsList = [];

        // Modal logic
        function openAddStudentModal() {
            document.getElementById('add-student-modal').classList.add('active');
        }

        function openEditStudentModal(id) {
            const student = globalStudentsList.find(s => s.id == id);
            if (!student) return;
            
            document.getElementById('edit-id').value = student.id;
            document.getElementById('edit-fname').value = student.first_name;
            document.getElementById('edit-lname').value = student.last_name;
            document.getElementById('edit-email').value = student.email;
            document.getElementById('edit-course').value = student.course || '';
            document.getElementById('edit-status').value = student.status;
            document.getElementById('edit-password').value = '';
            
            document.getElementById('edit-student-modal').classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            if(id === 'add-student-modal') {
                document.getElementById('add-student-form').reset();
            } else if (id === 'edit-student-modal') {
                document.getElementById('edit-student-form').reset();
            }
        }

        // Fetch Stats
        async function loadStats() {
            try {
                const res = await fetch('api/students.php?action=stats');
                const data = await res.json();
                if (data.success) {
                    document.getElementById('stat-total').textContent = data.data.total_enrolled;
                    document.getElementById('stat-active').textContent = data.data.active_students;
                    document.getElementById('stat-graduated').textContent = data.data.graduated;
                }
            } catch (err) { console.error('Failed to load stats', err); }
        }

        // Fetch Students
        async function loadStudents() {
            try {
                const res = await fetch('api/students.php?action=list');
                const data = await res.json();
                if (data.success) {
                    globalStudentsList = data.data;
                    renderStudents(data.data);
                }
            } catch (err) { console.error('Failed to load students', err); }
        }

        function getStatusBadgeClass(status) {
            if (status === 'active') return 'badge badge-success';
            if (status === 'suspended') return 'badge badge-error';
            return 'badge badge-warning';
        }

        function renderStudents(students) {
            const allBody = document.getElementById('all-students-body');
            const recentBody = document.getElementById('recent-students-body');
            
            allBody.innerHTML = '';
            recentBody.innerHTML = '';
            
            if (students.length === 0) {
                const emptyRow = `<tr><td colspan="6" class="text-center" style="padding: 2rem; color: var(--text-muted);">No student records found. Add your first student!</td></tr>`;
                allBody.innerHTML = emptyRow;
                recentBody.innerHTML = emptyRow;
                return;
            }

            students.forEach((student, index) => {
                const fullName = `${student.first_name} ${student.last_name}`;
                const statusBadge = `<span class="${getStatusBadgeClass(student.status)}">${student.status.toUpperCase()}</span>`;
                
                // Add to All Students
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${student.student_id_number}</strong></td>
                    <td>${fullName}</td>
                    <td>${student.email}</td>
                    <td>${student.course || 'N/A'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="openEditStudentModal(${student.id})">Edit</button>
                        <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: var(--danger-color); border-color: var(--danger-color);" onclick="deleteStudent(${student.id})">Delete</button>
                    </td>
                `;
                allBody.appendChild(tr);

                // Add up to 5 to Recent Students
                if (index < 5) {
                    const rtr = document.createElement('tr');
                    rtr.innerHTML = `
                        <td><strong>${student.student_id_number}</strong></td>
                        <td>${fullName}</td>
                        <td>${student.course || 'N/A'}</td>
                        <td>${statusBadge}</td>
                    `;
                    recentBody.appendChild(rtr);
                }
            });
        }

        async function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student and all their records?')) {
                try {
                    const res = await fetch('api/students.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert('Student deleted successfully');
                        loadStudents();
                        loadStats();
                    } else {
                        alert(data.message || 'Failed to delete');
                    }
                } catch (err) { console.error('Error deleting student:', err); }
            }
        }

        // Fetch Exams
        async function loadExams() {
            try {
                const res = await fetch('api/exams.php?action=list');
                const data = await res.json();
                
                const tbody = document.getElementById('all-exams-body');
                tbody.innerHTML = '';
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(ex => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><strong>#${ex.id}</strong></td>
                            <td>${ex.title}</td>
                            <td>${ex.duration_minutes} mins</td>
                            <td>${new Date(ex.created_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; border-color: var(--primary-color); color: var(--primary-color);" onclick="allocateExam(${ex.id})">Allocate to All Students</button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted" style="padding: 2rem;">No exams found.</td></tr>`;
                }
            } catch (err) { console.error('Failed to load exams', err); }
        }

        async function allocateExam(examId) {
            if (!confirm('Allocate this exam to all active students?')) return;
            
            try {
                const res = await fetch('api/exams.php?action=allocate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ exam_id: examId })
                });
                const data = await res.json();
                const alertDiv = document.getElementById('exam-alert');
                
                if (data.success) {
                    showAlert('exam-alert', data.message, 'success');
                } else {
                    showAlert('exam-alert', data.message || 'Failed to allocate', 'error');
                }
            } catch (err) {
                showAlert('exam-alert', 'Network error occurred', 'error');
            }
        }

        // Add Student Form Submission
        document.getElementById('add-student-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('add-student-btn');
            
            const payload = {
                first_name: document.getElementById('add-fname').value,
                last_name: document.getElementById('add-lname').value,
                username: document.getElementById('add-username').value,
                email: document.getElementById('add-email').value,
                password: document.getElementById('add-password').value,
                course: document.getElementById('add-course').value
            };

            try {
                btn.disabled = true;
                btn.textContent = 'Saving...';
                
                const res = await fetch('api/students.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (data.success) {
                    showAlert('modal-alert', `Successfully added! ID: ${data.data.student_id_number}`, 'success');
                    setTimeout(() => {
                        closeModal('add-student-modal');
                        loadStudents();
                        loadStats();
                    }, 1500);
                } else {
                    showAlert('modal-alert', data.message || 'Failed to add student', 'error');
                }
            } catch (err) {
                showAlert('modal-alert', 'Network error occurred', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Save Student';
            }
        });

        // Edit Student Form Submission
        document.getElementById('edit-student-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('edit-student-btn');
            
            const payload = {
                id: document.getElementById('edit-id').value,
                first_name: document.getElementById('edit-fname').value,
                last_name: document.getElementById('edit-lname').value,
                email: document.getElementById('edit-email').value,
                password: document.getElementById('edit-password').value,
                course: document.getElementById('edit-course').value,
                status: document.getElementById('edit-status').value
            };

            try {
                btn.disabled = true;
                btn.textContent = 'Saving...';
                
                const res = await fetch('api/students.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (data.success) {
                    showAlert('edit-modal-alert', `Profile updated successfully`, 'success');
                    setTimeout(() => {
                        closeModal('edit-student-modal');
                        loadStudents();
                        loadStats();
                    }, 1500);
                } else {
                    showAlert('edit-modal-alert', data.message || 'Failed to update student', 'error');
                }
            } catch (err) {
                showAlert('edit-modal-alert', 'Network error occurred', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            }
        });

        // Initialize dashboard data on load
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadStudents();
            
            // Search functionality
            const searchInput = document.getElementById('search-student');
            if(searchInput) {
                searchInput.addEventListener('keyup', (e) => {
                    const term = e.target.value.toLowerCase();
                    const rows = document.getElementById('all-students-body').getElementsByTagName('tr');
                    for(let row of rows) {
                        if(row.cells.length > 1) { // Skip empty placeholder row
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(term) ? '' : 'none';
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
