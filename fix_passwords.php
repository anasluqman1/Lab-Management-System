<?php
// run once to reset all passwords, then delete this file
require_once __DIR__ . '/config.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<pre style='font-family:Consolas;background:#0f172a;color:#06b6d4;padding:30px;font-size:14px;line-height:2;'>";
echo "Fixing passwords for all users...\n\n";

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ?");
    $stmt->execute([$hash]);

    $count = $stmt->rowCount();
    echo "✓ Updated $count user(s) with password: admin123\n";
    echo "✓ New hash: $hash\n\n";

    echo "Verification:\n";
    $users = $pdo->query("SELECT id, username, role, password FROM users")->fetchAll();
    foreach ($users as $u) {
        $ok = password_verify('admin123', $u['password']) ? '✓ OK' : '✗ FAIL';
        echo "  {$u['username']} ({$u['role']}): $ok\n";
    }

    echo "\n═══════════════════════════════════════\n";
    echo "All passwords set to: admin123\n";
    echo "⚠ DELETE THIS FILE NOW!\n";
    echo "═══════════════════════════════════════\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
