<?php
require_once '../config/config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'login') {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'กรุณากรอกข้อมูลให้ครบ']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, password, full_name FROM teachers WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true); // Regenerate session ID on login
            $_SESSION['teacher_id'] = $row['id'];
            $_SESSION['teacher_name'] = $row['full_name'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'รหัสผ่านไม่ถูกต้อง']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ไม่พบชื่อผู้ใช้นี้']);
    }

} elseif ($action === 'register') {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $fullName = $input['full_name'] ?? '';

    if (empty($username) || empty($password) || empty($fullName)) {
        echo json_encode(['success' => false, 'error' => 'กรุณากรอกข้อมูลให้ครบ']);
        exit;
    }

    // Check duplicate
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO teachers (username, password, full_name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hash, $fullName);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
