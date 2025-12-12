<?php
require_once '../config/config.php';

$submission_id = $_GET['submission_id'] ?? null;
if (!$submission_id) {
    die("ไม่พบผลการสอบ");
}

// Fetch submission details
$stmt = $conn->prepare("SELECT s.score, s.total_questions, e.title FROM submissions s JOIN exams e ON s.exam_id = e.id WHERE s.id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();

if (!$submission) {
    die("ไม่พบผลการสอบ");
}

// Fetch answers
$stmt = $conn->prepare("
    SELECT q.question_text, q.type, sa.is_correct, sa.written_answer,
           (SELECT GROUP_CONCAT(CONCAT(c.choice_key, ': ', c.choice_text) SEPARATOR '||') FROM choices c WHERE c.question_id = q.id) as all_choices,
           (SELECT c.choice_key FROM choices c WHERE c.id = sa.selected_choice_id) as selected_key,
           (SELECT c.choice_key FROM choices c WHERE c.question_id = q.id AND c.is_correct = 1) as correct_key
    FROM submission_answers sa
    JOIN questions q ON sa.question_id = q.id
    WHERE sa.submission_id = ?
    ORDER BY q.question_number
");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$answers_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลการสอบ: <?= htmlspecialchars($submission['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="result-header">
            <h1>ผลการสอบ: <?= htmlspecialchars($submission['title']) ?></h1>
            <div class="score-circle">
                <span class="score"><?= $submission['score'] ?></span>
                <span class="total">/ <?= $submission['total_questions'] ?></span>
            </div>
            <p>คุณทำคะแนนได้ <?= $submission['score'] ?> จาก <?= $submission['total_questions'] ?> คะแนน</p>
        </header>

        <main class="result-details">
            <h2>รายละเอียดคำตอบ</h2>
            <?php while ($row = $answers_result->fetch_assoc()): ?>
                <div class="question-block result-block <?= $row['is_correct'] ? 'correct-answer' : 'incorrect-answer' ?>">
                    <p><?= htmlspecialchars($row['question_text']) ?></p>
                    <?php if ($row['type'] == 'multiple_choice'): ?>
                        <ul>
                            <?php
                            $choices = explode('||', $row['all_choices']);
                            foreach ($choices as $choice_str) {
                                list($key, $text) = explode(': ', $choice_str, 2);
                                $class = '';
                                if ($key == $row['correct_key']) $class = 'correct';
                                if ($key == $row['selected_key'] && !$row['is_correct']) $class = 'incorrect';
                                echo "<li class='$class'>$key. $text</li>";
                            }
                            ?>
                        </ul>
                        <p class="feedback">คุณตอบ: <?= $row['selected_key'] ?? 'ไม่ได้ตอบ' ?> | คำตอบที่ถูก: <?= $row['correct_key'] ?></p>
                    <?php else: // Written answer ?>
                        <p class="feedback">คำตอบของคุณ: <?= htmlspecialchars($row['written_answer'] ?? 'ไม่ได้ตอบ') ?></p>
                        <p class="feedback"><em>(ข้อเขียนต้องให้ครูตรวจ)</em></p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </main>
        <div class="portal-actions">
            <a href="student_portal.php" class="button">กลับสู่หน้าหลัก</a>
        </div>

        <footer style="text-align: center; margin-top: 60px; padding: 24px 0; color: var(--text-secondary); font-size: 0.875rem; border-top: 1px solid var(--border-color);">
            <p style="margin: 0;">พัฒนาโดย นายศรณ์จุฑา มีแก้ว นิสิตจุฬาลงกรณ์มหาวิทยาลัย คณะวิศวกรรมศาสตร์ สาขาวิศวกรรมคอมพิวเตอร์และเทคโนโลยีดิจิทัล</p>
        </footer>
    </div>
</body>
</html>
