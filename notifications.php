<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        // mark everything as read (keeps notifications visible but removes unread badge)
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
        echo json_encode(['success' => true]);
        exit;
    }
    if (isset($_POST['clear_all'])) {
        // remove all notifications for the user
        $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$userId]);
        echo json_encode(['success' => true]);
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$q = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$q->execute([$userId]);
$count = $q->fetchColumn();

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => intval($count)
]);
