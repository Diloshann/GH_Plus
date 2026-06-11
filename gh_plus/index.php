<?php
// index.php — Login Page
require_once 'includes/config.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    redirect($role === 'admin' ? 'admin/dashboard.php' : ($role === 'doctor' ? 'doctor/search.php' : 'patient/dashboard.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nic  = sanitize($conn, $_POST['nic'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (empty($nic) || empty($pass)) {
        $error = 'Please enter your NIC number and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, password, role, profile_photo, status FROM users WHERE nic = ?");
        $stmt->bind_param('s', $nic);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'No account found with that NIC number.';
        } elseif ($user['status'] === 'pending') {
            $error = 'Your account is pending admin approval. Please wait.';
        } elseif ($user['status'] === 'rejected') {
            $error = 'Your registration was rejected. Contact support.';
        } elseif ($user['status'] === 'inactive') {
            $error = 'Your account has been deactivated. Contact admin.';
        } elseif (!verifyPassword($pass, $user['password'])) {
            $error = 'Incorrect password.';
        } else {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['full_name']    = $user['full_name'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['profile_photo']= $user['profile_photo'];

            if ($user['role'] === 'admin')  redirect('admin/dashboard.php');
            elseif ($user['role'] === 'doctor') redirect('doctor/search.php');
            else redirect('patient/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <img src="assets/logo.png" alt="GH+ Logo">
            <h2>Government Hospital Plus</h2>
            <p>Secure Medical Records System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nic">NIC Number</label>
                <input type="text" id="nic" name="nic" placeholder="e.g. 987654321V or 200012345678" maxlength="12" required autocomplete="off">
                <small id="nic-msg" style="font-size:11px;"></small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="pw-toggle">👁</button>
                </div>
            </div>

            <div style="text-align:right;margin-bottom:14px;">
                <a href="forgot_password.php" style="font-size:12px;color:var(--gray);">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <p style="text-align:center;margin-top:16px;font-size:13px;color:var(--gray);">
            Don't have an account?
            <a href="signup.php" style="color:var(--red);font-weight:600;">Sign Up</a>
        </p>

        <p style="text-align:center;margin-top:24px;font-size:11px;color:#bbb;">
            &copy; <?= date('Y') ?> <?= SITE_NAME ?> &mdash; Sri Lanka
        </p>
    </div>
</div>
<script src="js/main.js"></script>
</body>
</html>
