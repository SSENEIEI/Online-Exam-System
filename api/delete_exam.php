<?php
require_once '../config/config.php';
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$exam_id = $input['exam_id'] ?? null;

if (!$exam_id) {
    echo json_encode(['success' => false, 'error' => 'Missing exam_id']);
    exit;
}

// Verify ownership
$stmt = $conn->prepare("SELECT id FROM exams WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $exam_id, $_SESSION['teacher_id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Exam not found or permission denied']);
    exit;
}

// Delete exam (Cascading delete will handle related tables)
$stmt = $conn->prepare("DELETE FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}
?>
