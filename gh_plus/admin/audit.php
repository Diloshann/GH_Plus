<?php
// admin/audit.php
require_once '../includes/config.php';
requireLogin('admin');

$active_page = 'audit';
$logs = $conn->query("SELECT al.*, u.full_name, u.role FROM audit_log al LEFT JOIN users u ON u.id=al.user_id ORDER BY al.logged_at DESC LIMIT 200")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audit Log — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button><h1>Audit Log</h1></div>
        <div class="content-area">
            <div class="table-wrap">
                <div class="table-header">
                    <h3>Last 200 events</h3>
                    <input type="text" id="live-search" placeholder="Filter log..." style="padding:6px 12px;border:1px solid #ccc;border-radius:6px;font-size:13px;">
                </div>
                <table>
                    <thead>
                        <tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="font-size:12px;white-space:nowrap;"><?= date('d M Y H:i:s', strtotime($l['logged_at'])) ?></td>
                            <td><?= htmlspecialchars($l['full_name'] ?? 'System') ?></td>
                            <td style="text-transform:capitalize;font-size:12px;"><?= htmlspecialchars($l['role'] ?? '—') ?></td>
                            <td style="font-weight:600;color:var(--red);"><?= htmlspecialchars($l['action']) ?></td>
                            <td style="font-size:12px;color:var(--gray);"><?= htmlspecialchars($l['details']) ?></td>
                            <td style="font-size:11px;font-family:monospace;"><?= htmlspecialchars($l['ip_address']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
