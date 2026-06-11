<?php
// patient/history.php
require_once '../includes/config.php';
requireLogin('patient');

$uid         = $_SESSION['user_id'];
$active_page = 'history';

$filter_from = sanitize($conn, $_GET['from'] ?? '');
$filter_to   = sanitize($conn, $_GET['to']   ?? '');

$where = "r.patient_id = $uid";
if ($filter_from) $where .= " AND r.visit_date >= '$filter_from'";
if ($filter_to)   $where .= " AND r.visit_date <= '$filter_to'";

$records = $conn->query("
    SELECT r.*, u.full_name AS doctor_name, sd.hospital, sd.department
    FROM   medical_records r
    JOIN   users u ON u.id = r.doctor_id
    LEFT JOIN staff_details sd ON sd.user_id = r.doctor_id
    WHERE  $where
    ORDER  BY r.visit_date DESC
")->fetch_all(MYSQLI_ASSOC);

// Selected record detail
$selected = null;
$selDocs  = [];
if (isset($_GET['record'])) {
    $rid = (int)$_GET['record'];
    $selected = $conn->query("
        SELECT r.*, u.full_name AS doctor_name, sd.hospital, sd.department, sd.position
        FROM   medical_records r
        JOIN   users u ON u.id = r.doctor_id
        LEFT JOIN staff_details sd ON sd.user_id = r.doctor_id
        WHERE  r.id = $rid AND r.patient_id = $uid
    ")->fetch_assoc();
    if ($selected) {
        $selDocs = $conn->query("SELECT * FROM medical_documents WHERE record_id = $rid")->fetch_all(MYSQLI_ASSOC);
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
                <!-- ── Record detail view ── -->
                <div style="display:flex;justify-content:space-between;align-items:center;
                            margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <a href="history.php" style="font-size:13px;color:var(--red);">← Back to Timeline</a>
                    <a href="record_pdf.php?id=<?= $selected['id'] ?>" target="_blank"
                       class="btn btn-danger btn-sm"
                       style="display:inline-flex;align-items:center;gap:6px;">
                        📄 Download as PDF
                    </a>
                </div>

                <div class="card">
                    <div class="card-header"
                         style="background:var(--black);border-radius:8px 8px 0 0;">
                        <h3 style="color:#fff;">
                            Visit on <?= htmlspecialchars($selected['visit_date']) ?>
                        </h3>
                        <span style="font-size:12px;color:#aaa;">
                            <?= htmlspecialchars($selected['hospital'] ?? '') ?>
                            <?php if (!empty($selected['department'])): ?>
                                — <?= htmlspecialchars($selected['department']) ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="card-body">

                        <!-- Doctor & Hospital -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;
                                    margin-bottom:16px;">
                            <div>
                                <label style="font-size:11px;text-transform:uppercase;
                                              color:var(--gray);">Attending Doctor</label>
                                <p style="font-weight:600;margin-top:3px;">
                                    <?= htmlspecialchars($selected['doctor_name']) ?>
                                    <?php if (!empty($selected['position'])): ?>
                                        <span style="font-weight:400;color:var(--gray);
                                                     font-size:12px;">
                                            (<?= htmlspecialchars($selected['position']) ?>)
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <label style="font-size:11px;text-transform:uppercase;
                                              color:var(--gray);">Hospital</label>
                                <p style="margin-top:3px;">
                                    <?= htmlspecialchars($selected['hospital'] ?? '—') ?>
                                </p>
                            </div>
                        </div>

                        <!-- Diagnosis -->
                        <div style="margin-bottom:16px;">
                            <label style="font-size:11px;text-transform:uppercase;
                                          color:var(--gray);">Diagnosis</label>
                            <p style="margin-top:4px;line-height:1.7;">
                                <?= nl2br(htmlspecialchars($selected['diagnosis'])) ?>
                            </p>
                        </div>

                        <!-- Prescription -->
                        <?php if (!empty($selected['prescription'])): ?>
                        <div style="margin-bottom:16px;background:#fff8f8;
                                    border-left:3px solid var(--red);
                                    padding:12px;border-radius:0 6px 6px 0;">
                            <label style="font-size:11px;text-transform:uppercase;
                                          color:var(--gray);">Prescription</label>
                            <p style="margin-top:4px;line-height:1.7;">
                                <?= nl2br(htmlspecialchars($selected['prescription'])) ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <!-- Doctor's Notes -->
                        <?php if (!empty($selected['notes_for_patient'])): ?>
                        <div style="margin-bottom:16px;background:#f0f4ff;
                                    border-left:3px solid #3b6dd8;
                                    padding:12px;border-radius:0 6px 6px 0;">
                            <label style="font-size:11px;text-transform:uppercase;
                                          color:var(--gray);">Doctor's Notes for You</label>
                            <p style="margin-top:4px;line-height:1.7;">
                                <?= nl2br(htmlspecialchars($selected['notes_for_patient'])) ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <!-- Attached Documents -->
                        <?php if (!empty($selDocs)): ?>
                        <div>
                            <label style="font-size:11px;text-transform:uppercase;
                                          color:var(--gray);display:block;
                                          margin-bottom:8px;">
                                Attached Documents (<?= count($selDocs) ?>)
                            </label>
                            <div class="docs-grid">
                                <?php foreach ($selDocs as $d):
                                    $icons = ['image'=>'🖼','pdf'=>'📄',
                                              'audio'=>'🔊','video'=>'🎬','other'=>'📎'];
                                    $icon  = $icons[$d['file_type']] ?? '📎';
                                ?>
                                <div class="doc-thumb">
                                    <span class="doc-icon"><?= $icon ?></span>
                                    <a href="../uploads/<?= htmlspecialchars($d['file_name']) ?>"
                                       target="_blank">
                                        <?= htmlspecialchars(substr($d['file_name'],0,22)) ?>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div><!-- /card-body -->
                </div><!-- /card -->

            <?php else: ?>
                <!-- ── Timeline list view ── -->
                <form method="GET"
                      style="display:flex;gap:10px;margin-bottom:20px;
                             flex-wrap:wrap;align-items:flex-end;">
                    <div class="form-group" style="margin:0;">
                        <label>From Date</label>
                        <input type="date" name="from"
                               value="<?= htmlspecialchars($filter_from) ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>To Date</label>
                        <input type="date" name="to"
                               value="<?= htmlspecialchars($filter_to) ?>">
                    </div>
                    <button type="submit" class="btn btn-dark"
                            style="padding:9px 18px;">Filter</button>
                    <a href="history.php" class="btn btn-outline"
                       style="padding:9px 18px;">Reset</a>
                </form>

                <?php if (empty($records)): ?>
                    <div style="text-align:center;padding:3rem;color:var(--gray);">
                        <div style="font-size:48px;margin-bottom:1rem;">📋</div>
                        <p>No medical records found.</p>
                    </div>
                <?php else: ?>
                    <p style="color:var(--gray);font-size:13px;margin-bottom:16px;">
                        <?= count($records) ?> record(s) found
                    </p>
                    <div class="timeline">
                        <?php foreach ($records as $r): ?>
                        <div class="timeline-item">
                            <div class="timeline-date">
                                🗓 <?= htmlspecialchars($r['visit_date']) ?>
                                <?php if (!empty($r['hospital'])): ?>
                                    &nbsp;|&nbsp; <?= htmlspecialchars($r['hospital']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-title">
                                Dr. <?= htmlspecialchars($r['doctor_name']) ?>
                                <?php if (!empty($r['department'])): ?>
                                    — <?= htmlspecialchars($r['department']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-body">
                                <?= htmlspecialchars(substr($r['diagnosis'], 0, 120)) ?>...
                            </div>
                            <?php if (!empty($r['prescription'])): ?>
                            <div style="margin-top:5px;font-size:12px;color:var(--red);">
                                💊 Prescription issued
                            </div>
                            <?php endif; ?>
                            <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
                                <a href="history.php?record=<?= $r['id'] ?>"
                                   class="btn btn-outline btn-sm">View Full Record</a>
                                <a href="record_pdf.php?id=<?= $r['id'] ?>"
                                   target="_blank"
                                   class="btn btn-danger btn-sm">📄 PDF</a>
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
