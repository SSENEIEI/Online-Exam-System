<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES - Online Exam System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container portal-container">
        <header class="main-header">
            <div class="logo-section">
                <h1 class="main-title">
                    <span class="logo-text">OES</span>
                    <span class="subtitle">Online Exam System</span>
                </h1>
                <p class="tagline">แพลตฟอร์มสอบออนไลน์อัจฉริยะ พัฒนาด้วยเทคโนโลยี AI ชั้นนำ</p>
                <div class="trust-indicators">
                </div>
            </div>
            <div class="features-preview">
                <div class="feature-item premium">🤖 AI สร้างข้อสอบอัตโนมัติ</div>
                <div class="feature-item premium">🛡️ Anti-Cheat System</div>
                <div class="feature-item premium">📊 Analytics Dashboard</div>
                <div class="feature-item premium">☁️ Cloud-Based Platform</div>
            </div>
        </header>

        <main class="portal-main">
            <div class="role-selection">
                <h2>เริ่มต้นใช้งานระบบ</h2>
                <div class="role-cards">
                    <a href="pages/teacher_portal.php" class="portal-card-link">
                        <div class="portal-card teacher-card">
                            <div class="card-header">
                                <div class="portal-icon">
                                    <svg width="60" height="60" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                                        <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                                    </svg>
                                </div>
                                <div class="card-badge">EDUCATOR</div>
                            </div>
                            <h3>สำหรับครูและสถาบัน</h3>
                            <p>จัดการข้อสอบแบบมืออาชีพ ตั้งแต่การสร้างด้วย AI จนถึงการวิเคราะห์ผลลัพธ์เชิงลึก</p>
                            <div class="card-features">
                                <div class="feature-list">
                                    <span class="feature">✓ สร้างข้อสอบด้วย AI</span>
                                    <span class="feature">✓ Dashboard แบบเรียลไทม์</span>
                                    <span class="feature">✓ รายงานการโกงโดยละเอียด</span>
                                    <span class="feature">✓ ส่งออกผลการสอบ Excel/PDF</span>
                                </div>
                            </div>
                            <div class="card-action">
                                <span class="action-text">เข้าสู่ระบบครู →</span>
                            </div>
                        </div>
                    </a>

                    <a href="pages/student_portal.php" class="portal-card-link">
                        <div class="portal-card student-card">
                            <div class="card-header">
                                <div class="portal-icon">
                                    <svg width="60" height="60" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </div>
                                <div class="card-badge">STUDENT</div>
                            </div>
                            <h3>สำหรับนักเรียนและผู้สอบ</h3>
                            <p>ประสบการณ์การสอบที่ราบรื่น พร้อมระบบป้องกันการโกงและการฝึกฝนด้วย AI</p>
                            <div class="card-features">
                                <div class="feature-list">
                                    <span class="feature">✓ เข้าสอบด้วยรหัส 6 หลัก</span>
                                    <span class="feature">✓ โหมดฝึกฝนส่วนตัว</span>
                                    <span class="feature">✓ ดูผลสอบทันที</span>
                                    <span class="feature">✓ รองรับทุกอุปกรณ์</span>
                                </div>
                            </div>
                            <div class="card-action">
                                <span class="action-text">เริ่มทำข้อสอบ →</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="enterprise-features">
                <h2>ฟีเจอร์ระดับ Enterprise</h2>
                <div class="info-grid">
                    <div class="info-card premium-card">
                        <div class="card-icon">🤖</div>
                        <h4>AI-Powered Question Generation</h4>
                        <p>ใช้ Google Gemini AI รุ่นล่าสุดในการสร้างข้อสอบที่หลากหลายและมีคุณภาพสูง รองรับทั้งข้อปรนัยและอัตนัย</p>
                        <ul class="feature-details">
                            <li>สร้างข้อสอบตามหลักสูตรไทย</li>
                            <li>ปรับระดับความยากได้</li>
                            <li>ตรวจสอบความถูกต้องอัตโนมัติ</li>
                        </ul>
                    </div>
                    <div class="info-card premium-card">
                        <div class="card-icon">🛡️</div>
                        <h4>Advanced Anti-Cheat System</h4>
                        <p>ระบบป้องกันการโกงที่ครอบคลุมและแม่นยำ ตรวจจับพฤติกรรมผิดปกติได้แบบเรียลไทม์</p>
                        <ul class="feature-details">
                            <li>ตรวจจับการเปลี่ยนแท็บ/หน้าต่าง</li>
                            <li>บล็อกคีย์ลัดและคลิกขวา</li>
                            <li>บังคับโหมดเต็มจอ</li>
                            <li>รายงานการโกงโดยละเอียด</li>
                        </ul>
                    </div>
                    <div class="info-card premium-card">
                        <div class="card-icon">📊</div>
                        <h4>Real-time Analytics Dashboard</h4>
                        <p>แดชบอร์ดวิเคราะห์ผลการสอบแบบเรียลไทม์ พร้อมรายงานเชิงลึกและการส่งออกข้อมูล</p>
                        <ul class="feature-details">
                            <li>สถิติการตอบแบบเรียลไทม์</li>
                            <li>วิเคราะห์ข้อที่ตอบผิดบ่อย</li>
                            <li>ส่งออกเป็น Excel/PDF</li>
                        </ul>
                    </div>
                    <div class="info-card premium-card">
                        <div class="card-icon">☁️</div>
                        <h4>Cloud Infrastructure</h4>
                        <p>โครงสร้างพื้นฐานระบบคลาวด์ที่มั่นคงและปลอดภัย รองรับผู้ใช้งานจำนวนมากพร้อมกัน</p>
                        <ul class="feature-details">
                            <li>Auto-scaling สำหรับผู้ใช้จำนวนมาก</li>
                            <li>Backup ข้อมูลอัตโนมัติ</li>
                            <li>SSL Certificate ฟรี</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>