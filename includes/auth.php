<?php
// includes/auth.php
if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/config.php';
}

// Include the Database class
require_once __DIR__ . '/database.php';

// Create a global database instance
$db = new Database();

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && $_SESSION['user_level'] === 'admin';
}

function login($username, $password) {
    global $db;
    
    $username = trim($username);
    $password = trim($password);
    
    if (empty($username) || empty($password)) {
        return ['status' => 'error', 'message' => 'Username and password are required'];
    }
    
    // Use the Database class to get the user
    $conditions = [
        'username' => $username,
        'is_active' => 1
    ];
    
    $user = $db->getRow('users', $conditions, 'user_id, username, password, full_name, user_level');
    
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_level'] = $user['user_level'];
        $_SESSION['last_activity'] = time();
        
        // Log this login
        $log_data = [
            'user_id' => $user['user_id'],
            'action' => 'Login',
            'table_affected' => 'users',
            'record_id' => $user['user_id'],
            'action_details' => 'User logged in',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $db->insert('audit_log', $log_data);
        
        return ['status' => 'success'];
    }
    
    // Log failed attempt
    $log_data = [
        'user_id' => null,
        'action' => 'Failed Login',
        'table_affected' => 'users',
        'record_id' => null,
        'action_details' => "Failed login attempt for: $username",
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    $db->insert('audit_log', $log_data);
    
    return ['status' => 'error', 'message' => 'Invalid credentials'];
}

function logout() {
    global $db;
    
    if (is_logged_in()) {
        // Log the logout
        $log_data = [
            'user_id' => $_SESSION['user_id'],
            'action' => 'Logout',
            'table_affected' => 'users',
            'record_id' => $_SESSION['user_id'],
            'action_details' => 'User logged out',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $db->insert('audit_log', $log_data);
        
        $_SESSION = array();
        session_destroy();
    }
    header("Location: auth/login.php");
    exit();
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: auth/login.php");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Check for inactive session (30 minute timeout)
if (is_logged_in() && (time() - $_SESSION['last_activity'] > 1800)) {
    global $db;
    
    // Log session timeout
    $log_data = [
        'user_id' => $_SESSION['user_id'],
        'action' => 'Session Timeout',
        'table_affected' => 'users',
        'record_id' => $_SESSION['user_id'],
        'action_details' => 'Session expired due to inactivity',
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    $db->insert('audit_log', $log_data);
    
    logout();
}