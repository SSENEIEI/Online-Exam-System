<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES - สร้างข้อสอบฝึกฝน</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>ระบบสร้างข้อสอบฝึกฝนด้วย AI (นักเรียน)</h1>
            <p>พิมพ์สิ่งที่อยากให้ AI ออกข้อสอบ แล้วเริ่มทำได้ทันที ไม่ต้องบันทึกลงคลัง</p>
        </header>

        <main class="exam-generator">
            <!-- Panel 1: ตั้งค่าพื้นฐาน -->
            <div class="config-panel">
                <h2>1. ตั้งค่าข้อสอบ</h2>
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
                    <label>ระบุจำนวนข้อ (แบบผสม)</label>
                    <div class="mixed-inputs">
                        <input type="number" id="mcq-count" placeholder="ปรนัย" min="1">
                        <input type="number" id="written-count" placeholder="อัตนัย" min="1">
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
                    <label for="exam-timer">เวลาสอบ</label>
                    <select id="exam-timer" name="exam-timer">
                        <option value="unlimited" selected>ไม่จำกัด</option>
                        <option value="custom">กำหนดเอง (นาที)</option>
                    </select>
                </div>
                <div id="custom-time-options" class="form-group hidden">
                    <input type="number" id="custom-time-input" placeholder="ระบุเวลา (นาที)" min="1">
                </div>
            </div>

            <!-- Panel 2: แชทกับ AI -->
            <div class="chat-panel">
                <h2>2. พิมพ์คำสั่งให้ AI</h2>
                <div id="chat-history" class="chat-history">
                    <div class="message ai-message">
                        <p>ตัวอย่าง: "สร้างข้อสอบวิทยาศาสตร์ ม.2 เรื่องสารละลาย 5 ข้อ" หรือ "ปรนัย 3 อัตนัย 2 เรื่องเศรษฐศาสตร์เบื้องต้น"</p>
                    </div>
                </div>
                <div class="chat-input">
                    <textarea id="user-prompt" placeholder="พิมพ์สิ่งที่อยากฝึก หรือแก้ไข JSON ด้านบนแล้วสั่งอีกครั้ง..."></textarea>
                    <button id="send-prompt-btn">ส่งคำสั่ง</button>
                </div>
            </div>

            <!-- Panel 3: พรีวิว -->
            <div class="preview-panel">
                <h2>3. ตรวจสอบ & เริ่มทำ</h2>
                <div id="exam-preview" class="exam-preview">
                    <p class="placeholder">AI จะแสดงข้อสอบที่นี่...</p>
                </div>
                <div id="ai-suggestions" class="ai-suggestions"></div>
                <button id="confirm-practice-exam-btn" class="hidden">เริ่มทำข้อสอบทันที</button>
            </div>
        </main>
    </div>

    <script src="../assets/js/student.js?v=1.3"></script>
</body>
</html>

