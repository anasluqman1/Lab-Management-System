<?php
// 🔴 SHOW ERRORS (remove later in production if you want)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ================= DATABASE CONFIG =================

// Railway environment variables
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'lab_system');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// ================= APP CONFIG =================

define('APP_NAME', 'KOYA LAB');
define('APP_VERSION', '2.9.1');

// Auto-detect URL (works on Railway)
define('BASE_URL', getenv('APP_URL') ?: (
    (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']
));

// ================= DATABASE CONNECTION =================

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// ================= HELPER FUNCTIONS =================

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url)
{
    header("Location: $url");
    exit();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getUserRole()
{
    return $_SESSION['role'] ?? null;
}

function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}

function getUserName()
{
    return $_SESSION['full_name'] ?? 'User';
}

function hasRole($roles)
{
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return in_array(getUserRole(), $roles);
}

function generatePatientId()
{
    return 'P' . date('Ymd') . rand(1000, 9999);
}

function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

function formatDate($date)
{
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime)
{
    return date('M d, Y h:i A', strtotime($datetime));
}

// ================= NOTIFICATIONS =================

function getUnreadNotificationCount($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function createNotification($pdo, $userId, $type, $title, $message, $link = null)
{
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $title, $message, $link]);
}

function broadcastNotification($pdo, $roles, $type, $title, $message, $link = null)
{
    $placeholders = str_repeat('?,', count($roles) - 1) . '?';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE role IN ($placeholders) AND is_active = 1");
    $stmt->execute($roles);
    $users = $stmt->fetchAll();

    foreach ($users as $user) {
        createNotification($pdo, $user['id'], $type, $title, $message, $link);
    }
}