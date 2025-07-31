<?php
/**
 * File Manager Configuration
 * 
 * This file contains all configurable settings for the file manager.
 * Modify these values according to your needs.
 */

// Application Settings
define('APP_NAME', 'Advanced File Manager');
define('APP_VERSION', '2.0.0');
define('APP_DESCRIPTION', 'Secure Cloud Storage & File Management');

// File Upload Settings
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB in bytes
define('ALLOWED_EXTENSIONS', [
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
    'pdf', 'doc', 'docx', 'txt', 'rtf',
    'zip', 'rar', '7z', 'tar', 'gz',
    'mp3', 'mp4', 'avi', 'mov', 'wmv',
    'xls', 'xlsx', 'ppt', 'pptx'
]);

// Directory Settings
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('LOG_DIR', __DIR__ . '/logs/');
define('TEMP_DIR', __DIR__ . '/temp/');

// Security Settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_NAME', 'filemanager_session');
define('MAINTENANCE_KEY', 'your-secret-maintenance-key-change-this');

// Security Headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;"
]);

// Analytics Settings
define('GOOGLE_ANALYTICS_ID', 'G-XXXXXXXXXX'); // Replace with your GA ID
define('ENABLE_ANALYTICS', true);

// Performance Settings
define('ENABLE_CACHING', true);
define('CACHE_DURATION', 3600); // 1 hour
define('ENABLE_COMPRESSION', true);
define('LAZY_LOADING', true);

// Maintenance Settings
define('MAX_FILE_AGE', 90); // days
define('MAX_LOG_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_LOG_AGE', 30); // days
define('CLEANUP_BATCH_SIZE', 100);

// UI Settings
define('DEFAULT_VIEW', 'grid'); // 'grid' or 'list'
define('ITEMS_PER_PAGE', 20);
define('ENABLE_SEARCH', true);
define('ENABLE_SORTING', true);

// Feature Flags
define('ENABLE_DRAG_DROP', true);
define('ENABLE_KEYBOARD_SHORTCUTS', true);
define('ENABLE_PROGRESS_BARS', true);
define('ENABLE_TOOLTIPS', true);
define('ENABLE_ACCESSIBILITY', true);

// Error Reporting
define('ERROR_REPORTING', E_ALL);
define('DISPLAY_ERRORS', false);
define('LOG_ERRORS', true);

// Session Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false);
define('SESSION_HTTP_ONLY', true);

// File Type Icons (for display)
define('FILE_TYPE_ICONS', [
    'pdf' => '📄',
    'doc' => '📝', 'docx' => '📝',
    'txt' => '📄', 'rtf' => '📄',
    'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'webp' => '🖼️', 'svg' => '🖼️',
    'mp4' => '🎥', 'avi' => '🎥', 'mov' => '🎥', 'wmv' => '🎥',
    'mp3' => '🎵', 'wav' => '🎵', 'flac' => '🎵',
    'zip' => '📦', 'rar' => '📦', '7z' => '📦', 'tar' => '📦', 'gz' => '📦',
    'xls' => '📊', 'xlsx' => '📊',
    'ppt' => '📋', 'pptx' => '📋',
    'exe' => '⚙️', 'msi' => '⚙️'
]);

// Color Scheme (CSS Custom Properties)
define('COLOR_SCHEME', [
    'primary' => '#007bff',
    'secondary' => '#6c757d',
    'success' => '#28a745',
    'danger' => '#dc3545',
    'warning' => '#ffc107',
    'info' => '#17a2b8',
    'light' => '#f8f9fa',
    'dark' => '#343a40'
]);

// Responsive Breakpoints
define('BREAKPOINTS', [
    'mobile' => 768,
    'tablet' => 1200,
    'desktop' => 1201
]);

// Validation Rules
define('VALIDATION_RULES', [
    'filename' => '/^[a-zA-Z0-9._-]+$/',
    'foldername' => '/^[a-zA-Z0-9_-]+$/',
    'max_filename_length' => 255,
    'max_foldername_length' => 100
]);

// Logging Configuration
define('LOG_LEVELS', [
    'ERROR' => 0,
    'WARNING' => 1,
    'INFO' => 2,
    'DEBUG' => 3
]);

define('DEFAULT_LOG_LEVEL', 'INFO');

// Cache Settings
define('CACHE_SETTINGS', [
    'enabled' => true,
    'driver' => 'file', // 'file', 'redis', 'memcached'
    'prefix' => 'filemanager_',
    'ttl' => 3600
]);

// Rate Limiting
define('RATE_LIMITING', [
    'enabled' => true,
    'max_requests_per_minute' => 60,
    'max_uploads_per_hour' => 100
]);

// Backup Settings
define('BACKUP_SETTINGS', [
    'enabled' => true,
    'auto_backup' => true,
    'backup_retention_days' => 30,
    'backup_compression' => true
]);

// Notification Settings
define('NOTIFICATIONS', [
    'email_notifications' => false,
    'browser_notifications' => true,
    'success_messages' => true,
    'error_messages' => true
]);

// Advanced Settings
define('ADVANCED_SETTINGS', [
    'enable_debug_mode' => false,
    'enable_profiling' => false,
    'enable_auto_cleanup' => true,
    'enable_file_preview' => false,
    'enable_bulk_operations' => true,
    'enable_drag_reorder' => true
]);

// Environment Detection
function isProduction() {
    return !defined('DEVELOPMENT_MODE') || !DEVELOPMENT_MODE;
}

function isDevelopment() {
    return defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE;
}

// Initialize settings based on environment
if (isProduction()) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(ERROR_REPORTING);
    ini_set('display_errors', 1);
}

// Set session settings
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.cookie_path', SESSION_PATH);
ini_set('session.cookie_domain', SESSION_DOMAIN);
ini_set('session.cookie_secure', SESSION_SECURE);
ini_set('session.cookie_httponly', SESSION_HTTP_ONLY);

// Create necessary directories
$directories = [UPLOAD_DIR, LOG_DIR, TEMP_DIR];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Security: Prevent direct access to this file
if (basename($_SERVER['SCRIPT_NAME']) === 'config.php') {
    http_response_code(403);
    exit('Access denied');
}
?> 