<?php
// admin/profile.php
require_once '../includes/config.php';
requireLogin('admin');

$active_page = 'profile';
$uid = $_SESSION['user_id'];
$success = $error = '';

$user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();

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
            $user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
        }

    } elseif ($action === 'change_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
            $file = $_FILES['profile_photo'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) {
                $error = 'Only JPG, PNG, or GIF images are allowed.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $newName = 'photo_admin_' . $uid . '_' . time() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newName);
                $conn->query("UPDATE users SET profile_photo='$newName' WHERE id=$uid");
                $_SESSION['profile_photo'] = $newName;
                $success = 'Profile photo updated.';
                $user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
            }
        } else {
            $error = 'Please select a photo to upload.';
        }

    } elseif ($action === 'update_contact') {
        $email = sanitize($conn, $_POST['email'] ?? '');
        $phone = sanitize($conn, $_POST['phone'] ?? '');
        $name  = sanitize($conn, $_POST['full_name'] ?? '');
        if (empty($name)) {
            $error = 'Full name cannot be empty.';
        } else {
            $conn->query("UPDATE users SET full_name='$name', email='$email', phone='$phone' WHERE id=$uid");
            $_SESSION['full_name'] = $name;
            $success = 'Profile information updated.';
            $user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
        }
    }
}

$photoSrc = (!empty($user['profile_photo']) && file_exists('../uploads/' . $user['profile_photo']))
    ? '../uploads/' . $user['profile_photo']
    : '../assets/default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Profile — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Admin Profile</h1>
        </div>
        <div class="content-area">

            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Profile Banner -->
            <div class="profile-card" style="margin-bottom:24px;">
                <img src="<?= htmlspecialchars($photoSrc) ?>" alt="Admin Photo" id="photo-preview">
                <div class="info">
                    <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                    <p style="color:var(--red);font-weight:600;font-size:13px;">System Administrator</p>
                    <p style="font-size:13px;color:var(--gray);">NIC: <?= htmlspecialchars($user['nic']) ?></p>
                    <p style="font-size:13px;color:var(--gray);">
                        <?= htmlspecialchars($user['email'] ?? '—') ?>
                        <?php if (!empty($user['phone'])): ?> &nbsp;|&nbsp; <?= htmlspecialchars($user['phone']) ?><?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="two-col-grid">

                <!-- Update Name / Contact -->
                <div class="card">
                    <div class="card-header"><h3>👤 Update Profile Info</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_contact">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="admin@ghplus.lk">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="07X XXX XXXX">
                            </div>
                            <div class="form-group" style="background:#f5f5f5;padding:10px;border-radius:6px;">
                                <label>NIC (read-only)</label>
                                <p style="font-size:14px;font-weight:600;font-family:monospace;margin-top:2px;"><?= htmlspecialchars($user['nic']) ?></p>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Change Photo -->
                <div class="card">
                    <div class="card-header"><h3>📷 Change Profile Photo</h3></div>
                    <div class="card-body">
                        <div style="text-align:center;margin-bottom:16px;">
                            <img src="<?= htmlspecialchars($photoSrc) ?>"
                                 id="photo-preview-big"
                                 style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--red);">
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="change_photo">
                            <div class="form-group">
                                <label>Select New Photo (JPG/PNG, max 5MB)</label>
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Photo</button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header"><h3>🔒 Change Password</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-group">
                                <label>Current Password *</label>
                                <div class="pw-wrap">
                                    <input type="password" name="current_password" required placeholder="Enter current password">
                                    <button type="button" class="pw-toggle">👁</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password * (min 8 characters)</label>
                                <div class="pw-wrap">
                                    <input type="password" name="new_password" required placeholder="Enter new password">
                                    <button type="button" class="pw-toggle">👁</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password *</label>
                                <div class="pw-wrap">
                                    <input type="password" name="confirm_password" required placeholder="Repeat new password">
                                    <button type="button" class="pw-toggle">👁</button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>

                <!-- Account Info (read-only summary) -->
                <div class="card">
                    <div class="card-header"><h3>ℹ Account Summary</h3></div>
                    <div class="card-body">
                        <?php
                        $info = [
                            'Role'         => 'System Administrator',
                            'NIC'          => $user['nic'],
                            'Status'       => ucfirst($user['status']),
                            'Email'        => $user['email'] ?? '—',
                            'Phone'        => $user['phone'] ?? '—',
                            'Account Since'=> date('d M Y', strtotime($user['created_at'])),
                        ];
                        ?>
                        <?php foreach ($info as $k => $v): ?>
                        <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f0f0f0;font-size:13px;">
                            <span style="color:var(--gray);"><?= $k ?></span>
                            <span style="font-weight:600;"><?= htmlspecialchars($v) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div style="margin-top:16px;">
                            <a href="../logout.php" class="btn btn-danger" style="width:100%;text-align:center;display:block;">🚪 Logout</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
<script>
// Live photo preview
const photoInput = document.getElementById('profile_photo');
const previewBig = document.getElementById('photo-preview-big');
if (photoInput && previewBig) {
    photoInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => { previewBig.src = e.target.result; };
            reader.readAsDataURL(file);
        }
    });
}
</script>
</body>
</html>
