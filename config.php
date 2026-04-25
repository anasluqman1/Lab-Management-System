<?php
session_start();

// Use Railway environment variables instead of localhost
define('DB_HOST', getenv('MYSQLHOST'));
define('DB_NAME', getenv('MYSQLDATABASE'));
define('DB_USER', getenv('MYSQLUSER'));
define('DB_PASS', getenv('MYSQLPASSWORD'));
define('DB_PORT', getenv('MYSQLPORT'));
define('DB_CHARSET', 'utf8mb4');

// Update this AFTER deployment (put your Railway URL)
define('APP_NAME', 'KOYA LAB');
define('APP_VERSION', '2.9.1');
define('BASE_URL', getenv('APP_URL') ?: 'http://localhost');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ===== YOUR ORIGINAL FUNCTIONS (UNCHANGED) =====

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