<?php
require_once '../config/config.php';

// Helper: format exam duration (milliseconds) to H:i:s safely
function formatExamDuration($ms) {
    if (!is_numeric($ms) || $ms < 0) return '00:00:00';
    // Convert ms -> whole seconds (round down to avoid showing future time)
    $seconds = (int) floor($ms / 1000);
    return gmdate('H:i:s', $seconds);
}

$submission_id = $_GET['submission_id'] ?? null;
if (!$submission_id) {
    die('ไม่พบข้อมูลการส่งข้อสอบ');
}

// Fetch submission details along with the exam title
$stmt = $conn->prepare("SELECT s.*, e.title as exam_title FROM submissions s JOIN exams e ON s.exam_id = e.id WHERE s.id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();
$stmt->close();

if (!$submission) {
    die('ไม่พบข้อมูลการส่งข้อสอบ');
}

// Parse anti-cheat data
$antiCheatData = $submission['anti_cheat_data'] ? json_decode($submission['anti_cheat_data'], true) : null;

$exam_id = $submission['exam_id'];

// Fetch all questions and their correct answers/choices for the exam
$questions = [];
$stmt_q = $conn->prepare("
    SELECT q.id as question_id, q.question_text, q.type, q.correct_answer, c.id as choice_id, c.choice_text, c.is_correct
    FROM questions q
    LEFT JOIN choices c ON q.id = c.question_id
    WHERE q.exam_id = ?
    ORDER BY q.id, c.id
");
$stmt_q->bind_param("i", $exam_id);
$stmt_q->execute();
$result_q = $stmt_q->get_result();
while ($row = $result_q->fetch_assoc()) {
    $qid = $row['question_id'];
    if (!isset($questions[$qid])) {
        $questions[$qid] = [
            'question_text' => $row['question_text'],
            'type' => $row['type'],
            'correct_answer_text' => $row['correct_answer'],
            'choices' => []
        ];
    }
    if ($row['choice_id']) {
        $questions[$qid]['choices'][] = [
            'id' => $row['choice_id'],
            'text' => $row['choice_text'],
            'is_correct' => $row['is_correct']
        ];
    }
}
$stmt_q->close();

// Fetch the student's specific answers for this submission
$student_answers = [];
$stmt_a = $conn->prepare("SELECT question_id, selected_choice_id, written_answer FROM submission_answers WHERE submission_id = ?");
$stmt_a->bind_param("i", $submission_id);
$stmt_a->execute();
$result_a = $stmt_a->get_result();
while ($row = $result_a->fetch_assoc()) {
    $student_answers[$row['question_id']] = [
        'choice_id' => $row['selected_choice_id'],
        'written' => $row['written_answer']
    ];
}
$stmt_a->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดูผลการตอบ - <?= htmlspecialchars($submission['student_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>ผลการตอบของ: <?= htmlspecialchars($submission['student_name']) ?></h1>
            <p><strong>ชุดข้อสอบ:</strong> <?= htmlspecialchars($submission['exam_title']) ?></p>
            <p><strong>คะแนนที่ได้:</strong> <?= $submission['score'] ?> / <?= $submission['total_questions'] ?></p>
            
            <?php if ($antiCheatData): ?>
                <div class="anti-cheat-report <?= $antiCheatData['suspicious'] ? 'suspicious' : 'normal' ?>">
                    <h3>รายงานการป้องกันการโกง</h3>
                    <div class="cheat-stats">
                        <div class="stat-item">
                            <span class="stat-label">เปลี่ยนแท็บ/หน้าต่าง:</span>
                            <span class="stat-value <?= $antiCheatData['tabSwitches'] > 3 ? 'warning' : '' ?>">
                                <?= $antiCheatData['tabSwitches'] ?> ครั้ง
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">คลิกขวา:</span>
                            <span class="stat-value <?= $antiCheatData['rightClicks'] > 5 ? 'warning' : '' ?>">
                                <?= $antiCheatData['rightClicks'] ?> ครั้ง
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">ใช้คีย์ลัด:</span>
                            <span class="stat-value <?= $antiCheatData['keyboardShortcuts'] > 3 ? 'warning' : '' ?>">
                                <?= $antiCheatData['keyboardShortcuts'] ?> ครั้ง
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">ออกจากเต็มจอ:</span>
                            <span class="stat-value <?= $antiCheatData['fullscreenExits'] > 2 ? 'warning' : '' ?>">
                                <?= $antiCheatData['fullscreenExits'] ?> ครั้ง
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">เวลาทำข้อสอบ:</span>
                            <span class="stat-value">
                                <?= isset($antiCheatData['examDuration']) ? formatExamDuration($antiCheatData['examDuration']) : '00:00:00' ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($antiCheatData['suspicious']): ?>
                        <div class="suspicious-alert">
                            ⚠️ <strong>การส่งนี้มีพฤติกรรมที่น่าสงสัย</strong> - แนะนำให้ตรวจสอบเพิ่มเติม
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>

        <main class="submission-review-container">
            <?php foreach ($questions as $qid => $q): ?>
                <div class="question-review-block">
                    <p class="question-text"><strong>คำถาม:</strong> <?= htmlspecialchars($q['question_text']) ?></p>

                    <?php if ($q['type'] === 'multiple_choice'): ?>
                        <ul>
                            <?php
                            $student_choice_id = $student_answers[$qid]['choice_id'] ?? null;
                            $is_student_answer_correct = false;
                            // Check if the student's answer was correct
                            foreach ($q['choices'] as $choice) {
                                if ($choice['id'] == $student_choice_id && $choice['is_correct']) {
                                    $is_student_answer_correct = true;
                                    break;
                                }
                            }
                            ?>
                            <?php foreach ($q['choices'] as $choice): ?>
                                <?php
                                $class = '';
                                // Highlight the actual correct answer
                                if ($choice['is_correct']) {
                                    $class = 'correct-answer-text'; 
                                }
                                // Highlight the student's choice
                                if ($choice['id'] == $student_choice_id) {
                                    $class .= $is_student_answer_correct ? ' student-correct' : ' student-incorrect';
                                }
                                ?>
                                <li class="<?= $class ?>">
                                    <?= htmlspecialchars($choice['text']) ?>
                                    <?php if ($choice['is_correct']) echo ' <span class="label-correct">(เฉลย)</span>'; ?>
                                    <?php if ($choice['id'] == $student_choice_id) echo ' <span class="label-student">(คำตอบของนักเรียน)</span>'; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif ($q['type'] === 'written'): ?>
                        <div class="written-answer-review">
                            <strong>คำตอบของนักเรียน:</strong>
                            <div class="student-written-answer">
                                <?= nl2br(htmlspecialchars($student_answers[$qid]['written'] ?? '<em>ไม่ได้ตอบ</em>')) ?>
                            </div>
                            <strong>เฉลย/แนวคำตอบ:</strong>
                            <div class="correct-written-answer">
                                <?= nl2br(htmlspecialchars($q['correct_answer_text'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </main>
        <div class="portal-actions">
             <a href="dashboard.php?exam_id=<?= $exam_id ?>" class="button">กลับไปที่ Dashboard</a>
        </div>
    </div>
</body>
</html>
