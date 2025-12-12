<?php
require_once '../config/config.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (isset($_SESSION['teacher_id'])) {
    header("Location: teacher_portal.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบครู - OES</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime('../assets/css/style.css'); ?>">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 60px auto;
            padding: 30px;
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-header h1 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .auth-form .form-group {
            margin-bottom: 20px;
        }
        .auth-form button {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
        }
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .error-msg {
            color: var(--error-color);
            background: #fef2f2;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: none;
            border: 1px solid #fee2e2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>เข้าสู่ระบบครู</h1>
                <p>จัดการข้อสอบและดูผลคะแนนนักเรียน</p>
            </div>
            
            <div id="error-message" class="error-msg"></div>

            <form id="login-form" class="auth-form">
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">เข้าสู่ระบบ</button>
            </form>

            <div class="auth-footer">
                <p>ยังไม่มีบัญชี? <a href="register.php">ลงทะเบียนใหม่</a></p>
                <p><a href="../index.php">กลับหน้าหลัก</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const errorDiv = document.getElementById('error-message');
            
            btn.disabled = true;
            btn.textContent = 'กำลังตรวจสอบ...';
            errorDiv.style.display = 'none';

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.action = 'login';

            try {
                const res = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    window.location.href = 'teacher_portal.php';
                } else {
                    throw new Error(result.error || 'เข้าสู่ระบบไม่สำเร็จ');
                }
            } catch (err) {
                errorDiv.textContent = err.message;
                errorDiv.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'เข้าสู่ระบบ';
            }
        });
    </script>
</body>
</html>
