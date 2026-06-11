<?php
// doctor/profile.php
require_once '../includes/config.php';
requireLogin('doctor');

$active_page = 'profile';
$uid = $_SESSION['user_id'];
$success = $error = '';

$user = $conn->query("SELECT u.*, sd.position, sd.slmc_no, sd.hospital, sd.department FROM users u LEFT JOIN staff_details sd ON sd.user_id=u.id WHERE u.id=$uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!verifyPassword($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $h = hashPassword($new);
            $conn->query("UPDATE users SET password='$h' WHERE id=$uid");
            $success = 'Password updated successfully.';
        }
    } elseif ($action === 'change_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
            $file = $_FILES['profile_photo'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) {
                $error = 'Only image files allowed.';
            } elseif ($file['size'] > 5*1024*1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $newName = 'photo_doctor_' . $uid . '_' . time() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newName);
                $conn->query("UPDATE users SET profile_photo='$newName' WHERE id=$uid");
                $_SESSION['profile_photo'] = $newName;
                $success = 'Profile photo updated.';
            }
        }
    }
    $user = $conn->query("SELECT u.*, sd.position, sd.slmc_no, sd.hospital, sd.department FROM users u LEFT JOIN staff_details sd ON sd.user_id=u.id WHERE u.id=$uid")->fetch_assoc();
}

$photoSrc = (file_exists('../uploads/'.($user['profile_photo']??''))) ? '../uploads/'.$user['profile_photo'] : '../assets/default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button><h1>My Profile</h1></div>
        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Staff profile banner -->
            <div class="profile-card" style="margin-bottom:20px;">
                <img src="<?= htmlspecialchars($photoSrc) ?>" id="photo-preview">
                <div class="info">
                    <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                    <p style="color:var(--red);font-weight:600;"><?= htmlspecialchars($user['position'] ?? '') ?> &nbsp;|&nbsp; <?= htmlspecialchars($user['department'] ?? '') ?></p>
                    <p style="font-size:13px;color:var(--gray);">
                        <?= htmlspecialchars($user['hospital'] ?? '') ?>
                        &nbsp;|&nbsp; SLMC: <?= htmlspecialchars($user['slmc_no'] ?? '—') ?>
                    </p>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

                <!-- Account info (read-only fields) -->
                <div class="card">
                    <div class="card-header"><h3>Account Information</h3></div>
                    <div class="card-body">
                        <?php foreach (['NIC'=>$user['nic'],'Date of Birth'=>$user['date_of_birth'],'Gender'=>ucfirst($user['gender']??''),'City'=>$user['city'],'Email'=>$user['email'],'Phone'=>$user['phone']] as $k=>$v): ?>
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;">
                            <span style="font-size:13px;color:var(--gray);"><?= $k ?></span>
                            <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($v??'—') ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Change photo -->
                <div class="card">
                    <div class="card-header"><h3>Change Profile Photo</h3></div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="change_photo">
                            <div class="form-group">
                                <label>New Photo (JPG/PNG, max 5MB)</label>
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Photo</button>
                        </form>
                    </div>
                </div>

                <!-- Change password -->
                <div class="card">
                    <div class="card-header"><h3>Change Password</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="pw-wrap">
                                    <input type="password" name="current_password" required>
                                    <button type="button" class="pw-toggle">👁</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="pw-wrap">
                                    <input type="password" name="new_password" required placeholder="Min. 8 characters">
                                    <button type="button" class="pw-toggle">👁</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <div class="pw-wrap">
                                    <input type="password" name="confirm_password" required>
                                    <button type="button" class="pw-toggle">👁</button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
