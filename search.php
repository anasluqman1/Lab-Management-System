<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = sanitize($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, patient_id, full_name, age, gender, phone 
    FROM patients 
    WHERE full_name LIKE ? OR patient_id LIKE ? 
    ORDER BY full_name 
    LIMIT 10
");
$stmt->execute(["%$q%", "%$q%"]);
$results = $stmt->fetchAll();

echo json_encode($results);
