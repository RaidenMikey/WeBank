<?php
/**
 * Security Helper Functions
 */

// Start a secure session if not already started
function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Enable secure cookie if HTTPS is active
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
    }
}

// Generate CSRF Token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF Token
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Render CSRF Input Field
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Rate Limiting Helper
function check_rate_limit($key, $limit = 5, $period = 900) {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $now = time();
    
    // Initialize or reset if period expired
    if (!isset($_SESSION['rate_limits'][$key]) || ($now - $_SESSION['rate_limits'][$key]['start_time'] > $period)) {
        $_SESSION['rate_limits'][$key] = [
            'count' => 0,
            'start_time' => $now
        ];
    }
    
    $_SESSION['rate_limits'][$key]['count']++;
    
    return $_SESSION['rate_limits'][$key]['count'] <= $limit;
}
?>
