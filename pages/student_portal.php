<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>ยินดีต้อนรับ, นักเรียน!</h1>
            <p>เลือกสิ่งที่ต้องการทำด้านล่าง</p>
        </header>

        <main class="portal-main">
            <div class="portal-card">
                <h2>เข้าทำข้อสอบ</h2>
                <p>กรอกรหัสข้อสอบที่ได้รับจากคุณครูเพื่อเริ่มทำข้อสอบ</p>
                <form id="enter-exam-form" onsubmit="return false;">
                    <div class="code-input-container">
                        <input type="text" class="code-input" maxlength="1" required>
                        <input type="text" class="code-input" maxlength="1" required>
                        <input type="text" class="code-input" maxlength="1" required>
                        <input type="text" class="code-input" maxlength="1" required>
                        <input type="text" class="code-input" maxlength="1" required>
                        <input type="text" class="code-input" maxlength="1" required>
                    </div>
                    <button type="submit" class="button">เข้าสอบ</button>
                </form>
            </div>
            <div class="portal-card">
                <h2>สร้างข้อสอบฝึกฝนด้วย AI</h2>
                <p>สร้างชุดข้อสอบส่วนตัวเพื่อฝึกฝนและทบทวนบทเรียนได้ทุกเมื่อ</p>
                <button id="create-practice-btn" class="button-secondary">สร้างแบบทดสอบของฉัน</button>
            </div>
        </main>
    </div>
    <script src="../assets/js/student.js?v=1.3"></script>
</body>
</html>
