<?php
/**
 * Environment Configuration Loader
 * โหลดค่า configuration จากไฟล์ .env
 */

function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
    return true;
}

// โหลด environment variables (รองรับทั้ง config/.env และ root/.env)
if (!loadEnv(__DIR__ . '/.env')) {
    // fallback: project root
    $rootEnv = dirname(__DIR__) . '/.env';
    loadEnv($rootEnv);
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 3306);
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'oes_db');
define('DB_SSL', $_ENV['DB_SSL'] ?? getenv('DB_SSL') ?? 'false');

// Environment Settings
define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? getenv('ENVIRONMENT') ?? 'production');
define('DEBUG_MODE', $_ENV['DEBUG_MODE'] ?? getenv('DEBUG_MODE') ?? 'false');
define('SESSION_TIMEOUT', $_ENV['SESSION_TIMEOUT'] ?? getenv('SESSION_TIMEOUT') ?? 1800);

// Error Reporting & Log Directory Setup
$PROJECT_ROOT = dirname(__DIR__); // one level up from /config
$LOG_DIR = $PROJECT_ROOT . '/logs';
if (!is_dir($LOG_DIR)) {
    @mkdir($LOG_DIR, 0755, true);
}

if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', $LOG_DIR . '/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', $LOG_DIR . '/error-dev.log');
}

// Enable mysqli exceptions for error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create a database connection
    $conn = mysqli_init();
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

    if (DB_SSL === 'true') {
        $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
        $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT, NULL, MYSQLI_CLIENT_SSL);
    } else {
        $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    }

    // Set the character set to utf8mb4
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    if (ENVIRONMENT === 'production') {
        error_log('Connection Error: ' . $e->getMessage());
        die("ขออภัย, ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้");
    } else {
        die("Database Connection Error: " . $e->getMessage());
    }
}

/**
 * Security Helper Functions
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateExamCode($code) {
    return preg_match('/^[A-Z0-9]{6}$/', $code);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Session Management
 */
session_start();
// session_regenerate_id(true); // Removed to prevent session loss on concurrent requests

// Session timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

/**
 * Logger Class
 */
class Logger {
    private static $logDir;
    private static function ensureDir() {
        if (!self::$logDir) {
            self::$logDir = dirname(__DIR__) . '/logs/';
        }
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0755, true);
        }
    }
    
    public static function log($message, $level = 'INFO') {
        self::ensureDir();
        $timestamp = date('Y-m-d H:i:s');
        $log = "[$timestamp] [$level] $message" . PHP_EOL;
        $logFile = self::$logDir . 'app-' . date('Y-m-d') . '.log';

        // If directory not writable fallback to PHP system error_log to avoid emitting warnings that break JSON responses
        if (!is_writable(self::$logDir)) {
            // Try to adjust perms silently (best-effort)
            @chmod(self::$logDir, 0775);
        }
        if (!is_writable(self::$logDir)) {
            error_log('[FALLBACK LOG] ' . $log);
            return;
        }
        // Suppress warnings from file_put_contents (we already guard writability) to keep API output clean
        @file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    }
    
    public static function error($message) {
        self::log($message, 'ERROR');
    }
    
    public static function info($message) {
        self::log($message, 'INFO');
    }
    
    public static function warning($message) {
        self::log($message, 'WARNING');
    }
}

/**
 * Get Gemini API Key securely
 */
function getGeminiApiKey() {
    return $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?? null;
}

function getSecondaryGeminiApiKey() {
    return $_ENV['GEMINI_API_KEY_SECONDARY'] ?? getenv('GEMINI_API_KEY_SECONDARY') ?? null;
}
