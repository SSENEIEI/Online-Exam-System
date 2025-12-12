<?php
require_once '../config/config.php';
// Debug helper (development only)
function debug_out($label, $data) {
    if (ENVIRONMENT !== 'production') {
        Logger::info('[DEBUG save_exam] ' . $label . ': ' . (is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE)));
    }
}
header('Content-Type: application/json');
// Start output buffering to catch accidental warnings (e.g., permission issues) so we can return clean JSON
ob_start();

$raw = file_get_contents('php://input');
$requestBody = json_decode($raw, true);
debug_out('raw_input', $raw);

if (!$requestBody || !isset($requestBody['exam_data'])) {
    http_response_code(400);
    Logger::warning('Invalid exam save request received');
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

$examData = $requestBody['exam_data'];
$timer = $requestBody['timer'] ?? ['type' => 'unlimited'];

// Validate exam data
$title = sanitizeInput($examData['exam_title'] ?? '');
$difficulty = sanitizeInput($examData['difficulty'] ?? '');
$questions = $examData['questions'] ?? [];

if (empty($title)) {
    http_response_code(400);
    Logger::warning("Missing exam title");
    echo json_encode(['error' => 'กรุณาระบุชื่อข้อสอบ (exam_title)']);
    exit;
}
if (!is_array($questions) || count($questions) === 0) {
    http_response_code(400);
    Logger::warning("No questions supplied: title='$title'");
    echo json_encode(['error' => 'ไม่พบรายการคำถามในข้อมูลส่งเข้า']);
    exit;
}
// Basic structural validation first question
foreach ($questions as $idx => $q) {
    if (!isset($q['question_text']) || !isset($q['type'])) {
        http_response_code(400);
        Logger::warning('Question structure invalid at index ' . $idx);
        echo json_encode(['error' => 'โครงสร้างคำถามไม่ถูกต้อง (question_text/type หาย)']);
        exit;
    }
}

Logger::info("Saving exam: title='$title', difficulty='$difficulty', questions=" . count($questions));
debug_out('first_question_sample', $questions[0] ?? null);

// Generate a unique 6-character exam code (retry if collision)
do {
    $examCode = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
    $check = $conn->prepare("SELECT id FROM exams WHERE exam_code = ? LIMIT 1");
    $check->bind_param('s', $examCode);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
} while ($exists);

$conn->begin_transaction();

try {
    // Insert into exams table
    $teacherId = $_SESSION['teacher_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO exams (exam_code, title, difficulty, timer_minutes, teacher_id) VALUES (?, ?, ?, ?, ?)");
    $timer_minutes = ($timer['type'] === 'custom' && !empty($timer['value'])) ? (int)$timer['value'] : null;
    if ($timer_minutes !== null && $timer_minutes <= 0) {
        $timer_minutes = null; // sanitize bad value
    }
    $stmt->bind_param("sssii", $examCode, $title, $difficulty, $timer_minutes, $teacherId);
    $stmt->execute();
    $examId = $stmt->insert_id;
    debug_out('exam_inserted', ['exam_id'=>$examId,'exam_code'=>$examCode,'timer_minutes'=>$timer_minutes]);

    // Insert into questions and choices tables
    $questionStmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, correct_answer, type, question_number) VALUES (?, ?, ?, ?, ?)");
    $choiceStmt = $conn->prepare("INSERT INTO choices (question_id, choice_key, choice_text, is_correct) VALUES (?, ?, ?, ?)");

    $qNumber = 0;
    foreach ($examData['questions'] as $q) {
        $qNumber++;
        $question_number = isset($q['question_number']) ? (int)$q['question_number'] : $qNumber;
        $qText = trim($q['question_text']);
        $qType = $q['type'];
        $correct_answer_text = ($qType === 'written') ? ($q['correct_answer'] ?? '') : null;
    $questionStmt->bind_param("isssi", $examId, $qText, $correct_answer_text, $qType, $question_number);
    $questionStmt->execute();
    $questionId = $questionStmt->insert_id;
    if ($qNumber === 1) { debug_out('first_question_inserted', ['id'=>$questionId,'type'=>$qType]); }

        if ($qType === 'multiple_choice') {
            if (!empty($q['options']) && is_array($q['options'])) {
                foreach ($q['options'] as $key => $value) {
                    $choiceKey = substr($key, 0, 5); // safety
                    $choiceText = trim($value);
                    $isCorrect = ($key == ($q['correct_answer'] ?? '')) ? 1 : 0;
                    $choiceStmt->bind_param("issi", $questionId, $choiceKey, $choiceText, $isCorrect);
                    $choiceStmt->execute();
                }
            } else {
                Logger::warning("MCQ missing options at question_number=$question_number exam_id=$examId");
            }
        }
    }

    $conn->commit();
    Logger::info("Exam saved successfully: id=$examId, code=$examCode, title='$title'");
    // Clear any stray warnings captured
    $warnings = trim(ob_get_clean());
    if ($warnings) { Logger::warning('Buffered warnings during save_exam success: ' . strip_tags($warnings)); }
    echo json_encode([
        'success' => true,
        'exam_id' => $examId,
        'exam_code' => $examCode,
        'debug' => ENVIRONMENT !== 'production' ? 'saved_ok' : null
    ]);

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    Logger::error("Database error saving exam: " . $exception->getMessage());
    http_response_code(500);
    $warnings = trim(ob_get_clean());
    if ($warnings) { Logger::warning('Buffered warnings during save_exam failure: ' . strip_tags($warnings)); }
    echo json_encode([
        'error' => 'Database transaction failed',
        'details' => $exception->getMessage(),
        'trace' => ENVIRONMENT !== 'production' ? $exception->getTraceAsString() : null
    ]);
} finally {
    if (isset($stmt) && $stmt) { $stmt->close(); }
    if (isset($questionStmt) && $questionStmt) { $questionStmt->close(); }
    if (isset($choiceStmt) && $choiceStmt) { $choiceStmt->close(); }
    $conn->close();
}
