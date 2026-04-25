<?php
// run once to set up the database, then delete this file

echo "<pre style='font-family:Consolas,monospace;background:#0f172a;color:#06b6d4;padding:30px;font-size:14px;line-height:1.8;'>\n";
echo "╔══════════════════════════════════════════════╗\n";
echo "║        Drlab - Installation Script      ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ Connected to MySQL server\n";

    $sql = file_get_contents(__DIR__ . '/database.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt) && !preg_match('/^--/', $stmt)) {
            $pdo->exec($stmt);
        }
    }

    echo "✓ Database 'lab_system' created\n";
    echo "✓ All tables created\n";
    echo "✓ Default users seeded\n";
    echo "✓ Test catalog (50+ tests) seeded\n\n";

    echo "═══════════════════════════════════════════════\n";
    echo "  Setup Complete! Your system is ready.\n";
    echo "═══════════════════════════════════════════════\n\n";

    echo "  Default Login Accounts:\n";
    echo "  ┌─────────────┬──────────┬────────────┐\n";
    echo "  │ Username    │ Password │ Role       │\n";
    echo "  ├─────────────┼──────────┼────────────┤\n";
    echo "  │ admin       │ admin123 │ Admin      │\n";
    echo "  │ tech1       │ admin123 │ Technician │\n";
    echo "  │ doctor1     │ admin123 │ Doctor     │\n";
    echo "  └─────────────┴──────────┴────────────┘\n\n";

    echo "  Start the server:\n";
    echo "  > php -S localhost:8000\n\n";
    echo "  Then open: http://localhost:80/login.php\n\n";

    echo "  ⚠ DELETE THIS FILE after installation!\n";

} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n\n";
    echo "Make sure:\n";
    echo "  1. MySQL/MariaDB is running\n";
    echo "  2. Root user has no password (or edit config.php)\n";
    echo "  3. PHP PDO MySQL extension is enabled\n";
}

echo "</pre>";
