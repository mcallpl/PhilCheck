<?php
// PhilCheck Configuration
define('APP_NAME', 'PhilCheck');
define('DB_PATH', __DIR__ . '/data/philcheck.db');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Claude API - set your key in config.local.php
define('CLAUDE_API_KEY', '');
define('CLAUDE_MODEL', 'claude-sonnet-4-6-20250514');
define('CLAUDE_MAX_TOKENS', 4096);

// Load local overrides
if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
