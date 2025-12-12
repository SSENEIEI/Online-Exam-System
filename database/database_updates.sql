-- คำสั่ง SQL สำหรับอัปเดตฐานข้อมูล OES
-- รันคำสั่งเหล่านี้ใน phpMyAdmin หรือ MySQL client

-- 1. อัปเดตตาราง submissions เพื่อรองรับ anti-cheat data
ALTER TABLE submissions 
ADD COLUMN IF NOT EXISTS anti_cheat_data JSON DEFAULT NULL;

-- 2. เพิ่ม index สำหรับการค้นหาที่เร็วขึ้น
ALTER TABLE exams 
ADD INDEX idx_exam_code (exam_code),
ADD INDEX idx_created_at (created_at);

ALTER TABLE submissions 
ADD INDEX idx_exam_id (exam_id),
ADD INDEX idx_submitted_at (submitted_at);

ALTER TABLE questions 
ADD INDEX idx_exam_id_number (exam_id, question_number);

-- 3. เพิ่มตารางสำหรับ logging (optional)
CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    context JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. เพิ่มตารางสำหรับ user management (future use)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
