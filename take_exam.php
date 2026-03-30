<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .exam-container { max-width: 900px; margin: 2rem auto; }
        .timer-bar { position: sticky; top: 0; background: var(--primary-color); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center; font-size: 1.25rem; font-weight: bold; box-shadow: var(--shadow-md); z-index: 100; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .q-block { background: white; padding: 2rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; }
        .q-text { font-size: 1.125rem; font-weight: 500; margin-bottom: 1rem; }
        .q-options label { display: block; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 0.5rem; cursor: pointer; transition: background 0.2s; }
        .q-options label:hover { background: #F7FAFC; }
        .q-options input[type="radio"] { margin-right: 0.5rem; }
    </style>
</head>
<body style="background: #F7FAFC;">
    <div style="background: white; padding: 1rem 2rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center;">
        <h1 style="font-size: 1.25rem; color: var(--primary-color);">🎓 Online Examination Center</h1>
        <a href="student_dashboard.php" class="btn btn-outline" id="back-btn">Exit Portal</a>
    </div>

    <div class="exam-container">
        <!-- Exam List View -->
        <div id="exam-list-view">
            <h2 style="margin-bottom: 1.5rem;">Available Exams</h2>
            <div id="exams-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                <!-- Filled via JS -->
            </div>
        </div>

        <!-- Taking Exam View -->
        <div id="exam-taking-view" class="hidden">
            <div class="timer-bar">
                <span id="active-exam-title">Exam Title</span>
                <span id="timer-display"><i class='bx bx-time'></i> 00:00:00</span>
            </div>
            
            <form id="exam-form">
                <div id="questions-list"></div>
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="font-size: 1.125rem; padding: 1rem 3rem;" id="submit-exam-btn">Submit Exam</button>
                </div>
            </form>
        </div>
        
        <!-- Results View -->
        <div id="exam-results-view" class="hidden" style="text-align: center; background: white; padding: 4rem 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
            <div style="font-size: 4rem; color: var(--success-color); margin-bottom: 1rem;"><i class='bx bxs-check-circle'></i></div>
            <h2 style="font-size: 2rem; margin-bottom: 1rem;">Exam Submitted!</h2>
            <p style="font-size: 1.25rem; color: var(--text-muted); margin-bottom: 2rem;">Your score has been successfully recorded.</p>
            <div style="background: #F7FAFC; padding: 1.5rem; border-radius: var(--radius-md); display: inline-block; min-width: 300px; margin-bottom: 2rem;">
                <p style="font-size: 1rem; color: var(--text-muted); text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Final Score</p>
                <p style="font-size: 3rem; color: var(--primary-color); font-weight: bold;" id="result-score">0 / 0</p>
            </div>
            <br>
            <a href="student_dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
    </div>

    <script>
        let currentExamId = null;
        let timerInterval = null;

        // Load available exams
        async function loadExams() {
            try {
                const res = await fetch('api/exams.php?action=list');
                const data = await res.json();
                
                if (data.success) {
                    const grid = document.getElementById('exams-grid');
                    grid.innerHTML = '';
                    
                    if (data.data.length === 0) {
                        grid.innerHTML = '<p class="text-muted">No exams available.</p>';
                        return;
                    }
                    
                    data.data.forEach(exam => {
                        let actionBtn = `<button class="btn btn-primary" style="width: 100%;" onclick="startExam(${exam.id})">Start Exam (${exam.duration_minutes}m)</button>`;
                        
                        if (exam.taken) {
                            actionBtn = `<div style="text-align: center; color: var(--success-color); font-weight: bold;"><i class='bx bx-check'></i> COMPLETED: ${exam.result.score} / ${exam.result.total_marks}</div>`;
                        }
                        
                        const card = `
                            <div style="background: white; padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border-top: 4px solid var(--primary-color);">
                                <h3 style="margin-bottom: 0.5rem;">${exam.title}</h3>
                                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem; line-height: 1.5;">${exam.description || 'No description provided.'}</p>
                                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
                                    <span><i class='bx bx-time'></i> ${exam.duration_minutes} mins</span>
                                </div>
                                ${actionBtn}
                            </div>
                        `;
                        grid.insertAdjacentHTML('beforeend', card);
                    });
                }
            } catch (err) { console.error('Error loading exams', err); }
        }

        async function startExam(id) {
            if(!confirm("Are you ready to begin? The timer will start immediately.")) return;
            
            try {
                const res = await fetch(`api/exams.php?action=get&id=${id}`);
                const data = await res.json();
                
                if (data.success) {
                    currentExamId = id;
                    document.getElementById('exam-list-view').classList.add('hidden');
                    document.getElementById('back-btn').classList.add('hidden');
                    document.getElementById('exam-taking-view').classList.remove('hidden');
                    
                    const exam = data.data;
                    document.getElementById('active-exam-title').textContent = exam.title;
                    
                    // Render questions
                    const qList = document.getElementById('questions-list');
                    qList.innerHTML = '';
                    
                    // Shuffle array function for randomization
                    const shuffle = (array) => { 
                        for (let i = array.length - 1; i > 0; i--) { 
                            const j = Math.floor(Math.random() * (i + 1)); 
                            [array[i], array[j]] = [array[j], array[i]]; 
                        } 
                        return array; 
                    };
                    
                    let questions = shuffle(exam.questions);
                    
                    questions.forEach((q, idx) => {
                        let html = `
                            <div class="q-block">
                                <div class="q-text">${idx + 1}. ${q.question_text} <span style="font-size: 0.875rem; color: var(--text-muted); float: right;">(${q.marks} marks)</span></div>
                                <div class="q-options">
                        `;
                        
                        if (q.question_type === 'multiple_choice') {
                            let options = shuffle([...q.options]); // Shuffle options
                            options.forEach(opt => {
                                html += `<label><input type="radio" name="q_${q.id}" value="${opt}" required> ${opt}</label>`;
                            });
                        } else if (q.question_type === 'true_false') {
                            html += `
                                <label><input type="radio" name="q_${q.id}" value="true" required> True</label>
                                <label><input type="radio" name="q_${q.id}" value="false" required> False</label>
                            `;
                        } else {
                            html += `<input type="text" name="q_${q.id}" class="form-control" placeholder="Type your answer here..." required>`;
                        }
                        
                        html += `</div></div>`;
                        qList.insertAdjacentHTML('beforeend', html);
                    });
                    
                    // Start timer
                    startTimer(exam.duration_minutes * 60);
                }
            } catch (err) { alert('Failed to start exam.'); }
        }

        function startTimer(seconds) {
            let remaining = seconds;
            const display = document.getElementById('timer-display');
            
            timerInterval = setInterval(() => {
                remaining--;
                
                const h = Math.floor(remaining / 3600);
                const m = Math.floor((remaining % 3600) / 60);
                const s = remaining % 60;
                
                display.innerHTML = `<i class='bx bx-time'></i> ${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                
                if (remaining <= 300) { // Last 5 mins
                    document.querySelector('.timer-bar').style.backgroundColor = 'var(--danger-color)';
                }
                
                if (remaining <= 0) {
                    clearInterval(timerInterval);
                    alert("Time is up! Submitting your exam automatically.");
                    submitExam();
                }
            }, 1000);
        }

        async function submitExam() {
            clearInterval(timerInterval);
            const btn = document.getElementById('submit-exam-btn');
            btn.disabled = true;
            btn.textContent = "Submitting...";
            
            const form = document.getElementById('exam-form');
            const elements = form.elements;
            let answers = [];
            
            // Gather answers manually to ensure correct format
            const radioProcessed = new Set();
            for (let i = 0; i < elements.length; i++) {
                const el = elements[i];
                if (el.name && el.name.startsWith('q_')) {
                    const qId = el.name.split('_')[1];
                    
                    if (el.type === 'radio') {
                        if (!radioProcessed.has(el.name)) {
                            const selected = form.querySelector(`input[name="${el.name}"]:checked`);
                            if (selected) {
                                answers.push({ question_id: parseInt(qId), answer: selected.value });
                            }
                            radioProcessed.add(el.name);
                        }
                    } else if (el.type === 'text') {
                        answers.push({ question_id: parseInt(qId), answer: el.value });
                    }
                }
            }
            
            try {
                const res = await fetch('api/exams.php?action=submit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        exam_id: currentExamId,
                        answers: answers
                    })
                });
                
                const data = await res.json();
                if (data.success) {
                    document.getElementById('exam-taking-view').classList.add('hidden');
                    document.getElementById('exam-results-view').classList.remove('hidden');
                    document.getElementById('result-score').textContent = `${data.data.score} / ${data.data.total}`;
                } else {
                    alert(data.message || "Failed to submit exam!");
                    btn.disabled = false;
                    btn.textContent = "Submit Exam";
                }
            } catch (err) {
                alert("Network error occurred.");
                btn.disabled = false;
                btn.textContent = "Submit Exam";
            }
        }

        document.getElementById('exam-form').addEventListener('submit', (e) => {
            e.preventDefault();
            if(confirm("Are you sure you want to submit your exam now?")) {
                submitExam();
            }
        });

        document.addEventListener('DOMContentLoaded', loadExams);
    </script>
</body>
</html>
