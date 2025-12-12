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
    <title>ลงทะเบียนครูใหม่ - OES</title>
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
                <h1>ลงทะเบียนครูใหม่</h1>
                <p>สร้างบัญชีเพื่อเริ่มใช้งานระบบ</p>
            </div>
            
            <div id="error-message" class="error-msg"></div>

            <form id="register-form" class="auth-form">
                <div class="form-group">
                    <label for="full_name">ชื่อ-นามสกุล</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้ (Username)</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit">ลงทะเบียน</button>
            </form>

            <div class="auth-footer">
                <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
                <p><a href="../index.php">กลับหน้าหลัก</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const errorDiv = document.getElementById('error-message');
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;

            if (p1 !== p2) {
                errorDiv.textContent = 'รหัสผ่านไม่ตรงกัน';
                errorDiv.style.display = 'block';
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'กำลังบันทึก...';
            errorDiv.style.display = 'none';

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.action = 'register';

            try {
                const res = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    alert('ลงทะเบียนสำเร็จ! กรุณาเข้าสู่ระบบ');
                    window.location.href = 'login.php';
                } else {
                    throw new Error(result.error || 'ลงทะเบียนไม่สำเร็จ');
                }
            } catch (err) {
                errorDiv.textContent = err.message;
                errorDiv.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'ลงทะเบียน';
            }
        });
    </script>
</body>
</html>
