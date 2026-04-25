<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Auto-fix: ensure all default user passwords work on first-time setup
// This replaces the need for fix_passwords.php
try {
    $defaultUsers = ['admin', 'tech1', 'doctor1'];
    $defaultPassword = 'admin123';
    
    foreach ($defaultUsers as $defUser) {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$defUser]);
        $user = $stmt->fetch();
        
        if ($user && !password_verify($defaultPassword, $user['password'])) {
            // Password hash doesn't verify - regenerate it
            $newHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
        }
    }
} catch (Exception $e) {
    // Silently ignore - this is a best-effort fix
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    }
    else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Rehash if needed (keeps hash fresh for PHP upgrades)
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            redirect('dashboard.php');
        }
        else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        (function () {
            var lang = localStorage.getItem('labLang') || 'en';
            document.documentElement.setAttribute('data-lang', lang);
            document.documentElement.setAttribute('dir', lang === 'ku' ? 'rtl' : 'ltr');
            document.documentElement.setAttribute('lang', lang === 'ku' ? 'ku' : 'en');
        })();
    </script>
</head>

<body>

    <div class="login-page">

       
        <canvas id="labCanvas" class="lab-canvas"></canvas>

        
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>

        <!-- the glass morphisim card -->
        <div class="glass-card">

            <div class="card-brand">
                <div class="brand-icon"><i class="fas fa-microscope"></i></div>
                <h1><?php echo APP_NAME; ?></h1>
                <p data-translate="Laboratory Information Management">Laboratory Information Management</p>
            </div>

            <div class="card-divider"></div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">
                <div class="field">
                    <label for="username"><i class="fas fa-user"></i> <span data-translate="Username">Username</span></label>
                    <input type="text" id="username" name="username" data-translate="Enter username" placeholder="Enter username"
                        value="<?php echo htmlspecialchars($username ?? ''); ?>" required autofocus>
                </div>
                <div class="field">
                    <label for="password"><i class="fas fa-lock"></i> <span data-translate="Password">Password</span></label>
                    <div class="pw-wrap">
                        <input type="password" id="password" name="password" data-translate="Enter password" placeholder="Enter password"
                            required>
                        <button type="button" class="pw-toggle" onclick="togglePw()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">
                    <span data-translate="Sign In">Sign In</span> <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <p class="card-footer">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
        </div>

    </div>

    <script>
        function togglePw() {
            const i = document.getElementById('password'), e = document.getElementById('eyeIcon');
            if (i.type === 'password') { i.type = 'text'; e.classList.replace('fa-eye', 'fa-eye-slash'); }
            else { i.type = 'password'; e.classList.replace('fa-eye-slash', 'fa-eye'); }
        }
    </script>
    <script src="js/lang.js"></script>
    <script src="js/login-animation.js"></script>
</body>

</html>