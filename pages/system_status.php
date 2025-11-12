<?php
/**
 * OES System Status Checker
 * A comprehensive health check for the Online Examination System
 * 
 * Usage: Open this file in your browser to check system status
 * URL: http://your-domain/system_status.php
 */

require_once '../config/config.php';

// Start output buffering for clean HTML
ob_start();

// Function to check PHP extensions
function checkPhpExtensions() {
    $required = ['pdo_mysql', 'curl', 'json', 'session'];
    $status = [];
    
    foreach ($required as $ext) {
        $status[$ext] = extension_loaded($ext);
    }
    
    return $status;
}

// Function to check file permissions
function checkFilePermissions() {
    $files = [
        '.' => ['expected' => '755', 'type' => 'directory'],
        'config.php' => ['expected' => '644', 'type' => 'file'],
        '.env' => ['expected' => '644', 'type' => 'file'],
        'logs' => ['expected' => '755', 'type' => 'directory'],
        'api' => ['expected' => '755', 'type' => 'directory'],
    ];
    
    $status = [];
    
    foreach ($files as $file => $info) {
        if (file_exists($file)) {
            $perms = substr(sprintf('%o', fileperms($file)), -3);
            $status[$file] = [
                'exists' => true,
                'permissions' => $perms,
                'expected' => $info['expected'],
                'correct' => $perms === $info['expected']
            ];
        } else {
            $status[$file] = ['exists' => false];
        }
    }
    
    return $status;
}

// Function to check database connection
function checkDatabase() {
    global $dsn, $username, $password, $options;
    
    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        
        // Check if tables exist
        $tables = ['exams', 'submissions'];
        $existing_tables = [];
        
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $existing_tables[$table] = $stmt->rowCount() > 0;
        }
        
        return [
            'connected' => true,
            'tables' => $existing_tables,
            'error' => null
        ];
    } catch (Exception $e) {
        return [
            'connected' => false,
            'tables' => [],
            'error' => $e->getMessage()
        ];
    }
}

// Function to check API configuration
function checkApiConfig() {
    $status = [
        'env_file' => file_exists('.env'),
        'gemini_key' => false
    ];
    
    if ($status['env_file']) {
        $status['gemini_key'] = !empty(getGeminiApiKey());
    }
    
    return $status;
}

// Function to check logging system
function checkLogging() {
    $logFile = 'logs/app.log';
    
    return [
        'log_directory' => is_dir('logs'),
        'log_file_exists' => file_exists($logFile),
        'log_writable' => is_writable(dirname($logFile)),
        'recent_entries' => file_exists($logFile) ? count(file($logFile)) : 0
    ];
}

// Gather all system information
$system_info = [
    'php_version' => phpversion(),
    'php_extensions' => checkPhpExtensions(),
    'file_permissions' => checkFilePermissions(),
    'database' => checkDatabase(),
    'api_config' => checkApiConfig(),
    'logging' => checkLogging(),
    'server_info' => [
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ]
];

// Calculate overall health score
function calculateHealthScore($info) {
    $total_checks = 0;
    $passed_checks = 0;
    
    // PHP Extensions (4 points)
    foreach ($info['php_extensions'] as $ext => $loaded) {
        $total_checks++;
        if ($loaded) $passed_checks++;
    }
    
    // Database (2 points)
    $total_checks += 2;
    if ($info['database']['connected']) $passed_checks++;
    if (count(array_filter($info['database']['tables'])) >= 2) $passed_checks++;
    
    // API Config (2 points)
    $total_checks += 2;
    if ($info['api_config']['env_file']) $passed_checks++;
    if ($info['api_config']['gemini_key']) $passed_checks++;
    
    // Logging (1 point)
    $total_checks++;
    if ($info['logging']['log_directory'] && $info['logging']['log_writable']) $passed_checks++;
    
    return round(($passed_checks / $total_checks) * 100);
}

$health_score = calculateHealthScore($system_info);

// Clean the output buffer
ob_end_clean();

// Set content type
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES System Status</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .health-score {
            font-size: 3em;
            font-weight: bold;
            margin: 10px 0;
        }
        .health-status {
            font-size: 1.2em;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section h2 {
            color: #333;
            border-left: 4px solid #667eea;
            padding-left: 15px;
            margin-bottom: 20px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        .status-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }
        .status-item.success {
            border-left-color: #28a745;
            background: #f8fff9;
        }
        .status-item.warning {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        .status-item.error {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        .status-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .status-value {
            color: #666;
        }
        .timestamp {
            text-align: center;
            color: #666;
            font-size: 0.9em;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔥 OES System Status</h1>
            <div class="health-score"><?php echo $health_score; ?>%</div>
            <div class="health-status">
                <?php 
                if ($health_score >= 90) echo "🟢 ระบบทำงานได้ดีเยี่ยม";
                elseif ($health_score >= 70) echo "🟡 ระบบทำงานได้ดี";
                else echo "🔴 ระบบต้องการปรับปรุง";
                ?>
            </div>
        </div>
        
        <div class="content">
            <!-- PHP Configuration -->
            <div class="section">
                <h2>🐘 PHP Configuration</h2>
                <div class="status-grid">
                    <div class="status-item success">
                        <div class="status-label">PHP Version</div>
                        <div class="status-value"><?php echo $system_info['php_version']; ?></div>
                    </div>
                    <?php foreach ($system_info['php_extensions'] as $ext => $loaded): ?>
                    <div class="status-item <?php echo $loaded ? 'success' : 'error'; ?>">
                        <div class="status-label"><?php echo strtoupper($ext); ?></div>
                        <div class="status-value"><?php echo $loaded ? '✅ Loaded' : '❌ Missing'; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Database Status -->
            <div class="section">
                <h2>🗄️ Database Status</h2>
                <div class="status-grid">
                    <div class="status-item <?php echo $system_info['database']['connected'] ? 'success' : 'error'; ?>">
                        <div class="status-label">Connection</div>
                        <div class="status-value">
                            <?php echo $system_info['database']['connected'] ? '✅ Connected' : '❌ Failed'; ?>
                            <?php if ($system_info['database']['error']): ?>
                                <br><small><?php echo htmlspecialchars($system_info['database']['error']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php foreach ($system_info['database']['tables'] as $table => $exists): ?>
                    <div class="status-item <?php echo $exists ? 'success' : 'warning'; ?>">
                        <div class="status-label">Table: <?php echo $table; ?></div>
                        <div class="status-value"><?php echo $exists ? '✅ Exists' : '⚠️ Missing'; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- API Configuration -->
            <div class="section">
                <h2>🔑 API Configuration</h2>
                <div class="status-grid">
                    <div class="status-item <?php echo $system_info['api_config']['env_file'] ? 'success' : 'warning'; ?>">
                        <div class="status-label">.env File</div>
                        <div class="status-value"><?php echo $system_info['api_config']['env_file'] ? '✅ Found' : '⚠️ Missing'; ?></div>
                    </div>
                    <div class="status-item <?php echo $system_info['api_config']['gemini_key'] ? 'success' : 'warning'; ?>">
                        <div class="status-label">Gemini API Key</div>
                        <div class="status-value"><?php echo $system_info['api_config']['gemini_key'] ? '✅ Configured' : '⚠️ Not Set'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Logging System -->
            <div class="section">
                <h2>📝 Logging System</h2>
                <div class="status-grid">
                    <div class="status-item <?php echo $system_info['logging']['log_directory'] ? 'success' : 'warning'; ?>">
                        <div class="status-label">Log Directory</div>
                        <div class="status-value"><?php echo $system_info['logging']['log_directory'] ? '✅ Exists' : '⚠️ Missing'; ?></div>
                    </div>
                    <div class="status-item <?php echo $system_info['logging']['log_writable'] ? 'success' : 'error'; ?>">
                        <div class="status-label">Write Permission</div>
                        <div class="status-value"><?php echo $system_info['logging']['log_writable'] ? '✅ Writable' : '❌ Not Writable'; ?></div>
                    </div>
                    <div class="status-item success">
                        <div class="status-label">Log Entries</div>
                        <div class="status-value"><?php echo $system_info['logging']['recent_entries']; ?> entries</div>
                    </div>
                </div>
            </div>
            
            <!-- Server Information -->
            <div class="section">
                <h2>🖥️ Server Information</h2>
                <div class="status-grid">
                    <div class="status-item success">
                        <div class="status-label">Server Software</div>
                        <div class="status-value"><?php echo htmlspecialchars($system_info['server_info']['server_software']); ?></div>
                    </div>
                    <div class="status-item success">
                        <div class="status-label">Document Root</div>
                        <div class="status-value"><?php echo htmlspecialchars($system_info['server_info']['document_root']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="timestamp">
            Last checked: <?php echo $system_info['server_info']['timestamp']; ?>
            <br>
            <a href="?refresh=1" style="color: #667eea; text-decoration: none;">🔄 Refresh Status</a>
        </div>
    </div>
</body>
</html>
