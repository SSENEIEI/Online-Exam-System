<?php
require_once '../config/config.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check authentication
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) {
    die('ไม่พบรหัสชุดข้อสอบ');
}

// Fetch exam details
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$exam = $result->fetch_assoc();

if (!$exam) {
    die('ไม่พบข้อมูลชุดข้อสอบ');
}

// Check ownership
if ($exam['teacher_id'] !== $_SESSION['teacher_id']) {
    die('คุณไม่มีสิทธิ์เข้าถึงข้อมูลชุดข้อสอบนี้');
}

// Fetch submissions for this exam
$stmt = $conn->prepare("SELECT id, student_name, score, total_questions, submitted_at, anti_cheat_data FROM submissions WHERE exam_id = ? ORDER BY submitted_at DESC");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$submissions_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($exam['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime('../assets/css/style.css'); ?>">
</head>
<body>
    <div class="container">
        <header>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Dashboard: <?= htmlspecialchars($exam['title']) ?></h1>
                    <p>ภาพรวมและผลการสอบของชุดข้อสอบนี้</p>
                </div>
                <a href="teacher_portal.php" class="btn secondary">กลับหน้าหลัก</a>
            </div>
        </header>

        <main class="dashboard">
            <div class="dashboard-card access-code-card">
                <h2>รหัสสำหรับเข้าสอบ</h2>
                <p class="exam-code"><?= htmlspecialchars($exam['exam_code']) ?></p>
                <p>ให้นักเรียนใช้รหัสนี้เพื่อเข้าทำข้อสอบ</p>
            </div>

            <div class="dashboard-card exam-details-card">
                <h2>รายละเอียดข้อสอบ</h2>
                <p><strong>ระดับความยาก:</strong> <?= htmlspecialchars($exam['difficulty']) ?></p>
                <p><strong>เวลาสอบ:</strong> <?= $exam['timer_minutes'] ? htmlspecialchars($exam['timer_minutes']) . ' นาที' : 'ไม่จำกัด' ?></p>
                <a href="teacher_portal.php" class="button-secondary">กลับไปสร้างข้อสอบ</a>
            </div>

            <div class="dashboard-card submissions-card">
                <h2>ผลการส่งข้อสอบ (<?= $submissions_result->num_rows ?> คน)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อนักเรียน</th>
                            <th>คะแนน</th>
                            <th>เวลาที่ส่ง</th>
                            <th>สถานะ</th>
                            <th>การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($submissions_result->num_rows > 0): ?>
                            <?php while ($sub = $submissions_result->fetch_assoc()): ?>
                            <?php 
                                $antiCheatData = $sub['anti_cheat_data'] ? json_decode($sub['anti_cheat_data'], true) : null;
                                $suspicious = $antiCheatData && $antiCheatData['suspicious'] ? true : false;
                            ?>
                            <tr <?= $suspicious ? 'class="suspicious-submission"' : '' ?>>
                                <td><?= htmlspecialchars($sub['student_name']) ?></td>
                                <td><?= $sub['score'] ?>/<?= $sub['total_questions'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($sub['submitted_at'])) ?></td>
                                <td>
                                    <?php if ($suspicious): ?>
                                        <span class="status-suspicious">⚠️ ต้องตรวจสอบ</span>
                                    <?php else: ?>
                                        <span class="status-normal">✅ ปกติ</span>
                                    <?php endif; ?>
                                </td>
                                <td><a href="view_submission.php?submission_id=<?= $sub['id'] ?>" class="button-secondary">ดูคำตอบ</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">ยังไม่มีนักเรียนส่งข้อสอบ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

                        <div class="dashboard-card stats-card">
                <h2>สถิติการตอบ (ตัวอย่าง)</h2>
                <p><strong>ข้อที่นักเรียนตอบผิดบ่อยที่สุด:</strong> (Coming Soon)</p>
            </div>
        </main>

        <footer style="text-align: center; margin-top: 60px; padding: 24px 0; color: var(--text-secondary); font-size: 0.875rem; border-top: 1px solid var(--border-color);">
            <p style="margin: 0;">พัฒนาโดย นายศรณ์จุฑา มีแก้ว นิสิตจุฬาลงกรณ์มหาวิทยาลัย คณะวิศวกรรมศาสตร์ สาขาวิศวกรรมคอมพิวเตอร์และเทคโนโลยีดิจิทัล</p>
        </footer>
    </div>
</body>
</html>
        </main>
    </div>
</body>
</html>
