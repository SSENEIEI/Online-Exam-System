<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลการสอบฝึกฝน</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header id="result-header" class="result-header">
            <!-- Title and score will be loaded here by JavaScript -->
        </header>

        <main id="result-details" class="result-details">
            <!-- Answer details will be loaded here by JavaScript -->
        </main>
        <div class="portal-actions">
            <a href="student_portal.php" class="button">กลับสู่หน้าหลัก</a>
            <a href="student_practice.php" class="button-secondary">สร้างข้อสอบฝึกฝนอีกครั้ง</a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const resultDataString = sessionStorage.getItem('practiceResult');
            if (!resultDataString) {
                document.getElementById('result-header').innerHTML = '<h1>ไม่พบผลการสอบฝึกฝน</h1><p>กรุณากลับไปสร้างข้อสอบอีกครั้ง</p>';
                document.getElementById('result-details').innerHTML = '';
                return;
            }

            const result = JSON.parse(resultDataString);
            
            // Display header
            const header = document.getElementById('result-header');
            header.innerHTML = `
                <h1>${result.title ? result.title : 'ผลการสอบฝึกฝน'}</h1>
                <div class="score-circle">
                    <span class="score">${result.score}</span>
                    <span class="total">/ ${result.total}</span>
                </div>
                <p>คุณทำคะแนนได้ ${result.score} จาก ${result.total} คะแนน (เฉพาะข้อปรนัย)</p>
            `;

            // Display details
            const detailsContainer = document.getElementById('result-details');
            detailsContainer.innerHTML = '<h2>รายละเอียดคำตอบ</h2>';

            result.questions.forEach((q, index) => {
                const isCorrect = q.is_correct;
                let blockClass = 'incorrect-answer';
                if (isCorrect === true) {
                    blockClass = 'correct-answer';
                } else if (isCorrect === null) {
                    blockClass = ''; // Neutral for written questions
                }

                const block = document.createElement('div');
                block.className = `question-block result-block ${blockClass}`;
                
                let content = `<p><b>ข้อ ${index + 1}:</b> ${q.question_text}</p>`;

                if (q.type === 'multiple_choice') {
                    content += '<ul>';
                    q.all_choices.forEach(choice => {
                        let choiceClass = '';
                        if (choice.key === q.correct_key) {
                            choiceClass = 'correct';
                        }
                        if (choice.key === q.selected_key && !isCorrect) {
                            choiceClass = 'incorrect';
                        }
                        content += `<li class="${choiceClass}">${choice.key}. ${choice.text}</li>`;
                    });
                    content += '</ul>';
                    content += `<p class="feedback">คุณตอบ: ${q.selected_key || 'ไม่ได้ตอบ'} | คำตอบที่ถูก: ${q.correct_key}</p>`;
                } else { // Written
                    const userAnswerHtml = q.written_answer ? q.written_answer.replace(/\\n/g, '<br>') : '<em>ไม่ได้ตอบ</em>';
                    const correctAnswerHtml = q.correct_answer_text ? q.correct_answer_text.replace(/\\n/g, '<br>') : '<em>ไม่ได้ระบุ</em>';
                    content += `<div class="written-answer-review" style="grid-template-columns: 1fr; gap: 10px;">
                                    <div><strong>คำตอบของคุณ:</strong><div class="student-written-answer">${userAnswerHtml}</div></div>
                                    <div><strong>แนวคำตอบ:</strong><div class="correct-written-answer">${correctAnswerHtml}</div></div>
                               </div>`;
                }
                block.innerHTML = content;
                detailsContainer.appendChild(block);
            });

            // Clean up session storage
            sessionStorage.removeItem('practiceResult');
        });
    </script>
</body>
</html>
