<?php
// database/auto_migrate.php

// Load configuration manually to avoid session/header issues from config.php in CLI
$rootEnv = dirname(__DIR__) . '/config/.env';
if (file_exists($rootEnv)) {
    $lines = file($rootEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Get credentials from ENV (Docker/Render) or .env file
$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
$name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'oes_db');

echo "[Migration] Connecting to database at $host...\n";

$conn = new mysqli($host, $user, $pass, $name);

if ($conn->connect_error) {
    die("[Migration] Connection failed: " . $conn->connect_error . "\n");
}

// Check if main table exists
$check = $conn->query("SHOW TABLES LIKE 'exams'");
if ($check->num_rows == 0) {
    echo "[Migration] Database empty. Starting schema import...\n";
    
    $sqlFile = __DIR__ . '/sql_schema.sql';
    if (!file_exists($sqlFile)) {
        die("[Migration] Error: sql_schema.sql not found.\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute multi-query
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        echo "[Migration] Schema imported successfully!\n";
    } else {
        echo "[Migration] Error importing schema: " . $conn->error . "\n";
    }
} else {
    echo "[Migration] Database already initialized. Skipping.\n";
}

$conn->close();
?>
