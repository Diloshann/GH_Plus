<?php
// patient/profile.php
require_once '../includes/config.php';
requireLogin('patient');

$uid = $_SESSION['user_id'];
$active_page = 'profile';
$success = $error = '';

$user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$patient = $conn->query("SELECT * FROM patient_details WHERE user_id=$uid")->fetch_assoc();

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
            $hashed = hashPassword($new);
            $conn->query("UPDATE users SET password='$hashed' WHERE id=$uid");
            $success = 'Password changed successfully.';
        }
    } elseif ($action === 'update_contact') {
        $email = sanitize($conn, $_POST['email'] ?? '');
        $phone = sanitize($conn, $_POST['phone'] ?? '');
        $city  = sanitize($conn, $_POST['city'] ?? '');
        $conn->query("UPDATE users SET email='$email', phone='$phone', city='$city' WHERE id=$uid");
        $success = 'Contact information updated.';
        $user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
    } elseif ($action === 'change_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
            $file = $_FILES['profile_photo'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) {
                $error = 'Only JPG, PNG, or GIF images are allowed.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $newName = 'photo_patient_' . $uid . '_' . time() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newName);
                $conn->query("UPDATE users SET profile_photo='$newName' WHERE id=$uid");
                $_SESSION['profile_photo'] = $newName;
                $success = 'Profile photo updated.';
                $user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
            }
        }
    }
}

$photoSrc = (file_exists('../uploads/' . ($user['profile_photo'] ?? ''))) ? '../uploads/' . $user['profile_photo'] : '../assets/default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile Settings — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button><h1>Profile Settings</h1></div>
        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Patient Profile Banner -->
            <div class="profile-card" style="margin-bottom:20px;">
                <img src="<?= htmlspecialchars($photoSrc) ?>" id="photo-preview">
                <div class="info">
                    <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                    <p style="color:var(--red);font-weight:600;">Patient Profile &nbsp;|&nbsp; <?= ageLabel($user['date_of_birth']) ?></p>
                    <p style="font-size:13px;color:var(--gray);">
                        <?= htmlspecialchars($user['city'] ?? '—') ?>
                        &nbsp;|&nbsp; Blood Type: <?= htmlspecialchars($user['blood_type'] ?? '—') ?>
                    </p>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

                <!-- Read-only info -->
                <div class="card">
                    <div class="card-header"><h3>Personal Information</h3></div>
                    <div class="card-body">
                        <?php $ro = ['NIC'=>$user['nic'],'Date of Birth'=>$user['date_of_birth'],'Gender'=>ucfirst($user['gender']??''),'City'=>$user['city']]; ?>
                        <?php foreach ($ro as $label => $val): ?>
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;">
                            <span style="font-size:13px;color:var(--gray);"><?= $label ?></span>
                            <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($val ?? '—') ?></span>
                        </div>
                        <?php endforeach; ?>
                        <p style="font-size:11px;color:var(--gray);margin-top:10px;">⚠ Name, NIC, DOB can only be changed by an Admin.</p>
                    </div>
                </div>

                <!-- Medical Health Info -->
                <div class="card">
                    <div class="card-header"><h3>Medical Health Info</h3></div>
                    <div class="card-body">
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;">
                            <span style="font-size:13px;color:var(--gray);">Blood Type</span>
                            <span style="font-size:13px;font-weight:600;color:var(--red);"><?= htmlspecialchars($user['blood_type'] ?? '—') ?></span>
                        </div>
                        <div style="padding:8px 0;">
                            <span style="font-size:13px;color:var(--gray);">Known Diseases</span>
                            <p style="font-size:12px;margin-top:4px;padding:8px;background:#f9f9f9;border-radius:4px;white-space:pre-wrap;">
                                <?= htmlspecialchars($patient['diseases'] ?? 'None recorded') ?>
                            </p>
                        </div>
                        <div style="padding:8px 0;">
                            <span style="font-size:13px;color:var(--gray);">Allergies & Notes</span>
                            <p style="font-size:12px;margin-top:4px;padding:8px;background:#f9f9f9;border-radius:4px;white-space:pre-wrap;">
                                <?= htmlspecialchars($patient['description'] ?? 'No notes') ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Change photo -->
                <div class="card">
                    <div class="card-header"><h3>Change Profile Photo</h3></div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="change_photo">
                            <div class="form-group">
                                <label>New Photo (max 5MB)</label>
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Photo</button>
                        </form>
                    </div>
                </div>

                <!-- Contact info -->
                <div class="card">
                    <div class="card-header"><h3>Update Contact Info</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_contact">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <select name="city">
                                    <?php foreach ($sriLankaCities as $c): ?>
                                        <option <?= $c===$user['city']?'selected':'' ?>><?= $c ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
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
