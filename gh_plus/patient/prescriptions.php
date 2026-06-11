<?php
// patient/prescriptions.php
require_once '../includes/config.php';
requireLogin('patient');

$uid = $_SESSION['user_id'];
$active_page = 'prescriptions';

$records = $conn->query("SELECT r.*, u.full_name AS doctor_name, sd.position, sd.hospital
    FROM medical_records r
    JOIN users u ON u.id = r.doctor_id
    LEFT JOIN staff_details sd ON sd.user_id = r.doctor_id
    WHERE r.patient_id = $uid AND r.prescription IS NOT NULL AND r.prescription != ''
    ORDER BY r.visit_date DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prescriptions — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button><h1>My Prescriptions</h1></div>
        <div class="content-area">

            <?php if (empty($records)): ?>
                <div style="text-align:center;padding:3rem;color:var(--gray);">
                    <div style="font-size:48px;margin-bottom:1rem;">💊</div>
                    <p>No prescriptions on record yet.</p>
                </div>
            <?php else: ?>
                <p style="color:var(--gray);font-size:13px;margin-bottom:16px;"><?= count($records) ?> prescription(s) found</p>
                <?php foreach ($records as $r): ?>
                <div class="card" style="margin-bottom:16px;">
                    <div class="card-header">
                        <div>
                            <h3>🗓 <?= htmlspecialchars($r['visit_date']) ?></h3>
                            <p style="font-size:12px;color:var(--gray);">
                                Dr. <?= htmlspecialchars($r['doctor_name']) ?> (<?= htmlspecialchars($r['position'] ?? '') ?>)
                                &nbsp;|&nbsp; <?= htmlspecialchars($r['hospital'] ?? '') ?>
                            </p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom:12px;">
                            <label style="font-size:11px;text-transform:uppercase;color:var(--gray);">Diagnosis</label>
                            <p style="margin-top:4px;"><?= nl2br(htmlspecialchars($r['diagnosis'])) ?></p>
                        </div>
                        <div style="background:#fff8f8;border-left:3px solid var(--red);padding:12px;border-radius:0 6px 6px 0;">
                            <label style="font-size:11px;text-transform:uppercase;color:var(--gray);">Prescription</label>
                            <p style="margin-top:6px;line-height:1.7;white-space:pre-line;"><?= htmlspecialchars($r['prescription']) ?></p>
                        </div>
                        <?php if ($r['notes_for_patient']): ?>
                        <div style="margin-top:10px;">
                            <label style="font-size:11px;text-transform:uppercase;color:var(--gray);">Doctor's Notes</label>
                            <p style="margin-top:4px;font-size:13px;color:var(--gray-dark);"><?= nl2br(htmlspecialchars($r['notes_for_patient'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
