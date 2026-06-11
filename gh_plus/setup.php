<?php
// setup.php — Run this ONCE in your browser if admin login fails
// URL: http://localhost/gh_plus/setup.php
// DELETE this file after use for security!

require_once 'includes/config.php';

$adminNic      = '000000000V';
$adminPassword = 'Admin@1234';
$adminName     = 'System Administrator';
$adminEmail    = 'admin@ghplus.lk';

// Check if admin already exists
$existing = $conn->query("SELECT id, password FROM users WHERE nic='$adminNic'")->fetch_assoc();

$newHash = password_hash($adminPassword, PASSWORD_BCRYPT);
$log = [];

if ($existing) {
    // Update the password hash with freshly generated one
    $conn->query("UPDATE users SET password='$newHash', status='active', role='admin' WHERE nic='$adminNic'");
    $log[] = "✅ Admin account found and password reset successfully.";
    $log[] = "   Hash updated: <code>" . htmlspecialchars($newHash) . "</code>";
} else {
    // Insert fresh admin account
    $stmt = $conn->prepare("INSERT INTO users (nic, full_name, password, role, email, status) VALUES (?,?,?,'admin',?,'active')");
    $stmt->bind_param('ssss', $adminNic, $adminName, $newHash, $adminEmail);
    $stmt->execute();
    $stmt->close();
    $log[] = "✅ Admin account created successfully.";
}

// Verify it works right now
$check = $conn->query("SELECT password FROM users WHERE nic='$adminNic'")->fetch_assoc();
$verified = $check && password_verify($adminPassword, $check['password']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GH+ Setup</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <img src="assets/logo.png" alt="GH+" style="width:70px;">
            <h2 style="margin-top:8px;color:var(--red);">GH+ Setup</h2>
        </div>

        <?php foreach ($log as $line): ?>
            <div class="alert alert-success"><?= $line ?></div>
        <?php endforeach; ?>

        <?php if ($verified): ?>
        <div class="alert alert-success">
            ✅ <strong>Verification passed</strong> — login will work correctly.
        </div>
        <div style="background:#f9f9f9;border:1px solid #e0e0e0;border-radius:8px;padding:16px;margin-bottom:16px;font-size:14px;">
            <p style="font-weight:600;margin-bottom:8px;">Login credentials:</p>
            <p>NIC: <strong>000000000V</strong></p>
            <p>Password: <strong>Admin@1234</strong></p>
        </div>
        <a href="index.php" class="btn btn-primary">Go to Login →</a>
        <?php else: ?>
        <div class="alert alert-error">
            ❌ Verification failed. Check your database connection in <code>includes/config.php</code>.
        </div>
        <?php endif; ?>

        <div style="margin-top:20px;background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:12px;font-size:12px;color:#856404;">
            ⚠ <strong>Security:</strong> Delete or rename <code>setup.php</code> after use.<br>
            Do not leave this file accessible on a production server.
        </div>
    </div>
</div>
</body>
</html>
