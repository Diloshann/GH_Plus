<?php
// admin/approvals.php
require_once '../includes/config.php';
requireLogin('admin');

$active_page = 'approvals';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid    = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($uid && $action === 'approve') {
        $conn->query("UPDATE users SET status='active' WHERE id=$uid AND status='pending'");
        $success = 'Account approved successfully.';
    } elseif ($uid && $action === 'reject') {
        $conn->query("UPDATE users SET status='rejected' WHERE id=$uid AND status='pending'");
        $success = 'Account rejected.';
    }
}

$tab = $_GET['tab'] ?? 'patients';

$patients = $conn->query("SELECT u.*, p.diseases, p.description FROM users u LEFT JOIN patient_details p ON p.user_id=u.id WHERE u.role='patient' AND u.status='pending' ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$staff    = $conn->query("SELECT u.*, sd.position, sd.slmc_no, sd.hospital, sd.department FROM users u LEFT JOIN staff_details sd ON sd.user_id=u.id WHERE u.role='doctor' AND u.status='pending' ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pending Approvals — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Pending Approvals</h1>
            <div class="topbar-right">
                <span class="badge badge-pending"><?= count($patients)+count($staff) ?> pending</span>
            </div>
        </div>
        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <div class="tabs">
                <button class="tab-btn <?= $tab==='patients'?'active':'' ?>" data-group="appr" data-tab="tab-patients">Patient Requests (<?= count($patients) ?>)</button>
                <button class="tab-btn <?= $tab==='staff'?'active':'' ?>"    data-group="appr" data-tab="tab-staff">Staff Requests (<?= count($staff) ?>)</button>
            </div>

            <!-- Patient requests -->
            <div id="tab-patients" class="tab-content <?= $tab==='patients'?'active':'' ?>" data-group="appr">
                <?php if (empty($patients)): ?>
                    <div style="text-align:center;padding:2rem;color:var(--gray);">✅ No pending patient requests.</div>
                <?php else: ?>
                    <?php foreach ($patients as $p): ?>
                    <div class="card" style="margin-bottom:16px;">
                        <div class="card-header">
                            <div>
                                <h3><?= htmlspecialchars($p['full_name']) ?></h3>
                                <p style="font-size:12px;color:var(--gray);">NIC: <?= htmlspecialchars($p['nic']) ?> &nbsp;|&nbsp; Applied: <?= date('d M Y H:i', strtotime($p['created_at'])) ?></p>
                            </div>
                            <span class="badge badge-pending">Pending</span>
                        </div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px;">
                                <?php foreach (['DOB'=>$p['date_of_birth'],'Gender'=>ucfirst($p['gender']??''),'City'=>$p['city'],'Blood'=>$p['blood_type']] as $k=>$v): ?>
                                <div><label style="font-size:11px;color:var(--gray);text-transform:uppercase;"><?= $k ?></label><p style="font-weight:600;"><?= htmlspecialchars($v??'—') ?></p></div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($p['diseases']): ?><p style="font-size:13px;margin-bottom:8px;">Conditions: <strong><?= htmlspecialchars($p['diseases']) ?></strong></p><?php endif; ?>
                            <?php if ($p['description']): ?><p style="font-size:13px;color:var(--gray);">Notes: <?= htmlspecialchars($p['description']) ?></p><?php endif; ?>
                            <div style="display:flex;gap:10px;margin-top:12px;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-dark" style="background:#2e7d32;">✔ Approve</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger" data-confirm="Reject this patient request? This cannot be undone.">✘ Reject</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Staff requests -->
            <div id="tab-staff" class="tab-content <?= $tab==='staff'?'active':'' ?>" data-group="appr">
                <?php if (empty($staff)): ?>
                    <div style="text-align:center;padding:2rem;color:var(--gray);">✅ No pending staff requests.</div>
                <?php else: ?>
                    <?php foreach ($staff as $s): ?>
                    <div class="card" style="margin-bottom:16px;">
                        <div class="card-header">
                            <div>
                                <h3><?= htmlspecialchars($s['full_name']) ?></h3>
                                <p style="font-size:12px;color:var(--gray);">NIC: <?= htmlspecialchars($s['nic']) ?> &nbsp;|&nbsp; Applied: <?= date('d M Y H:i', strtotime($s['created_at'])) ?></p>
                            </div>
                            <span class="badge badge-pending">Pending</span>
                        </div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px;">
                                <?php foreach (['Position'=>$s['position'],'SLMC No'=>$s['slmc_no'],'Hospital'=>$s['hospital'],'Department'=>$s['department']] as $k=>$v): ?>
                                <div><label style="font-size:11px;color:var(--gray);text-transform:uppercase;"><?= $k ?></label><p style="font-weight:600;"><?= htmlspecialchars($v??'—') ?></p></div>
                                <?php endforeach; ?>
                            </div>
                            <div style="display:flex;gap:10px;margin-top:12px;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-dark" style="background:#2e7d32;">✔ Approve</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger" data-confirm="Reject this staff request?">✘ Reject</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
