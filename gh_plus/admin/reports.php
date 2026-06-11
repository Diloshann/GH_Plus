<?php
// admin/reports.php
require_once '../includes/config.php';
requireLogin('admin');

// ── ALL redirects MUST happen before ANY output ───────────────
if (isset($_GET['resolve'])) {
    $rid = (int)$_GET['resolve'];
    $conn->query("UPDATE support_messages SET status='resolved' WHERE id=$rid");
    redirect('reports.php');
}

$active_page = 'reports';

$stats = [
    'Active Patients'       => $conn->query("SELECT COUNT(*) c FROM users WHERE role='patient' AND status='active'")->fetch_assoc()['c'],
    'Active Staff'          => $conn->query("SELECT COUNT(*) c FROM users WHERE role='doctor' AND status='active'")->fetch_assoc()['c'],
    'Total Medical Records' => $conn->query("SELECT COUNT(*) c FROM medical_records")->fetch_assoc()['c'],
    'Documents Uploaded'    => $conn->query("SELECT COUNT(*) c FROM medical_documents")->fetch_assoc()['c'],
    'Pending Approvals'     => $conn->query("SELECT COUNT(*) c FROM users WHERE status='pending'")->fetch_assoc()['c'],
    'Open Support Tickets'  => $conn->query("SELECT COUNT(*) c FROM support_messages WHERE status='open'")->fetch_assoc()['c'],
];

$monthly     = $conn->query("SELECT DATE_FORMAT(visit_date,'%b %Y') AS month, COUNT(*) c FROM medical_records WHERE visit_date >= DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(visit_date,'%Y-%m') ORDER BY visit_date ASC")->fetch_all(MYSQLI_ASSOC);
$by_position = $conn->query("SELECT sd.position, COUNT(*) c FROM staff_details sd JOIN users u ON u.id=sd.user_id WHERE u.status='active' GROUP BY sd.position ORDER BY c DESC")->fetch_all(MYSQLI_ASSOC);
$by_blood    = $conn->query("SELECT blood_type, COUNT(*) c FROM users WHERE role='patient' AND status='active' AND blood_type IS NOT NULL GROUP BY blood_type ORDER BY c DESC")->fetch_all(MYSQLI_ASSOC);
$support     = $conn->query("SELECT sm.*, u.full_name FROM support_messages sm LEFT JOIN users u ON u.id=sm.user_id ORDER BY sm.submitted_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Reports — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>System Reports</h1>
        </div>
        <div class="content-area">

            <div class="stat-grid">
                <?php foreach ($stats as $label => $val): ?>
                <div class="stat-card">
                    <div class="stat-label"><?= $label ?></div>
                    <div class="stat-value"><?= $val ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="two-col-grid">
                <div class="card">
                    <div class="card-header"><h3>Staff by Position</h3></div>
                    <div class="card-body" style="padding:0;">
                        <table>
                            <thead><tr><th>Position</th><th>Count</th></tr></thead>
                            <tbody>
                                <?php foreach ($by_position as $b): ?>
                                <tr><td><?= htmlspecialchars($b['position']) ?></td><td style="font-weight:700;color:var(--red);"><?= $b['c'] ?></td></tr>
                                <?php endforeach; ?>
                                <?php if (empty($by_position)): ?><tr><td colspan="2" style="text-align:center;color:var(--gray);padding:16px;">No data yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3>Patients by Blood Type</h3></div>
                    <div class="card-body" style="padding:0;">
                        <table>
                            <thead><tr><th>Blood Type</th><th>Patients</th></tr></thead>
                            <tbody>
                                <?php foreach ($by_blood as $b): ?>
                                <tr><td style="font-weight:700;color:var(--red);font-size:16px;"><?= htmlspecialchars($b['blood_type']) ?></td><td style="font-weight:700;"><?= $b['c'] ?></td></tr>
                                <?php endforeach; ?>
                                <?php if (empty($by_blood)): ?><tr><td colspan="2" style="text-align:center;color:var(--gray);padding:16px;">No data yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h3>Medical Records — Last 6 Months</h3></div>
                <div class="card-body">
                    <?php if (empty($monthly)): ?>
                        <p style="color:var(--gray);text-align:center;">No records in this period.</p>
                    <?php else: ?>
                        <div style="display:flex;align-items:flex-end;gap:10px;height:140px;padding-top:20px;">
                            <?php
                            $max = max(array_column($monthly,'c'));
                            foreach ($monthly as $m):
                                $h = $max > 0 ? max(round(($m['c']/$max)*110), 4) : 4;
                            ?>
                            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
                                <span style="font-size:12px;font-weight:700;"><?= $m['c'] ?></span>
                                <div style="width:100%;height:<?= $h ?>px;background:var(--red);border-radius:4px 4px 0 0;"></div>
                                <span style="font-size:11px;color:var(--gray);white-space:nowrap;"><?= htmlspecialchars($m['month']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Support Messages</h3></div>
                <div class="card-body" style="padding:0;overflow-x:auto;">
                    <table>
                        <thead><tr><th>User</th><th>Category</th><th>Subject</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($support as $s): ?>
                            <tr>
                                <td style="font-size:13px;"><?= htmlspecialchars($s['full_name'] ?? 'Guest') ?></td>
                                <td><span class="badge badge-pending"><?= htmlspecialchars($s['category']) ?></span></td>
                                <td style="font-size:13px;"><?= htmlspecialchars(substr($s['subject']??'',0,40)) ?></td>
                                <td><span class="badge badge-<?= $s['status']==='open'?'pending':'active' ?>"><?= $s['status'] ?></span></td>
                                <td style="font-size:12px;white-space:nowrap;"><?= date('d M Y', strtotime($s['submitted_at'])) ?></td>
                                <td>
                                    <?php if ($s['status']==='open'): ?>
                                    <a href="?resolve=<?= $s['id'] ?>" class="btn btn-outline btn-sm" data-confirm="Mark as resolved?">Resolve</a>
                                    <?php else: ?><span style="font-size:12px;color:green;">✔ Done</span><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($support)): ?>
                            <tr><td colspan="6" style="text-align:center;color:var(--gray);padding:20px;">No messages.</td></tr>
                            <?php endif; ?>
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
