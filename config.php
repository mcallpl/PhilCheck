<?php
// PhilCheck Configuration
define('APP_NAME', 'PhilCheck');
define('DB_PATH', __DIR__ . '/data/philcheck.db');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('SESSION_TIMEOUT', 600); // 10 minutes in seconds

// Load local overrides first (API keys, etc.)
if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// Claude API defaults (only if not set in config.local.php)
if (!defined('CLAUDE_API_KEY')) define('CLAUDE_API_KEY', '');
if (!defined('CLAUDE_MODEL')) define('CLAUDE_MODEL', 'claude-sonnet-4-6-20250514');
if (!defined('CLAUDE_MAX_TOKENS')) define('CLAUDE_MAX_TOKENS', 4096);
