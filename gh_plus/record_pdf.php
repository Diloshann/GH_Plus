<?php
// patient/record_pdf.php
// Opens a clean, printable page — browser saves it as PDF via Ctrl+P / Print dialog
require_once '../includes/config.php';
requireLogin('patient');

$uid = $_SESSION['user_id'];
$rid = (int)($_GET['id'] ?? 0);

if (!$rid) redirect('history.php');

// Fetch record — patient can only download their OWN records
$record = $conn->query("
    SELECT r.*,
           p.full_name   AS patient_name,
           p.nic         AS patient_nic,
           p.date_of_birth,
           p.gender,
           p.blood_type,
           p.city,
           pd.diseases,
           d.full_name   AS doctor_name,
           sd.position   AS doctor_position,
           sd.hospital,
           sd.department,
           sd.slmc_no
    FROM   medical_records r
    JOIN   users p   ON p.id = r.patient_id
    JOIN   users d   ON d.id = r.doctor_id
    LEFT JOIN patient_details pd ON pd.user_id = p.id
    LEFT JOIN staff_details   sd ON sd.user_id = r.doctor_id
    WHERE  r.id = $rid
      AND  r.patient_id = $uid
      AND  p.status = 'active'
")->fetch_assoc();

if (!$record) {
    die('<p style="font-family:sans-serif;padding:2rem;color:red;">Record not found or access denied.</p>');
}

// Attached files (images only — embed in PDF page)
$docs = $conn->query("SELECT * FROM medical_documents WHERE record_id = $rid ORDER BY uploaded_at ASC")->fetch_all(MYSQLI_ASSOC);

$imageDocs = array_filter($docs, fn($d) => $d['file_type'] === 'image');
$otherDocs = array_filter($docs, fn($d) => $d['file_type'] !== 'image');

$generated = date('d M Y, H:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medical Record — <?= htmlspecialchars($record['patient_name']) ?> — <?= htmlspecialchars($record['visit_date']) ?></title>
<style>
/* ── Base ── */
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1a1a1a;
    background: #f0f0f0;
    padding: 20px;
}

/* ── Print controls bar (hidden when printing) ── */
.controls {
    max-width: 800px;
    margin: 0 auto 16px auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}

.controls a, .controls button {
    padding: 9px 18px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-back  { background: #333; color: #fff; }
.btn-print { background: #C8102E; color: #fff; font-size: 14px; padding: 10px 24px; }
.btn-print:hover { background: #a00c24; }

.controls p { font-size: 12px; color: #666; }

/* ── A4 Page ── */
.page {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.12);
    overflow: hidden;
}

/* ── Header ── */
.page-header {
    background: #1a1a1a;
    color: #fff;
    padding: 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.header-left { display: flex; align-items: center; gap: 14px; }
.header-logo { font-size: 28px; font-weight: 900; color: #fff; }
.header-logo span { color: #C8102E; }
.header-title h1 { font-size: 15px; color: #fff; font-weight: 600; }
.header-title p  { font-size: 11px; color: #aaa; margin-top: 2px; }

.header-right { text-align: right; }
.header-right .record-no { font-size: 18px; font-weight: 700; color: #C8102E; }
.header-right .record-date { font-size: 11px; color: #aaa; margin-top: 2px; }

/* ── Red bar ── */
.red-bar { height: 4px; background: #C8102E; }

/* ── Section label ── */
.section-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #C8102E;
    border-bottom: 1.5px solid #C8102E;
    padding-bottom: 4px;
    margin-bottom: 12px;
}

/* ── Page body padding ── */
.page-body { padding: 24px 28px; }

/* ── Patient & Doctor info grid ── */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}

.info-box { background: #f9f9f9; border-radius: 6px; padding: 14px; }

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #eeeeee;
    gap: 8px;
}
.info-row:last-child { border-bottom: none; }

.info-row .lbl { font-size: 11px; color: #888; white-space: nowrap; }
.info-row .val { font-size: 12px; font-weight: 600; text-align: right; word-break: break-word; }

/* ── Blood type badge ── */
.blood-badge {
    display: inline-block;
    background: #C8102E;
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 4px;
}

/* ── Clinical section ── */
.clinical-section {
    margin-bottom: 20px;
}

.clinical-section p, .clinical-section div {
    font-size: 13px;
    line-height: 1.7;
    color: #222;
    white-space: pre-wrap;
}

/* ── Prescription box ── */
.rx-box {
    background: #fff8f8;
    border-left: 4px solid #C8102E;
    border-radius: 0 6px 6px 0;
    padding: 14px 16px;
}

.rx-box p {
    font-size: 13px;
    line-height: 1.8;
}

/* ── Notes box ── */
.notes-box {
    background: #f0f4ff;
    border-left: 4px solid #3b6dd8;
    border-radius: 0 6px 6px 0;
    padding: 12px 16px;
}

/* ── Images section ── */
.images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
    margin-top: 10px;
}

.images-grid img {
    width: 100%;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
    object-fit: contain;
    max-height: 220px;
}

.image-label {
    font-size: 11px;
    color: #888;
    margin-top: 4px;
    text-align: center;
    word-break: break-all;
}

/* ── Other files table ── */
.files-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.files-table th { background: #1a1a1a; color: #fff; padding: 7px 10px; font-size: 11px; text-align: left; }
.files-table td { padding: 7px 10px; font-size: 12px; border-bottom: 1px solid #f0f0f0; }
.files-table tr:last-child td { border-bottom: none; }

/* ── Signature ── */
.signature-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 32px;
    padding-top: 16px;
    border-top: 1px solid #e0e0e0;
}

.sig-box { text-align: center; }
.sig-line { border-bottom: 1.5px solid #333; height: 40px; margin-bottom: 6px; }
.sig-name { font-size: 11px; color: #555; font-weight: 600; text-transform: uppercase; }
.sig-sub  { font-size: 10px; color: #888; }

/* ── Footer ── */
.page-footer {
    background: #1a1a1a;
    color: #888;
    padding: 12px 28px;
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    flex-wrap: wrap;
    gap: 6px;
}
.page-footer span { color: #C8102E; }

/* ── Watermark ── */
.watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%,-50%) rotate(-35deg);
    font-size: 80px;
    font-weight: 900;
    color: rgba(200,16,46,0.04);
    pointer-events: none;
    z-index: 0;
    white-space: nowrap;
    user-select: none;
}

/* ============================================================
   PRINT STYLES — this is what becomes the PDF
============================================================ */
@media print {

    @page {
        size: A4;
        margin: 10mm 12mm;
    }

    body {
        background: #fff !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    /* Hide browser UI and our controls */
    .controls    { display: none !important; }
    .watermark   { display: none !important; }

    /* Remove card shadow for print */
    .page {
        box-shadow: none !important;
        border-radius: 0 !important;
        max-width: 100% !important;
    }

    /* Keep header background */
    .page-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .red-bar     { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .rx-box      { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .notes-box   { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .page-footer { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .files-table th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .blood-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    /* Avoid page breaks inside sections */
    .info-grid, .clinical-section, .rx-box, .notes-box,
    .signature-row, .images-grid { page-break-inside: avoid; }

    /* Images smaller in print */
    .images-grid { grid-template-columns: repeat(3, 1fr); }
    .images-grid img { max-height: 160px; }
}
</style>
</head>
<body>

<!-- Print / Back controls — hidden when printing -->
<div class="controls">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="history.php?record=<?= $rid ?>" class="btn-back">← Back</a>
        <p>Your browser will open the <strong>Print dialog</strong>.<br>
           Select <strong>Save as PDF</strong> or your printer.</p>
    </div>
    <button class="btn-print" onclick="window.print()">🖨 Save / Print as PDF</button>
</div>

<div class="watermark">GH+</div>

<!-- ══════════════════════════════════════════
     PDF PAGE
══════════════════════════════════════════ -->
<div class="page">

    <!-- Header -->
    <div class="page-header">
        <div class="header-left">
            <div class="header-logo">GH<span>+</span></div>
            <div class="header-title">
                <h1>Government Hospital Plus</h1>
                <p>Official Medical Record — Sri Lanka</p>
            </div>
        </div>
        <div class="header-right">
            <div class="record-no">Record #<?= str_pad($record['id'], 6, '0', STR_PAD_LEFT) ?></div>
            <div class="record-date">Visit Date: <?= htmlspecialchars($record['visit_date']) ?></div>
            <div class="record-date">Generated: <?= $generated ?></div>
        </div>
    </div>
    <div class="red-bar"></div>

    <!-- Body -->
    <div class="page-body">

        <!-- Patient & Doctor info side by side -->
        <div class="info-grid">

            <!-- Patient info -->
            <div class="info-box">
                <div class="section-label">Patient Information</div>
                <?php $patientRows = [
                    'Full Name'    => $record['patient_name'],
                    'NIC Number'   => $record['patient_nic'],
                    'Date of Birth'=> $record['date_of_birth'] ?? '—',
    'Age'          => ageLabel($record['date_of_birth']),
                    'Gender'       => ucfirst($record['gender'] ?? '—'),
                    'City'         => $record['city'] ?? '—',
                    'Blood Type'   => null, // handled separately
                ]; ?>
                <?php foreach ($patientRows as $lbl => $val): ?>
                    <?php if ($lbl === 'Blood Type'): ?>
                    <div class="info-row">
                        <span class="lbl">Blood Type</span>
                        <span class="val">
                            <?php if (!empty($record['blood_type'])): ?>
                                <span class="blood-badge"><?= htmlspecialchars($record['blood_type']) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="info-row">
                        <span class="lbl"><?= $lbl ?></span>
                        <span class="val"><?= htmlspecialchars($val) ?></span>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (!empty($record['diseases'])): ?>
                <div class="info-row">
                    <span class="lbl">Conditions</span>
                    <span class="val" style="font-size:11px;"><?= htmlspecialchars($record['diseases']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Doctor / Hospital info -->
            <div class="info-box">
                <div class="section-label">Doctor & Hospital</div>
                <?php $drRows = [
                    'Attending Doctor' => $record['doctor_name'],
                    'Position'         => $record['doctor_position'] ?? '—',
                    'SLMC No.'         => $record['slmc_no'] ?? '—',
                    'Hospital'         => $record['hospital'] ?? '—',
                    'Department'       => $record['department'] ?? '—',
                    'Visit Date'       => $record['visit_date'],
                ]; ?>
                <?php foreach ($drRows as $lbl => $val): ?>
                <div class="info-row">
                    <span class="lbl"><?= $lbl ?></span>
                    <span class="val"><?= htmlspecialchars($val) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- Diagnosis -->
        <div class="clinical-section">
            <div class="section-label">Diagnosis</div>
            <p><?= nl2br(htmlspecialchars($record['diagnosis'])) ?></p>
        </div>

        <!-- Prescription -->
        <?php if (!empty($record['prescription'])): ?>
        <div class="clinical-section">
            <div class="section-label">Prescription</div>
            <div class="rx-box">
                <p>💊 <?= nl2br(htmlspecialchars($record['prescription'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notes for Patient -->
        <?php if (!empty($record['notes_for_patient'])): ?>
        <div class="clinical-section">
            <div class="section-label">Doctor's Notes / Follow-up Instructions</div>
            <div class="notes-box">
                <p><?= nl2br(htmlspecialchars($record['notes_for_patient'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Attached Images (embedded) -->
        <?php if (!empty($imageDocs)): ?>
        <div class="clinical-section">
            <div class="section-label">Attached Images (<?= count($imageDocs) ?>)</div>
            <div class="images-grid">
                <?php foreach ($imageDocs as $img): ?>
                <div>
                    <img src="../uploads/<?= htmlspecialchars($img['file_name']) ?>"
                         alt="<?= htmlspecialchars($img['file_name']) ?>">
                    <div class="image-label"><?= htmlspecialchars($img['file_name']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Other files list -->
        <?php if (!empty($otherDocs)): ?>
        <div class="clinical-section">
            <div class="section-label">Other Attached Files (<?= count($otherDocs) ?>)</div>
            <table class="files-table">
                <thead>
                    <tr><th>#</th><th>File Name</th><th>Type</th><th>Uploaded</th></tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($otherDocs as $doc): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($doc['file_name']) ?></td>
                        <td style="text-transform:uppercase;"><?= htmlspecialchars($doc['file_type']) ?></td>
                        <td><?= date('d M Y', strtotime($doc['uploaded_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="font-size:11px;color:#888;margin-top:6px;">* Non-image files are listed here and must be accessed from the GH+ system.</p>
        </div>
        <?php endif; ?>

        <!-- No documents at all -->
        <?php if (empty($docs)): ?>
        <div class="clinical-section">
            <div class="section-label">Attached Documents</div>
            <p style="color:#888;font-style:italic;">No documents attached to this record.</p>
        </div>
        <?php endif; ?>

        <!-- Signature section -->
        <div class="signature-row">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-name"><?= htmlspecialchars($record['doctor_name']) ?></div>
                <div class="sig-sub"><?= htmlspecialchars($record['doctor_position'] ?? 'Attending Doctor') ?></div>
                <div class="sig-sub"><?= htmlspecialchars($record['hospital'] ?? '') ?></div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-name"><?= htmlspecialchars($record['patient_name']) ?></div>
                <div class="sig-sub">Patient — NIC: <?= htmlspecialchars($record['patient_nic']) ?></div>
                <div class="sig-sub">Date: <?= htmlspecialchars($record['visit_date']) ?></div>
            </div>
        </div>

    </div><!-- /page-body -->

    <!-- Footer -->
    <div class="page-footer">
        <span style="color:#aaa;">Government Hospital Plus (GH+) &mdash; Sri Lanka &mdash; Confidential Medical Document</span>
        <span>Record #<?= str_pad($record['id'], 6, '0', STR_PAD_LEFT) ?> &nbsp;|&nbsp; Generated: <?= $generated ?></span>
    </div>

</div><!-- /page -->

<script>
// Auto-trigger print dialog when page loads
window.addEventListener('load', function () {
    // Small delay so images load first
    setTimeout(function () {
        window.print();
    }, 800);
});
</script>

</body>
</html>
