<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (clevis_is_admin()) {
    header('Location: admin.php');
    exit;
}

$authFlash = $_SESSION['auth_flash'] ?? null;
$authOld = $_SESSION['auth_old'] ?? [];
unset($_SESSION['auth_flash'], $_SESSION['auth_old']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['auth_old'] = [
        'email' => trim($_POST['email'] ?? ''),
    ];

    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        clevis_set_auth_flash('error', 'Enter the admin email and password.');
        header('Location: admin-login');
        exit;
    }

    if (!$pdo) {
        clevis_set_auth_flash('error', $database_error ?? 'Database connection failed.');
        header('Location: admin-login');
        exit;
    }

    $statement = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
    $statement->execute([':email' => $email]);
    $user = $statement->fetch();

    if (!$user || ($user['role'] ?? 'user') !== 'admin' || !password_verify($password, $user['password_hash'])) {
        clevis_set_auth_flash('error', 'Invalid admin login credentials.');
        header('Location: admin-login');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = 'admin';

    try {
        $loginStatement = $pdo->prepare(
            'INSERT INTO login_records (user_id, email, role)
             VALUES (:user_id, :email, :role)'
        );
        $loginStatement->execute([
            ':user_id' => $_SESSION['user_id'],
            ':email' => $user['email'],
            ':role' => 'admin',
        ]);
    } catch (PDOException $loginException) {
        // Login history is optional if the new table has not been imported yet.
    }

    unset($_SESSION['auth_old'], $_SESSION['auth_flash']);

    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Clevis Mark</title>
    <meta name="description" content="Private admin login page for the Clevis Mark website.">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-page">
    <div class="page-shell">
        <header class="topbar">
            <a class="brand" href="index.php">
                <span class="brand-mark" aria-hidden="true">CM</span>
                <span class="brand-name">Clevis Mark</span>
            </a>
            <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-nav">
                <span class="menu-toggle-lines" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>
            <button class="nav-overlay" type="button" aria-label="Close navigation" tabindex="-1"></button>
            <nav class="topnav" id="site-nav">
                <a class="nav-home" href="index.php">Public Site</a>
                <a class="nav-pdf" href="portfolio_pdf.php">PDF Portfolio</a>
            </nav>
        </header>

        <main>
            <section class="auth-hero" id="admin-login">
                <div class="auth-copy">
                    <p class="eyebrow">Private Access</p>
                    <h1>Admin login for Clevis Mark.</h1>
                    <p class="hero-text">
                        Use the private login path to access the dashboard. Public registration has been removed.
                    </p>
                    <?php if ($authFlash): ?>
                        <div class="flash <?= htmlspecialchars($authFlash['type'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($authFlash['message'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php elseif ($database_error): ?>
                        <div class="flash info">
                            <?= htmlspecialchars($database_error, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <form class="auth-form" action="admin-login" method="post">
                    <div class="auth-form-head">
                        <h2>Sign in</h2>
                        <p>Only the site owner can access this page.</p>
                    </div>

                    <label>
                        Email Address
                        <input type="email" name="email" placeholder="nyongesaclevis76@gmail.com" value="<?= htmlspecialchars($authOld['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        Password
                        <input type="password" name="password" placeholder="Your password" required>
                    </label>

                    <button class="button primary full" type="submit">Login</button>
                </form>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
