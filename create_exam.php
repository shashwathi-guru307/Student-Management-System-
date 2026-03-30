<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam Builder</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .exam-builder { max-width: 800px; margin: 0 auto; padding: 2rem 0; }
        .question-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow-md); margin-bottom: 1.5rem; border: 1px solid var(--border-color); }
        .question-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem; }
        .remove-btn { color: var(--danger-color); cursor: pointer; border: none; background: none; font-weight: bold; }
        .options-container { margin-top: 1rem; padding-left: 1rem; border-left: 3px solid var(--primary-color); }
        .option-item { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center; }
    </style>
</head>
<body>
    <div style="background: var(--card-bg); padding: 1rem 2rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
        <h1 style="font-size: 1.25rem;">📝 Exam Builder</h1>
        <a href="admin_dashboard.php" class="btn btn-outline">Back to Dashboard</a>
    </div>

    <div class="exam-builder">
        <div id="alert-box" class="alert hidden"></div>
        
        <div class="question-card" style="border-top: 4px solid var(--primary-color);">
            <h2 style="margin-bottom: 1rem;">Exam Settings</h2>
            <div class="form-group">
                <label>Exam Title</label>
                <input type="text" id="exam-title" class="form-control" required placeholder="e.g. Midterm Physics Exam">
            </div>
            <div class="form-group">
                <label>Description / Instructions</label>
                <textarea id="exam-desc" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Time Limit (Minutes)</label>
                <input type="number" id="exam-duration" class="form-control" value="60" min="5" required>
            </div>
        </div>

        <div id="questions-container">
            <!-- Questions dynamically added here -->
        </div>

        <div style="text-align: center; margin: 2rem 0;">
            <button class="btn btn-outline" onclick="addQuestion()" style="width: auto; background: white;"><i class='bx bx-plus'></i> Add Question</button>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; padding: 2rem; background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
            <button id="save-exam-btn" class="btn btn-primary" onclick="submitExam()" style="width: auto; padding: 0.75rem 3rem;">Publish Exam</button>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        let questionCount = 0;

        function addQuestion() {
            questionCount++;
            const qId = `q-${questionCount}`;
            
            const html = `
                <div class="question-card" id="${qId}">
                    <div class="question-header">
                        <h3>Question ${questionCount}</h3>
                        <button class="remove-btn" onclick="removeQuestion('${qId}')"><i class='bx bx-trash'></i> Remove</button>
                    </div>
                    
                    <div class="form-group">
                        <label>Question Text</label>
                        <textarea class="form-control q-text" rows="2" required></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 2;">
                            <label>Question Type</label>
                            <select class="form-control q-type" onchange="toggleQuestionType('${qId}', this.value)">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True / False</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Marks</label>
                            <input type="number" class="form-control q-marks" value="1" min="1">
                        </div>
                    </div>
                    
                    <div class="options-container" id="options-${qId}">
                        <!-- MC Options added by default -->
                        <div class="option-item"><input type="radio" name="correct-${qId}" value="0" checked> <input type="text" class="form-control q-opt" placeholder="Option A" value="A"></div>
                        <div class="option-item"><input type="radio" name="correct-${qId}" value="1"> <input type="text" class="form-control q-opt" placeholder="Option B" value="B"></div>
                        <div class="option-item"><input type="radio" name="correct-${qId}" value="2"> <input type="text" class="form-control q-opt" placeholder="Option C" value="C"></div>
                        <div class="option-item"><input type="radio" name="correct-${qId}" value="3"> <input type="text" class="form-control q-opt" placeholder="Option D" value="D"></div>
                    </div>
                    
                    <div class="form-group hidden" id="correct-text-${qId}">
                        <label>Correct Answer (Exact Match)</label>
                        <input type="text" class="form-control q-correct-text">
                    </div>
                </div>
            `;
            
            document.getElementById('questions-container').insertAdjacentHTML('beforeend', html);
        }

        function removeQuestion(qId) {
            document.getElementById(qId).remove();
        }

        function toggleQuestionType(qId, type) {
            const optContainer = document.getElementById(`options-${qId}`);
            const correctTextObj = document.getElementById(`correct-text-${qId}`);
            
            if (type === 'multiple_choice') {
                optContainer.classList.remove('hidden');
                correctTextObj.classList.add('hidden');
                optContainer.innerHTML = `
                    <div class="option-item"><input type="radio" name="correct-${qId}" value="0" checked> <input type="text" class="form-control q-opt" placeholder="Option A"></div>
                    <div class="option-item"><input type="radio" name="correct-${qId}" value="1"> <input type="text" class="form-control q-opt" placeholder="Option B"></div>
                    <div class="option-item"><input type="radio" name="correct-${qId}" value="2"> <input type="text" class="form-control q-opt" placeholder="Option C"></div>
                    <div class="option-item"><input type="radio" name="correct-${qId}" value="3"> <input type="text" class="form-control q-opt" placeholder="Option D"></div>
                `;
            } else if (type === 'true_false') {
                optContainer.classList.remove('hidden');
                correctTextObj.classList.add('hidden');
                optContainer.innerHTML = `
                    <div class="option-item"><input type="radio" name="correct-${qId}" value="true" checked> True</div>
                    <div class="option-item"><input type="radio" name="correct-${qId}" value="false"> False</div>
                `;
            } else {
                optContainer.classList.add('hidden');
                correctTextObj.classList.remove('hidden');
                optContainer.innerHTML = '';
            }
        }

        async function submitExam() {
            const title = document.getElementById('exam-title').value;
            const desc = document.getElementById('exam-desc').value;
            const duration = document.getElementById('exam-duration').value;
            
            if (!title) return alert("Exam Title is required!");
            
            const qCards = document.querySelectorAll('.question-card[id^="q-"]');
            if (qCards.length === 0) return alert("Please add at least one question!");
            
            const questions = [];
            let isValid = true;
            
            qCards.forEach(card => {
                const qId = card.id;
                const text = card.querySelector('.q-text').value;
                const type = card.querySelector('.q-type').value;
                const marks = card.querySelector('.q-marks').value;
                
                if (!text) isValid = false;
                
                let options = null;
                let correctAnswer = "";
                
                if (type === 'multiple_choice') {
                    const optInputs = card.querySelectorAll('.q-opt');
                    options = Array.from(optInputs).map(inp => inp.value);
                    const selected = card.querySelector(`input[name="correct-${qId}"]:checked`);
                    correctAnswer = selected ? options[parseInt(selected.value)] : "";
                } else if (type === 'true_false') {
                    const selected = card.querySelector(`input[name="correct-${qId}"]:checked`);
                    correctAnswer = selected ? selected.value : "";
                } else {
                    correctAnswer = card.querySelector('.q-correct-text').value;
                    if(!correctAnswer) isValid = false;
                }
                
                questions.push({
                    question_text: text,
                    question_type: type,
                    marks: parseInt(marks),
                    options: options,
                    correct_answer: correctAnswer
                });
            });
            
            if (!isValid) return alert("Please fill all question fields and correct answers!");
            
            const payload = {
                title: title,
                description: desc,
                duration: duration,
                questions: questions
            };
            
            const btn = document.getElementById('save-exam-btn');
            btn.disabled = true;
            btn.textContent = "Publishing...";
            
            try {
                const res = await fetch('api/exams.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json();
                if (data.success) {
                    alert("Exam created successfully!");
                    window.location.href = 'admin_dashboard.php';
                } else {
                    alert(data.message || "Failed to create exam");
                }
            } catch (err) {
                alert("An error occurred");
                console.error(err);
            } finally {
                btn.disabled = false;
                btn.textContent = "Publish Exam";
            }
        }

        // Add initial question
        document.addEventListener('DOMContentLoaded', () => {
            addQuestion();
        });
    </script>
</body>
</html>
