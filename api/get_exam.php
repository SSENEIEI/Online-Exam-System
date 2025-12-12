<?php
require_once '../config/config.php';
header('Content-Type: application/json');

$exam_code = $_GET['code'] ?? '';

if (empty($exam_code)) {
    http_response_code(400);
    Logger::warning('Empty exam code provided');
    echo json_encode(['error' => 'Exam code is required']);
    exit;
}

// Validate exam code format
if (!validateExamCode($exam_code)) {
    http_response_code(400);
    Logger::warning("Invalid exam code format: $exam_code");
    echo json_encode(['error' => 'Invalid exam code format']);
    exit;
}

$exam_code = sanitizeInput($exam_code);

$stmt = $conn->prepare("SELECT id, title, timer_minutes FROM exams WHERE exam_code = ?");
$stmt->bind_param("s", $exam_code);
$stmt->execute();
$result = $stmt->get_result();
$exam = $result->fetch_assoc();

if (!$exam) {
    http_response_code(404);
    Logger::info("Exam not found: $exam_code");
    echo json_encode(['error' => 'Exam not found']);
    exit;
}

Logger::info("Exam loaded successfully: $exam_code");

// Add server timestamp for timer synchronization
$exam['server_time'] = time();

$exam_id = $exam['id'];

$stmt = $conn->prepare("
    SELECT q.id, q.question_text, q.type, q.question_number, c.id as choice_id, c.choice_key, c.choice_text
    FROM questions q
    LEFT JOIN choices c ON q.id = c.question_id
    WHERE q.exam_id = ?
    ORDER BY q.question_number, c.choice_key
");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
    $qid = $row['id'];
    if (!isset($questions[$qid])) {
        $questions[$qid] = [
            'id' => $qid,
            'question_text' => $row['question_text'],
            'type' => $row['type'],
            'choices' => []
        ];
    }
    if ($row['choice_id']) {
        $questions[$qid]['choices'][] = [
            'id' => $row['choice_id'],
            'key' => $row['choice_key'],
            'text' => $row['choice_text']
        ];
    }
}

$exam['questions'] = array_values($questions);

echo json_encode($exam);

$stmt->close();
$conn->close();
