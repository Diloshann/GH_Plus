<?php
// includes/sidebar.php
// Usage: include this after defining $active_page (string)
// and after session_start() + config.php

$role = $_SESSION['role'] ?? 'patient';
$name = $_SESSION['full_name'] ?? 'User';
$photo = $_SESSION['profile_photo'] ?? 'default.png';

$photoSrc = (file_exists(__DIR__ . '/../uploads/' . $photo)) ? '../uploads/' . $photo : '../assets/default.png';
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="GH+">
        <span>GH<em>+</em></span>
    </div>
    <div class="sidebar-user">
        <img src="<?= htmlspecialchars($photoSrc) ?>" alt="Photo">
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($name) ?></div>
            <div class="user-role"><?= ucfirst($role) ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php if ($role === 'patient'): ?>
            <a href="../patient/dashboard.php" class="<?= ($active_page==='dashboard')?'active':'' ?>">
                <span class="nav-icon">🏠</span> Dashboard
            </a>
            <a href="../patient/history.php" class="<?= ($active_page==='history')?'active':'' ?>">
                <span class="nav-icon">📋</span> Medical History
            </a>
            <a href="../patient/prescriptions.php" class="<?= ($active_page==='prescriptions')?'active':'' ?>">
                <span class="nav-icon">💊</span> Prescriptions
            </a>
            <a href="../patient/documents.php" class="<?= ($active_page==='documents')?'active':'' ?>">
                <span class="nav-icon">📁</span> Documents
            </a>
            <a href="../patient/profile.php" class="<?= ($active_page==='profile')?'active':'' ?>">
                <span class="nav-icon">👤</span> Profile Settings
            </a>
            <a href="../patient/contact.php" class="<?= ($active_page==='contact')?'active':'' ?>">
                <span class="nav-icon">📞</span> Contact Us
            </a>

        <?php elseif ($role === 'doctor'): ?>
            <a href="../doctor/search.php" class="<?= ($active_page==='search')?'active':'' ?>">
                <span class="nav-icon">🔍</span> Search Patients
            </a>
            <a href="../doctor/my_patients.php" class="<?= ($active_page==='my_patients')?'active':'' ?>">
                <span class="nav-icon">🧑‍⚕️</span> My Patients
            </a>
           
            <a href="../doctor/profile.php" class="<?= ($active_page==='profile')?'active':'' ?>">
                <span class="nav-icon">👤</span> Profile
            </a>

        <?php elseif ($role === 'admin'): ?>
            <a href="../admin/dashboard.php" class="<?= ($active_page==='dashboard')?'active':'' ?>">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="../admin/patients.php" class="<?= ($active_page==='patients')?'active':'' ?>">
                <span class="nav-icon">🧑‍🤝‍🧑</span> Manage Patients
            </a>
            <a href="../admin/staff.php" class="<?= ($active_page==='staff')?'active':'' ?>">
                <span class="nav-icon">🏥</span> Manage Staff
            </a>
            <a href="../admin/approvals.php" class="<?= ($active_page==='approvals')?'active':'' ?>">
                <span class="nav-icon">✅</span> Pending Approvals
                <?php
                $pcount = $conn->query("SELECT COUNT(*) c FROM users WHERE status='pending'")->fetch_assoc()['c'];
                if ($pcount > 0) echo "<span style='background:var(--red);color:#fff;border-radius:20px;padding:1px 7px;font-size:11px;margin-left:auto;'>$pcount</span>";
                ?>
            </a>
            <a href="../admin/reports.php" class="<?= ($active_page==='reports')?'active':'' ?>">
                <span class="nav-icon">📈</span> Reports
            </a>
            <a href="../admin/support.php" class="<?= ($active_page==='support')?'active':'' ?>">
                <span class="nav-icon">💬</span> Support Messages
                <?php
                $smcount = $conn->query("SELECT COUNT(*) c FROM support_messages WHERE status='open'")->fetch_assoc()['c'];
                if ($smcount > 0) echo "<span style='background:orange;color:#fff;border-radius:20px;padding:1px 7px;font-size:11px;margin-left:auto;'>$smcount</span>";
                ?>
            </a>
            <a href="../admin/profile.php" class="<?= ($active_page==='profile')?'active':'' ?>">
                <span class="nav-icon">👤</span> My Profile
            </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php">
            <span>🚪</span> Logout
        </a>
    </div>
</div>
