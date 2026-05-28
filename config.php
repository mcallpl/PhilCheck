<?php
// PhilCheck Configuration
define('APP_NAME', 'PhilCheck');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('SESSION_TIMEOUT', 600); // 10 minutes in seconds

// MySQL Database
define('DB_HOST', 'localhost');
define('DB_USER', 'mcallpl');
define('DB_PASS', 'amazing123');
define('DB_NAME', 'philcheck');

// Load local overrides first (API keys, etc.)
if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// Gemini API defaults (only if not set in config.local.php)
if (!defined('GEMINI_API_KEY')) define('GEMINI_API_KEY', '');
if (!defined('GEMINI_MODEL')) define('GEMINI_MODEL', 'gemini-2.0-flash');
if (!defined('GEMINI_MAX_TOKENS')) define('GEMINI_MAX_TOKENS', 4096);
