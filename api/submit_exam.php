<?php
/**
 * Store a student's exam submission.
 * Aligns with current schema:
 *  - submissions (id, exam_id, student_name, score, total_questions, anti_cheat_data, submitted_at)
 *  - submission_answers (submission_id, question_id, selected_choice_id, written_answer, is_correct)
 *  - questions / choices tables hold correct answers (choices.is_correct = 1 OR questions.correct_answer for written)
 */
require_once '../config/config.php';
header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || !isset($payload['exam_id'], $payload['student_name'], $payload['answers'])) {
    http_response_code(400);
    Logger::warning('Invalid submission data received');
    echo json_encode(['error' => 'Invalid submission data']);
    exit;
}

$exam_id = (int)$payload['exam_id'];
$student_name = sanitizeInput($payload['student_name']);
$answers = $payload['answers']; // array of {question_id, type, answer}
$anti_cheat_report = $payload['anti_cheat_report'] ?? null;

if ($exam_id <= 0 || !$student_name || !is_array($answers) || count($answers) === 0) {
    http_response_code(400);
    Logger::warning("Invalid submission parameters: exam_id=$exam_id, student='$student_name'");
    echo json_encode(['error' => 'Invalid submission parameters']);
    exit;
}

Logger::info("Processing exam submission: exam_id=$exam_id, student=$student_name, answers=" . count($answers));

// Build a map of question -> (type, correct choice id(s), written correct answer text)
$questionIds = array_map(fn($a) => (int)$a['question_id'], $answers);
$placeholders = implode(',', array_fill(0, count($questionIds), '?'));

// Fetch questions
$types = [];
$writtenCorrect = [];
if ($placeholders) {
    $stmt = $conn->prepare("SELECT id, type, correct_answer FROM questions WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($questionIds)), ...$questionIds);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $types[$row['id']] = $row['type'];
        if ($row['type'] === 'written') {
            $writtenCorrect[$row['id']] = $row['correct_answer'];
        }
    }
    $stmt->close();
}

// Fetch correct choices for MCQs
$correctChoices = [];
$mcqIds = array_filter($questionIds, fn($qid) => ($types[$qid] ?? '') === 'multiple_choice');
if ($mcqIds) {
    $ph2 = implode(',', array_fill(0, count($mcqIds), '?'));
    $stmt = $conn->prepare("SELECT question_id, id FROM choices WHERE question_id IN ($ph2) AND is_correct = 1");
    $stmt->bind_param(str_repeat('i', count($mcqIds)), ...$mcqIds);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $correctChoices[$row['question_id']] = $row['id'];
    }
    $stmt->close();
}

// Score answers & collect rows for insertion
$score = 0;
$totalQuestions = count($answers);
$answerRows = [];
foreach ($answers as $ans) {
    $qid = (int)$ans['question_id'];
    $type = $types[$qid] ?? $ans['type'] ?? '';
    $selected_choice_id = null;
    $written_answer = null;
    $is_correct = 0;

    if ($type === 'multiple_choice') {
        // Expect answer to be a choice id (from front-end value attribute). If front-end sends key, adapt here.
        $selected_choice_id = is_numeric($ans['answer']) ? (int)$ans['answer'] : null;
        if ($selected_choice_id && isset($correctChoices[$qid]) && $selected_choice_id === (int)$correctChoices[$qid]) {
            $is_correct = 1;
            $score++;
        }
    } elseif ($type === 'written') {
        $written_answer = trim((string)$ans['answer']);
        // Written questions not auto-scored (could implement future AI scoring)
        $is_correct = 0;
    }
    $answerRows[] = [$qid, $selected_choice_id, $written_answer, $is_correct];
}

// Insert submission + answers in transaction
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO submissions (exam_id, student_name, score, total_questions, anti_cheat_data) VALUES (?,?,?,?,?)");
    $antiCheatJson = $anti_cheat_report ? json_encode($anti_cheat_report, JSON_UNESCAPED_UNICODE) : null;
    $stmt->bind_param('isiis', $exam_id, $student_name, $score, $totalQuestions, $antiCheatJson);
    $stmt->execute();
    $submission_id = $stmt->insert_id;
    $stmt->close();

    $stmtAns = $conn->prepare("INSERT INTO submission_answers (submission_id, question_id, selected_choice_id, written_answer, is_correct) VALUES (?,?,?,?,?)");
    foreach ($answerRows as $row) {
        [$qid, $choiceId, $writtenAns, $correctFlag] = $row;
        // selected_choice_id nullable
        $choiceParam = $choiceId ? $choiceId : null;
        $stmtAns->bind_param('iiisi', $submission_id, $qid, $choiceParam, $writtenAns, $correctFlag);
        $stmtAns->execute();
    }
    $stmtAns->close();

    $conn->commit();
    Logger::info("Submission stored: submission_id=$submission_id, exam_id=$exam_id, student=$student_name, score=$score/$totalQuestions");
    echo json_encode([
        'success' => true,
        'submission_id' => $submission_id,
        'score' => $score,
        'total_questions' => $totalQuestions,
        'percentage' => $totalQuestions > 0 ? round(($score / $totalQuestions) * 100, 2) : 0
    ], JSON_UNESCAPED_UNICODE);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    Logger::error('Submission transaction failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถบันทึกการส่งได้', 'details' => $e->getMessage()]);
}

$conn->close();
?>
