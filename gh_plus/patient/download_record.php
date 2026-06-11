<?php
// patient/download_record.php
// Generates a clean printable page → browser saves as PDF
require_once '../includes/config.php';
requireLogin('patient');

$uid = $_SESSION['user_id'];
$rid = (int)($_GET['id'] ?? 0);

if (!$rid) redirect('history.php');

// Fetch record — must belong to this patient
$record = $conn->query("
    SELECT r.*,
           d.full_name   AS doctor_name,
           sd.position   AS doctor_position,
           sd.hospital,
           sd.department,
           sd.slmc_no,
           p.full_name   AS patient_name,
           p.nic         AS patient_nic,
           p.date_of_birth,
           p.gender,
           p.blood_type,
           p.city,
           p.email,
           p.phone,
           pd.diseases,
           pd.description AS patient_notes
    FROM medical_records r
    JOIN users d  ON d.id  = r.doctor_id
    JOIN users p  ON p.id  = r.patient_id
    LEFT JOIN staff_details   sd ON sd.user_id = r.doctor_id
    LEFT JOIN patient_details pd ON pd.user_id = r.patient_id
    WHERE r.id = $rid AND r.patient_id = $uid
")->fetch_assoc();

if (!$record) {
    echo '<p style="padding:2rem;color:red;font-family:sans-serif;">Record not found or access denied.</p>';
    exit;
}

// Attached documents
$docs = $conn->query("SELECT * FROM medical_documents WHERE record_id = $rid ORDER BY uploaded_at ASC")->fetch_all(MYSQLI_ASSOC);

// Age calc
$age = '—';
if ($record['date_of_birth']) {
    $age = (new DateTime($record['date_of_birth']))->diff(new DateTime())->y . ' yrs';
}

$generated = date('d F Y, h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medical Record — <?= htmlspecialchars($record['patient_name']) ?> — <?= $record['visit_date'] ?></title>
<style>
/* ── Screen styles ── */
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    background: #f0f0f0;
    color: #1a1a1a;
}

.page-wrapper {
    max-width: 820px;
    margin: 24px auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.12);
    overflow: hidden;
}

/* ── Print toolbar (hidden in print) ── */
.print-toolbar {
    background: #1a1a1a;
    padding: 12px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.print-toolbar span { color: #ccc; font-size: 13px; }
.btn-group { display: flex; gap: 8px; }
.btn-pdf {
    background: #C8102E;
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}
.btn-pdf:hover { background: #a00c24; }
.btn-back {
    background: transparent;
    color: #ccc;
    border: 1px solid #555;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
}
.btn-back:hover { background: #333; color: #fff; }

/* ── Document body ── */
.doc-body { padding: 32px 36px; }

/* Header */
.doc-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 3px solid #C8102E;
    gap: 16px;
}
.doc-header-left { display: flex; align-items: center; gap: 14px; }
.doc-header-left img { width: 60px; height: 60px; object-fit: contain; }
.hospital-name h1 { font-size: 18px; font-weight: 700; color: #1a1a1a; }
.hospital-name p  { font-size: 11px; color: #666; margin-top: 2px; }
.doc-header-right { text-align: right; }
.record-id  { font-size: 22px; font-weight: 700; color: #C8102E; }
.record-sub { font-size: 11px; color: #888; margin-top: 2px; }

/* Section titles */
.section-title {
    background: #1a1a1a;
    color: #fff;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border-radius: 4px;
    margin: 20px 0 10px;
}

/* Info grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 8px;
}
.info-grid.two-col { grid-template-columns: repeat(2, 1fr); }
.info-grid.four-col{ grid-template-columns: repeat(4, 1fr); }

.info-cell {
    background: #f9f9f9;
    border: 1px solid #e8e8e8;
    border-radius: 5px;
    padding: 8px 10px;
}
.info-cell .lbl {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #888;
    margin-bottom: 3px;
}
.info-cell .val {
    font-size: 13px;
    font-weight: 600;
    color: #1a1a1a;
}
.info-cell .val.red { color: #C8102E; font-size: 16px; }

/* Clinical content boxes */
.content-box {
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    padding: 14px;
    margin-bottom: 12px;
    line-height: 1.7;
    white-space: pre-wrap;
    font-size: 13px;
    color: #1a1a1a;
}
.content-box.rx {
    border-left: 4px solid #C8102E;
    background: #fff8f8;
}
.content-box.notes {
    border-left: 4px solid #2e7d32;
    background: #f8fff8;
}

/* Documents table */
.docs-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.docs-table th {
    background: #1a1a1a;
    color: #fff;
    text-align: left;
    padding: 7px 10px;
    font-size: 11px;
    text-transform: uppercase;
}
.docs-table td {
    padding: 7px 10px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 12px;
    vertical-align: middle;
}
.docs-table tr:last-child td { border-bottom: none; }
.file-type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}
.ft-image { background: #e3f2fd; color: #0d47a1; }
.ft-pdf   { background: #fde8ec; color: #C8102E; }
.ft-audio { background: #fff3e0; color: #e65100; }
.ft-video { background: #f3e5f5; color: #6a1b9a; }
.ft-other { background: #f5f5f5; color: #555; }

/* Footer */
.doc-footer {
    margin-top: 28px;
    padding-top: 14px;
    border-top: 1px solid #e8e8e8;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
}
.doc-footer .gen-info { font-size: 10px; color: #999; line-height: 1.6; }
.signature-box {
    text-align: center;
    min-width: 160px;
}
.signature-line {
    border-top: 1px solid #1a1a1a;
    margin-bottom: 4px;
    width: 160px;
}
.signature-box p { font-size: 11px; color: #555; }

/* Confidential watermark-like bar */
.confidential-bar {
    background: #f9f9f9;
    border-top: 1px solid #e8e8e8;
    text-align: center;
    padding: 6px;
    font-size: 10px;
    color: #aaa;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* ── PRINT STYLES ── */
@media print {
    body { background: #fff; font-size: 12px; }

    .print-toolbar { display: none !important; }

    .page-wrapper {
        margin: 0;
        border-radius: 0;
        box-shadow: none;
        max-width: 100%;
    }

    .doc-body { padding: 16px 20px; }

    /* Force page break avoidance inside sections */
    .info-grid, .content-box, .docs-table { page-break-inside: avoid; }

    /* Keep header together */
    .doc-header { page-break-after: avoid; }

    /* Ensure links show as text, not blue underlined */
    a { color: #1a1a1a !important; text-decoration: none !important; }

    /* Slightly smaller section titles in print */
    .section-title { font-size: 10px; }

    @page {
        size: A4;
        margin: 12mm 14mm;
    }
}

@media (max-width: 600px) {
    .doc-body { padding: 16px; }
    .info-grid, .info-grid.two-col, .info-grid.four-col { grid-template-columns: 1fr 1fr; }
    .doc-header { flex-direction: column; }
    .doc-header-right { text-align: left; }
    .print-toolbar { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

<div class="page-wrapper">

    <!-- Toolbar (hidden on print) -->
    <div class="print-toolbar">
        <span>📄 Medical Record — <?= htmlspecialchars($record['patient_name']) ?></span>
        <div class="btn-group">
            <a href="history.php" class="btn-back">← Back</a>
            <button class="btn-pdf" onclick="window.print()">
                ⬇ Download as PDF
            </button>
        </div>
    </div>

    <!-- Document -->
    <div class="doc-body">

        <!-- Header -->
        <div class="doc-header">
            <div class="doc-header-left">
                <img src="../assets/logo.png" alt="GH+">
                <div class="hospital-name">
                    <h1>Government Hospital Plus</h1>
                    <p><?= htmlspecialchars($record['hospital'] ?? 'Sri Lanka Government Health System') ?></p>
                    <p style="margin-top:2px;color:#C8102E;font-weight:600;"><?= htmlspecialchars($record['department'] ?? '') ?></p>
                </div>
            </div>
            <div class="doc-header-right">
                <div class="record-id">Record #<?= str_pad($record['id'], 5, '0', STR_PAD_LEFT) ?></div>
                <div class="record-sub">Medical Record</div>
                <div style="margin-top:6px;font-size:12px;font-weight:600;"><?= date('d F Y', strtotime($record['visit_date'])) ?></div>
                <div style="font-size:11px;color:#888;">Visit Date</div>
            </div>
        </div>

        <!-- Patient Details -->
        <div class="section-title">Patient Information</div>
        <div class="info-grid four-col">
            <div class="info-cell">
                <div class="lbl">Full Name</div>
                <div class="val"><?= htmlspecialchars($record['patient_name']) ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">NIC Number</div>
                <div class="val" style="font-family:monospace;"><?= htmlspecialchars($record['patient_nic']) ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">Date of Birth</div>
                <div class="val"><?= htmlspecialchars($record['date_of_birth'] ?? '—') ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">Age</div>
                <div class="val"><?= $age ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">Gender</div>
                <div class="val"><?= ucfirst($record['gender'] ?? '—') ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">Blood Type</div>
                <div class="val red"><?= htmlspecialchars($record['blood_type'] ?? '—') ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">City</div>
                <div class="val"><?= htmlspecialchars($record['city'] ?? '—') ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">Contact</div>
                <div class="val" style="font-size:12px;"><?= htmlspecialchars($record['phone'] ?? '—') ?></div>
            </div>
        </div>

        <?php if ($record['diseases']): ?>
        <div class="info-grid two-col" style="margin-top:8px;">
            <div class="info-cell">
                <div class="lbl">Known Medical Conditions</div>
                <div class="val" style="font-size:12px;color:#C8102E;"><?= htmlspecialchars($record['diseases']) ?></div>
            </div>
            <?php if ($record['patient_notes']): ?>
            <div class="info-cell">
                <div class="lbl">Allergies / Notes</div>
                <div class="val" style="font-size:12px;"><?= htmlspecialchars($record['patient_notes']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Attending Doctor -->
        <div class="section-title">Attending Doctor</div>
        <div class="info-grid">
            <div class="info-cell">
                <div class="lbl">Doctor Name</div>
                <div class="val"><?= htmlspecialchars($record['doctor_name']) ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">Position</div>
                <div class="val"><?= htmlspecialchars($record['doctor_position'] ?? '—') ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">SLMC Registration</div>
                <div class="val" style="font-family:monospace;"><?= htmlspecialchars($record['slmc_no'] ?? '—') ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">Hospital</div>
                <div class="val" style="font-size:12px;"><?= htmlspecialchars($record['hospital'] ?? '—') ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">Department</div>
                <div class="val"><?= htmlspecialchars($record['department'] ?? '—') ?></div>
            </div>
            <div class="info-cell">
                <div class="lbl">Visit Date</div>
                <div class="val"><?= date('d F Y', strtotime($record['visit_date'])) ?></div>
            </div>
        </div>

        <!-- Diagnosis -->
        <div class="section-title">Diagnosis</div>
        <div class="content-box"><?= htmlspecialchars($record['diagnosis']) ?></div>

        <!-- Prescription -->
        <div class="section-title">Prescription</div>
        <?php if (!empty($record['prescription'])): ?>
        <div class="content-box rx">💊 <?= htmlspecialchars($record['prescription']) ?></div>
        <?php else: ?>
        <div class="content-box" style="color:#888;font-style:italic;">No prescription issued for this visit.</div>
        <?php endif; ?>

        <!-- Notes for patient -->
        <?php if (!empty($record['notes_for_patient'])): ?>
        <div class="section-title">Doctor's Instructions</div>
        <div class="content-box notes">📝 <?= htmlspecialchars($record['notes_for_patient']) ?></div>
        <?php endif; ?>

        <!-- Documents list -->
        <div class="section-title">Attached Documents (<?= count($docs) ?>)</div>
        <?php if (empty($docs)): ?>
            <p style="color:#888;font-style:italic;padding:8px 0;">No documents attached to this record.</p>
        <?php else: ?>
            <table class="docs-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>File Name</th>
                        <th>Type</th>
                        <th>Uploaded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docs as $i => $doc): ?>
                    <tr>
                        <td style="color:#888;"><?= $i + 1 ?></td>
                        <td>
                            <?php
                            $icons = ['image'=>'🖼','pdf'=>'📄','audio'=>'🔊','video'=>'🎬','other'=>'📎'];
                            echo ($icons[$doc['file_type']] ?? '📎') . ' ';
                            echo htmlspecialchars($doc['file_name']);
                            ?>
                        </td>
                        <td>
                            <?php
                            $ftClass = ['image'=>'ft-image','pdf'=>'ft-pdf','audio'=>'ft-audio','video'=>'ft-video','other'=>'ft-other'];
                            $cls = $ftClass[$doc['file_type']] ?? 'ft-other';
                            ?>
                            <span class="file-type-badge <?= $cls ?>"><?= htmlspecialchars($doc['file_type']) ?></span>
                        </td>
                        <td style="color:#666;"><?= date('d M Y', strtotime($doc['uploaded_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="font-size:10px;color:#aaa;margin-top:6px;">* Actual files are stored in the GH+ system and can be downloaded from the Documents section.</p>
        <?php endif; ?>

        <!-- Footer -->
        <div class="doc-footer">
            <div class="gen-info">
                <p><strong>Government Hospital Plus (GH+)</strong></p>
                <p>Sri Lanka National Health Records System</p>
                <p>Generated: <?= $generated ?></p>
                <p>This is a computer-generated document and is valid without a physical signature.</p>
            </div>
            <div class="signature-box">
                <div style="height:36px;"></div>
                <div class="signature-line"></div>
                <p><?= htmlspecialchars($record['doctor_name']) ?></p>
                <p><?= htmlspecialchars($record['doctor_position'] ?? '') ?></p>
                <p style="color:#C8102E;"><?= htmlspecialchars($record['department'] ?? '') ?></p>
            </div>
        </div>

    </div><!-- /doc-body -->

    <div class="confidential-bar">
        Confidential Medical Record — GH+ Government Hospital Plus — Sri Lanka
    </div>

</div><!-- /page-wrapper -->

<script>
// Auto-open print dialog when ?print=1 is in URL
const params = new URLSearchParams(window.location.search);
if (params.get('print') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
}
</script>

</body>
</html>
