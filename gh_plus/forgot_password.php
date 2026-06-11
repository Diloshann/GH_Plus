<?php
// forgot_password.php
require_once 'includes/config.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nic = strtoupper(sanitize($conn, $_POST['nic'] ?? ''));
    $email = sanitize($conn, $_POST['email'] ?? '');

    $user = $conn->query("SELECT id FROM users WHERE nic='$nic' AND email='$email' AND status='active'")->fetch_assoc();

    if (!$user) {
        $error = 'No matching account found. Check your NIC and email.';
    } else {
        // In a real system: generate token, email reset link
        // For now: show a message
        $success = 'A password reset link has been sent to your email address. (For demo: contact your system admin to reset your password.)';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — GH+</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <img src="assets/logo.png" alt="GH+">
            <h2 style="margin-top:8px;">Reset Password</h2>
            <p>Enter your NIC and registered email</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <a href="index.php" class="btn btn-primary" style="margin-top:8px;">Back to Login</a>
        <?php else: ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>NIC Number</label>
                    <input type="text" name="nic" placeholder="987654321V or 200012345678" required maxlength="12">
                </div>
                <div class="form-group">
                    <label>Registered Email</label>
                    <input type="email" name="email" placeholder="your@email.com" required>
                </div>
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </form>
            <p style="text-align:center;margin-top:16px;font-size:13px;">
                <a href="index.php" style="color:var(--gray);">← Back to Login</a>
            </p>
        <?php endif; ?>
    </div>
</div>
<script src="js/main.js"></script>
</body>
</html>
