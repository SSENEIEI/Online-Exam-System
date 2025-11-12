<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES - Teacher Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>ระบบสร้างข้อสอบด้วย AI (สำหรับครู)</h1>
            <p>สร้างชุดข้อสอบคุณภาพสูงได้อย่างรวดเร็วและง่ายดาย</p>
        </header>

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
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>
