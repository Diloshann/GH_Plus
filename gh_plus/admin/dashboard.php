<?php
// admin/dashboard.php
require_once '../includes/config.php';
requireLogin('admin');

$active_page = 'dashboard';

$total_patients = $conn->query("SELECT COUNT(*) c FROM users WHERE role='patient' AND status='active'")->fetch_assoc()['c'];
$total_staff    = $conn->query("SELECT COUNT(*) c FROM users WHERE role='doctor' AND status='active'")->fetch_assoc()['c'];
$total_records  = $conn->query("SELECT COUNT(*) c FROM medical_records")->fetch_assoc()['c'];
$pending        = $conn->query("SELECT COUNT(*) c FROM users WHERE status='pending'")->fetch_assoc()['c'];

// Role breakdown
$role_counts = $conn->query("SELECT sd.position, COUNT(*) c FROM users u JOIN staff_details sd ON sd.user_id=u.id WHERE u.role='doctor' AND u.status='active' GROUP BY sd.position")->fetch_all(MYSQLI_ASSOC);

// Recent activity
$recent_users = $conn->query("SELECT id, full_name, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Admin Dashboard</h1>
            <div class="topbar-right">
                <span>👤 <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <span>|</span>
                <span>🗓 <?= date('d M Y') ?></span>
            </div>
        </div>
        <div class="content-area">

            <!-- Stats -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-label">Active Patients</div>
                    <div class="stat-value"><?= $total_patients ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Medical Staff</div>
                    <div class="stat-value"><?= $total_staff ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Records</div>
                    <div class="stat-value"><?= $total_records ?></div>
                </div>
                <div class="stat-card" style="border-left-color:orange;">
                    <div class="stat-label">Pending Approvals</div>
                    <div class="stat-value" style="color:orange;"><?= $pending ?></div>
                    <?php if ($pending > 0): ?>
                    <div class="stat-sub"><a href="approvals.php" style="color:orange;">Review now →</a></div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr;gap:20px;">

                <!-- Recent registrations -->
                <div class="table-wrap">
                    <div class="table-header"><h3>Recent Registrations</h3></div>
                    <table>
                        <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['full_name']) ?></td>
                                <td style="text-transform:capitalize;"><?= $u['role'] ?></td>
                                <td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
                                <td style="font-size:12px;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
