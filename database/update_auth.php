<?php
// database/update_auth.php
require_once dirname(__DIR__) . '/config/config.php';

echo "Updating database for Authentication system...\n";

// 1. Create teachers table
$sql = "CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'teachers' created or exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// 2. Add teacher_id to exams table if not exists
$result = $conn->query("SHOW COLUMNS FROM `exams` LIKE 'teacher_id'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE `exams` ADD COLUMN `teacher_id` int(11) DEFAULT NULL AFTER `id`";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'teacher_id' added to 'exams'.\n";
        
        // Add Foreign Key
        $sql = "ALTER TABLE `exams` ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL";
        if ($conn->query($sql) === TRUE) {
            echo "Foreign key added.\n";
        } else {
            echo "Error adding foreign key: " . $conn->error . "\n";
        }
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column 'teacher_id' already exists.\n";
}

echo "Database update completed.\n";
?>
