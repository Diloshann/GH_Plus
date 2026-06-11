<?php
// patient/history.php
require_once '../includes/config.php';
requireLogin('patient');

$uid = $_SESSION['user_id'];
$active_page = 'history';

// Filters
$filter_from = sanitize($conn, $_GET['from'] ?? '');
$filter_to   = sanitize($conn, $_GET['to'] ?? '');

$where = "r.patient_id = $uid";
if ($filter_from) $where .= " AND r.visit_date >= '$filter_from'";
if ($filter_to)   $where .= " AND r.visit_date <= '$filter_to'";

$records = $conn->query("SELECT r.*, u.full_name AS doctor_name, sd.hospital, sd.department
    FROM medical_records r
    JOIN users u ON u.id = r.doctor_id
    LEFT JOIN staff_details sd ON sd.user_id = r.doctor_id
    WHERE $where
    ORDER BY r.visit_date DESC")->fetch_all(MYSQLI_ASSOC);

// Selected record
$selected = null;
$selDocs   = [];
if (isset($_GET['record'])) {
    $rid = (int)$_GET['record'];
    $selected = $conn->query("SELECT r.*, u.full_name AS doctor_name, sd.hospital, sd.department, sd.position
        FROM medical_records r
        JOIN users u ON u.id=r.doctor_id
        LEFT JOIN staff_details sd ON sd.user_id=r.doctor_id
        WHERE r.id=$rid AND r.patient_id=$uid")->fetch_assoc();
    if ($selected) {
        $selDocs = $conn->query("SELECT * FROM medical_documents WHERE record_id=$rid")->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medical History — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Medical History</h1>
        </div>
        <div class="content-area">
            <?php if ($selected): ?>
                <!-- Record detail view -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                <a href="history.php" style="font-size:13px;color:var(--red);">← Back to Timeline</a>
                <a href="record_pdf.php?id=<?= $selected['id'] ?>" target="_blank" class="btn btn-danger btn-sm" style="display:inline-flex;align-items:center;gap:6px;">📄 Download as PDF</a>
                </div>
                <div class="card">
                    <div class="card-header" style="background:var(--black);color:#fff;border-radius:8px 8px 0 0;">
                        <h3 style="color:#fff;">Visit on <?= htmlspecialchars($selected['visit_date']) ?></h3>
                        <span style="font-size:12px;color:#aaa;"><?= htmlspecialchars($selected['hospital'] ?? '') ?> — <?= htmlspecialchars($selected['department'] ?? '') ?></span>
                    </div>
                    <div class="card-body">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div>
                                <label style="font-size:11px;text-transform:uppercase;color:var(--gray);">Attending Doctor</label>
                                <p style="font-weight:600;"><?= htmlspecialchars($selected['doctor_name']) ?> (<?= htmlspecialchars($selected['position'] ?? '') ?>)</p>
                            </div>
                            <div>
                                <label style="font-size:11px;text-transform:uppercase;color:var(--gray);">Hospital</label>
                                <p><?= htmlspecialchars($selected['hospital'] ?? '—') ?></p>
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="font-size:11px;text-transform:uppercase;color:var(--gray);">Diagnosis</label>
                            <p style="margin-top:4px;line-height:1.6;"><?= nl2br(htmlspecialchars($selected['diagnosis'])) ?></p>
                        </div>

                        <?php if ($selected['prescription']): ?>
                        <div style="margin-bottom:16px;background:#fff8f8;border-left:3px solid var(--red);padding:12px;border-radius:0 6px 6px 0;">
                            <label style="font-size:11px;text-transform:uppercase;color:var(--gray);">Prescription</label>
                            <p style="margin-top:4px;line-height:1.6;"><?= nl2br(htmlspecialchars($selected['prescription'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Download PDF button -->
                        <div style="margin-top:20px;padding-top:16px;border-top:1px solid #f0f0f0;display:flex;gap:10px;flex-wrap:wrap;">
                            <a href="download_record.php?id=<?= $selected['id'] ?>"
                               target="_blank"
                               class="btn btn-danger"
                               style="display:inline-flex;align-items:center;gap:6px;">
                               ⬇ Download as PDF
                            </a>
                            <a href="download_record.php?id=<?= $selected['id'] ?>&print=1"
                               target="_blank"
                               class="btn btn-dark"
                               style="display:inline-flex;align-items:center;gap:6px;">
                               🖨 Print Record
                            </a>
                        </div>

                        <?php if ($selected['notes_for_patient']): ?>
                        <div style="margin-bottom:16px;">
                            <label style="font-size:11px;text-transform:uppercase;color:var(--gray);">Doctor's Notes for You</label>
                            <p style="margin-top:4px;"><?= nl2br(htmlspecialchars($selected['notes_for_patient'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($selDocs)): ?>
                        <div>
                            <label style="font-size:11px;text-transform:uppercase;color:var(--gray);display:block;margin-bottom:8px;">Attached Documents</label>
                            <div class="docs-grid">
                                <?php foreach ($selDocs as $d): ?>
                                <div class="doc-thumb">
                                    <span class="doc-icon">
                                        <?= $d['file_type']==='image' ? '🖼' : ($d['file_type']==='pdf' ? '📄' : ($d['file_type']==='audio' ? '🔊' : '🎬')) ?>
                                    </span>
                                    <a href="../uploads/<?= htmlspecialchars($d['file_name']) ?>" target="_blank"><?= htmlspecialchars($d['file_name']) ?></a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- Timeline filter -->
                <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:flex-end;">
                    <div class="form-group" style="margin:0;">
                        <label>From Date</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($filter_from) ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>To Date</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($filter_to) ?>">
                    </div>
                    <button type="submit" class="btn btn-dark" style="padding:9px 18px;">Filter</button>
                    <a href="history.php" class="btn btn-outline" style="padding:9px 18px;">Reset</a>
                </form>

                <?php if (empty($records)): ?>
                    <div style="text-align:center;padding:3rem;color:var(--gray);">
                        <div style="font-size:48px;margin-bottom:1rem;">📋</div>
                        <p>No medical records found.</p>
                    </div>
                <?php else: ?>
                    <p style="color:var(--gray);font-size:13px;margin-bottom:16px;"><?= count($records) ?> record(s) found</p>
                    <div class="timeline">
                        <?php foreach ($records as $r): ?>
                        <div class="timeline-item">
                            <div class="timeline-date">🗓 <?= htmlspecialchars($r['visit_date']) ?> &nbsp;|&nbsp; <?= htmlspecialchars($r['hospital'] ?? '') ?></div>
                            <div class="timeline-title">Dr. <?= htmlspecialchars($r['doctor_name']) ?> — <?= htmlspecialchars($r['department'] ?? '') ?></div>
                            <div class="timeline-body"><?= htmlspecialchars(substr($r['diagnosis'], 0, 120)) ?>...</div>
                            <?php if ($r['prescription']): ?>
                                <div style="margin-top:6px;font-size:12px;color:var(--red);">💊 Prescription issued</div>
                            <?php endif; ?>
                            <div style="margin-top:10px;">
                                <a href="history.php?record=<?= $r['id'] ?>" class="btn btn-outline btn-sm">View Full Record</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
