<?php
/**
 * IT Equipment Tracking System - Configuration File
 * 
 * This file contains all system configurations including:
 * - Database connection settings
 * - Base URL for dynamic path generation
 * - Timezone settings
 * - System-wide constants
 */

// Prevent direct access
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__) . '/');
defined('APP_PATH') or define('APP_PATH', __DIR__ . '/');

// ========================
// 1. ENVIRONMENT SETTINGS
// ========================

// System environment (development/production)
define('ENVIRONMENT', 'development');

// Error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ========================
// 2. BASE URL CONFIGURATION
// ========================

// Define your base URL (include trailing slash)
// Example: 'http://localhost/it_tracker/' or 'https://yourdomain.com/'
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

// Manual override (uncomment and set if automatic detection fails)
// $base_url = 'http://yourdomain.com/path/to/app/';

define('BASE_URL', $base_url);

// ========================
// 3. TIMEZONE CONFIGURATION
// ========================

// Set default timezone (list: https://www.php.net/manual/en/timezones.php)
date_default_timezone_set('Asia/Dubai'); // Change to your preferred timezone

// ========================
// 4. DATABASE CONFIGURATION
// ========================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'leverage_inventory');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

// ========================
// 5. SYSTEM CONSTANTS
// ========================

// Session configuration
define('SESSION_NAME', 'IT_EQUIPMENT_TRACKER');
define('SESSION_LIFETIME', 86400); // 24 hours
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', $_SERVER['HTTP_HOST']);
define('SESSION_SECURE', isset($_SERVER['HTTPS']));
define('SESSION_HTTPONLY', true);

// Password hashing options
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', ['cost' => 12]);


// Image upload settings
//define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('UPLOAD_URL', '/uploads/');
// Image Processing
define('IMAGE_QUALITY', 80);       // 0–100
define('MAX_WIDTH', 1024);         // Resize width, height keeps aspect ratio
// Watermarking
define('ENABLE_WATERMARK', true);
define('WATERMARK_IMAGE', dirname(__DIR__) . '/assets/watermark.png');
define('WATERMARK_POSITION', 'bottom-right'); // Options: top-left, top-right, bottom-left, bottom-right

define('ISSUEDBY_NAME','Gimesh Isuranga');
define('ISSUEDBY_DESIGNATION','IT Engineer');
define('ISSUED_DEPARTMENT','Leverage - IT Department');

// ========================
// EMAIL CONFIG
// ========================

define('FROMEMAIL','noreply@systems.nlgroup.ae');
define('FROMNAME','Leverage Inventory System');
define('HOST','systems.nlgroup.ae');
define('USERNAME','noreply@systems.nlgroup.ae');
define('PASSWORD','Isuranga1');
define('SMTPSecure','tls');
define('PORT','587');
define('REPLYTO','it@nlgroup.ae');


// ========================
// 6. DATABASE CONNECTION
// ========================

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset(DB_CHARSET);
    
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log($e->getMessage());
    die("System is currently unavailable. Please try again later.");
}

// ========================
// 7. HELPER FUNCTIONS
// ========================

/**
 * Generate absolute URL from relative path
 */
function url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Redirect to specified URL
 */
function redirect($url) {
    header("Location: " . url($url));
    exit();
}

/**
 * Escape HTML output
 */
function esc($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Start session with configured settings
session_name(SESSION_NAME);
session_set_cookie_params(
    SESSION_LIFETIME,
    SESSION_PATH,
    SESSION_DOMAIN,
    SESSION_SECURE,
    SESSION_HTTPONLY
);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Manual data values
//  Item Status
//  1 = Gppd (Working condition)
//  2 = Under Repair
//  3 = Out of Service

//  5 = Damaged (Return Status)
//  6 = Lost (Return Status)