<?php
require_once dirname(__DIR__) . '/config/config.php';

echo "Connecting to TiDB at " . DB_HOST . "...\n";

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully.\n";

// 1. Run Base Schema
echo "Running base schema (sql_schema.sql)...\n";
$schemaSql = file_get_contents(__DIR__ . '/sql_schema.sql');

// Split by semicolon to run multiple queries
// Note: This is a simple splitter, might break on complex stored procs but fine for simple tables
$queries = explode(';', $schemaSql);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        try {
            $conn->query($query);
        } catch (Exception $e) {
            // Ignore "Table already exists" errors if re-running
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "Error running query: " . $e->getMessage() . "\n";
            }
        }
    }
}
echo "Base schema executed.\n";

// 2. Run Auth Updates
echo "Running auth updates...\n";
// We can just include the update_auth.php logic here or call it.
// But update_auth.php uses require_once config, which we already have.
// Let's just replicate the logic or run the file via CLI if needed.
// Better to just replicate the logic to be safe and self-contained.

// Create teachers table
$sql = "CREATE TABLE IF NOT EXISTS `teachers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `full_name` varchar(100) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'teachers' created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Add teacher_id to exams
$sql = "SHOW COLUMNS FROM `exams` LIKE 'teacher_id'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE `exams` ADD `teacher_id` int(11) DEFAULT NULL AFTER `timer_minutes`";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'teacher_id' added to 'exams'.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
    
    // Add FK
    $sql = "ALTER TABLE `exams` ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL";
    if ($conn->query($sql) === TRUE) {
        echo "Foreign key added.\n";
    } else {
        echo "Error adding foreign key: " . $conn->error . "\n";
    }
} else {
    echo "Column 'teacher_id' already exists.\n";
}

echo "Database initialization complete.\n";
?>
