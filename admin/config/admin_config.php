<?php
session_start();
define('ADMIN_PATH', dirname(__DIR__));
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
define('ADMIN_URL', getenv('ADMIN_URL') ?: $protocol . $_SERVER['HTTP_HOST'] . '/admin');

// Database configuration
define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQL_USER') ?: 'root');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: '');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'murna_foundation');
define('DB_PORT', getenv('MYSQL_PORT') ?: 3306);

// Admin session timeout (30 minutes)
define('ADMIN_TIMEOUT', 1800);

// Function to check admin login
function isAdminLoggedIn() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_last_activity'])) {
        return false;
    }
    
    // Check session timeout
    if (time() - $_SESSION['admin_last_activity'] > ADMIN_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    $_SESSION['admin_last_activity'] = time();
    return true;
}

// Function to redirect if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Function to log admin activity
function logAdminActivity($admin_id, $action, $description = null) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $admin_id, $action, $description, $ip);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // Silent fail for logging
    }
}

// Database connection function
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}


/**
 * Mask a NIN for display. Accepts any string of digits; returns a masked
 * version with the middle replaced by bullets.
 *
 * @param string|null $nin     The raw NIN (11 digits for Nigeria).
 * @param int         $keep    How many leading digits to keep (0 = hide all).
 * @param int         $trail   How many trailing digits to keep.
 * @return string              Masked form, or '—' if empty.
 */
function mask_nin(?string $nin, int $keep = 0, int $trail = 4): string
{
    $nin = preg_replace('/\D+/', '', (string) $nin); // digits only
    $len = strlen($nin);

    if ($len === 0) {
        return '—';
    }
    if ($len <= $keep + $trail) {
        // Too short to mask meaningfully — hide everything
        return str_repeat('•', $len);
    }

    $middle = $len - $keep - $trail;

    return substr($nin, 0, $keep)
         . str_repeat('•', $middle)
         . substr($nin, -$trail);
}

?>
