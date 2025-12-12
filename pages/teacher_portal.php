<?php
require_once '../config/config.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES - Teacher Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime('../assets/css/style.css'); ?>">
</head>
<body>
    <div class="container">
        <header>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>ระบบสร้างข้อสอบด้วย AI (สำหรับครู)</h1>
                    <p>ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['teacher_name']); ?></p>
                </div>
                <a href="logout.php" class="btn secondary" style="padding: 8px 16px; font-size: 0.9rem;">ออกจากระบบ</a>
            </div>
        </header>

        <?php
        $teacher_id = $_SESSION['teacher_id'];
        $stmt = $conn->prepare("SELECT id, title, exam_code, created_at FROM exams WHERE teacher_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $my_exams = $stmt->get_result();
        ?>

        <section class="my-exams" style="margin-bottom: 30px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <h2 style="margin-top: 0; color: var(--primary-color); border-bottom: 2px solid var(--bg-color); padding-bottom: 10px; margin-bottom: 15px;">ประวัติการสร้างข้อสอบ</h2>
            <?php if ($my_exams->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--bg-color); text-align: left;">
                                <th style="padding: 12px; border-radius: 6px 0 0 6px;">ชื่อชุดข้อสอบ</th>
                                <th style="padding: 12px;">รหัสข้อสอบ</th>
                                <th style="padding: 12px;">วันที่สร้าง</th>
                                <th style="padding: 12px; border-radius: 0 6px 6px 0;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($exam = $my_exams->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($exam['title']); ?></td>
                                    <td style="padding: 12px;"><span style="background: var(--accent-color); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.9em;"><?php echo htmlspecialchars($exam['exam_code']); ?></span></td>
                                    <td style="padding: 12px; color: var(--text-secondary);"><?php echo date('d/m/Y H:i', strtotime($exam['created_at'])); ?></td>
                                    <td style="padding: 12px;">
                                        <a href="dashboard.php?exam_id=<?php echo $exam['id']; ?>" class="btn secondary" style="padding: 4px 12px; font-size: 0.85rem; text-decoration: none;">ดูผลสอบ</a>
                                        <button onclick="deleteExam(<?php echo $exam['id']; ?>)" class="btn" style="padding: 4px 12px; font-size: 0.85rem; background-color: #ef4444; color: white; border: none; margin-left: 5px; cursor: pointer;">ลบ</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 20px;">ยังไม่มีประวัติการสร้างข้อสอบ</p>
            <?php endif; ?>
        </section>

        <main class="exam-generator">
            <div class="config-panel">
                <h2>1. กำหนดค่าเริ่มต้น</h2>
                <div class="form-group">
                    <label for="exam-type">รูปแบบข้อสอบ</label>
                    <select id="exam-type" name="exam-type">
                        <option value="multiple_choice">ปรนัย (ก-ง)</option>
                        <option value="written">อัตนัย (เขียนตอบ)</option>
                        <option value="mixed">แบบผสม</option>
                    </select>
                </div>
                <div id="single-type-options" class="form-group">
                    <label for="total-questions">จำนวนข้อ</label>
                    <input type="number" id="total-questions" placeholder="ระบุจำนวนข้อ" min="1" value="10">
                </div>
                <div id="mixed-options" class="form-group mixed-options hidden">
                    <label>ระบุจำนวนข้อ (แบบผสม):</label>
                    <div class="mixed-inputs">
                        <input type="number" id="mcq-count" placeholder="จำนวนข้อปรนัย" min="1">
                        <input type="number" id="written-count" placeholder="จำนวนข้ออัตนัย" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label for="difficulty">ระดับความยาก</label>
                    <select id="difficulty" name="difficulty">
                        <option value="Easy">ง่าย</option>
                        <option value="Medium" selected>ปานกลาง</option>
                        <option value="Hard">ยาก</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="exam-timer">กำหนดเวลาสอบ</label>
                    <select id="exam-timer" name="exam-timer">
                        <option value="unlimited" selected>ไม่จำกัดเวลา</option>
                        <option value="custom">กำหนดเอง (นาที)</option>
                    </select>
                </div>
                <div id="custom-time-options" class="form-group hidden">
                     <input type="number" id="custom-time-input" placeholder="ระบุเวลา (นาที)" min="1">
                </div>
            </div>

            <div class="chat-panel">
                <h2>2. สั่งให้ AI สร้างข้อสอบ</h2>
                <div id="chat-history" class="chat-history">
                    <div class="message ai-message">
                        <p>สวัสดีครับคุณครู! โปรดบอกรายละเอียดข้อสอบที่ต้องการได้เลยครับ เช่น "สร้างข้อสอบวิทยาศาสตร์ ม.3 เรื่องระบบสุริยะ 10 ข้อ"</p>
                    </div>
                </div>
                <div class="chat-input">
                    <textarea id="user-prompt" placeholder="พิมพ์คำสั่ง หรือแก้ไขข้อสอบที่นี่..."></textarea>
                    <button id="send-prompt-btn">ส่งคำสั่ง</button>
                </div>
            </div>

            <div class="preview-panel">
                <h2>3. ตรวจสอบและยืนยัน</h2>
                <div id="exam-preview" class="exam-preview">
                    <p class="placeholder">AI จะแสดงตัวอย่างข้อสอบที่นี่...</p>
                </div>
                <div id="ai-suggestions" class="ai-suggestions"></div>
                <button id="confirm-exam-btn" class="hidden">ยืนยันและสร้างชุดข้อสอบ</button>
            </div>
        </main>

        <footer style="text-align: center; margin-top: 60px; padding: 24px 0; color: var(--text-secondary); font-size: 0.875rem; border-top: 1px solid var(--border-color);">
            <p style="margin: 0;">พัฒนาโดย นายศรณ์จุฑา มีแก้ว นิสิตจุฬาลงกรณ์มหาวิทยาลัย คณะวิศวกรรมศาสตร์ สาขาวิศวกรรมคอมพิวเตอร์และเทคโนโลยีดิจิทัล</p>
        </footer>
    </div>

    <script src="../assets/js/app.js?v=<?php echo filemtime('../assets/js/app.js'); ?>"></script>
    <script>
        async function deleteExam(examId) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะลบข้อสอบชุดนี้? การกระทำนี้ไม่สามารถย้อนกลับได้ และข้อมูลผลสอบทั้งหมดจะถูกลบด้วย')) {
                return;
            }

            try {
                const res = await fetch('../api/delete_exam.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ exam_id: examId })
                });
                const result = await res.json();

                if (result.success) {
                    alert('ลบข้อสอบเรียบร้อยแล้ว');
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (result.error || 'ไม่สามารถลบข้อสอบได้'));
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + err.message);
            }
        }
    </script>
</body>
</html>
