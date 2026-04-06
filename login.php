<?php
require_once __DIR__ . '/includes/db.php';
session_start();

// Already logged in? Go to app
if (!empty($_SESSION['user_id']) && (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity']) <= SESSION_TIMEOUT)) {
    header('Location: index.php');
    exit;
}

$error = '';
$timeout = $_SESSION['timeout_message'] ?? '';
unset($_SESSION['timeout_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Update login count and last login
            $stmt = $db->prepare("UPDATE users SET login_count = login_count + 1, last_login = NOW() WHERE id = ?");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $stmt->close();

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['last_activity'] = time();

            header('Location: index.php');
            exit;
        } else {
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
    <title>PhilCheck — Sign In</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #E8F5E9 0%, #F5F7F5 50%, #FFF8E1 100%);
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .login-card .logo {
            font-size: 48px;
            margin-bottom: 8px;
        }
        .login-card h1 {
            font-size: 28px;
            color: var(--primary-dark);
            margin-bottom: 6px;
        }
        .login-card .subtitle {
            color: var(--text-light);
            font-size: 16px;
            margin-bottom: 32px;
        }
        .login-field {
            margin-bottom: 18px;
            text-align: left;
        }
        .login-field label {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 6px;
        }
        .login-field input {
            width: 100%;
            padding: 14px 16px;
            font-size: 17px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .login-field input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .login-btn {
            width: 100%;
            padding: 16px;
            font-size: 18px;
            font-weight: 700;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.2s;
        }
        .login-btn:hover { background: var(--primary-dark); }
        .login-error {
            background: #FFEBEE;
            color: #C62828;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 15px;
            margin-bottom: 18px;
        }
        .login-timeout {
            background: #FFF3E0;
            color: #E65100;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 15px;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo">&#9829;</div>
            <h1>PhilCheck</h1>
            <p class="subtitle">Your Personal Health Journal</p>

            <?php if ($timeout): ?>
                <div class="login-timeout"><?= htmlspecialchars($timeout) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="login-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="login-field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" autocomplete="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autofocus>
                </div>
                <div class="login-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password">
                </div>
                <button type="submit" class="login-btn">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
