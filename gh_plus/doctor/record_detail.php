<?php
// doctor/record_detail.php
require_once '../includes/config.php';
requireLogin('doctor');

$active_page = 'search';
$did = $_SESSION['user_id'];
$rid = (int)($_GET['id'] ?? 0);

if (!$rid) redirect('search.php');

// Fetch the record — doctor can view any active patient's record
$record = $conn->query("
    SELECT r.*,
           p.full_name  AS patient_name,
           p.nic        AS patient_nic,
           p.blood_type,
           p.profile_photo,
           p.id         AS patient_id,
           d.full_name  AS doctor_name,
           sd.position  AS doctor_position,
           sd.hospital,
           sd.department
    FROM medical_records r
    JOIN users p  ON p.id = r.patient_id
    JOIN users d  ON d.id = r.doctor_id
    LEFT JOIN staff_details sd ON sd.user_id = r.doctor_id
    WHERE r.id = $rid
      AND p.status = 'active'
")->fetch_assoc();

if (!$record) {
    echo '<div style="padding:2rem;color:red;font-family:sans-serif;">Record not found or access denied.</div>';
    exit;
}

// Fetch attached documents
$docs = $conn->query("SELECT * FROM medical_documents WHERE record_id = $rid ORDER BY uploaded_at ASC")->fetch_all(MYSQLI_ASSOC);

$photoSrcc = (file_exists('../uploads/' . ($record['profile_photo'] ?? '')))
    ? '../uploads/' . $record['profile_photo']
    : '../assets/default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Record Details — GH+</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.detail-section {
    margin-bottom: 20px;
}
.detail-section label {
    display: block;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray);
    margin-bottom: 6px;
    font-weight: 600;
}
.detail-section p {
    font-size: 14px;
    color: var(--black);
    line-height: 1.7;
    white-space: pre-wrap;
}
.rx-box {
    background: #fff8f8;
    border-left: 4px solid var(--red);
    border-radius: 0 8px 8px 0;
    padding: 14px 16px;
}
.doc-card {
    background: #f9f9f9;
    border: 1px solid #e8e8e8;
    border-radius: 8px;
    padding: 14px;
    text-align: center;
    transition: box-shadow 0.15s;
}
.doc-card:hover {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.doc-icon-lg {
    font-size: 40px;
    margin-bottom: 8px;
    display: block;
}
.doc-card a {
    display: block;
    margin-top: 10px;
    background: var(--red);
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
}
.doc-card a:hover {
    background: var(--red-dark);
    text-decoration: none;
}
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    background: #f9f9f9;
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 20px;
}
.info-grid .item label {
    font-size: 11px;
    color: var(--gray);
    text-transform: uppercase;
    display: block;
    margin-bottom: 2px;
}
.info-grid .item span {
    font-size: 14px;
    font-weight: 600;
    color: var(--black);
}
</style>
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Medical Record Details</h1>
            <div class="topbar-right">
                <a href="patient_profile.php?id=<?= $record['patient_id'] ?>"
                   style="font-size:13px;color:var(--gray);">← Back to Patient</a>
            </div>
        </div>

        <div class="content-area">

            <!-- Patient info banner -->
            <div class="profile-card" style="margin-bottom:20px;">
                <img src="<?= htmlspecialchars($photoSrcc) ?>" alt="Patient Photo">
                <div class="info">
                    <h2><?= htmlspecialchars($record['patient_name']) ?></h2>
                    <p>NIC: <?= htmlspecialchars($record['patient_nic']) ?>
                       &nbsp;|&nbsp;
                       Blood Type: <strong style="color:var(--red);"><?= htmlspecialchars($record['blood_type'] ?? '—') ?></strong>
                    </p>
                </div>
                <div style="margin-left:auto;text-align:right;">
                    <div style="font-size:22px;font-weight:700;color:var(--red);"><?= htmlspecialchars($record['visit_date']) ?></div>
                    <div style="font-size:12px;color:var(--gray);">Visit Date</div>
                </div>
            </div>

            <!-- Visit metadata -->
            <div class="info-grid">
                <div class="item">
                    <label>Attending Doctor</label>
                    <span><?= htmlspecialchars($record['doctor_name']) ?></span>
                </div>
                <div class="item">
                    <label>Position</label>
                    <span><?= htmlspecialchars($record['doctor_position'] ?? '—') ?></span>
                </div>
                <div class="item">
                    <label>Hospital</label>
                    <span><?= htmlspecialchars($record['hospital'] ?? '—') ?></span>
                </div>
                <div class="item">
                    <label>Department</label>
                    <span><?= htmlspecialchars($record['department'] ?? '—') ?></span>
                </div>
                <div class="item">
                    <label>Record ID</label>
                    <span>#<?= $record['id'] ?></span>
                </div>
                <div class="item">
                    <label>Documents</label>
                    <span><?= count($docs) ?> file(s)</span>
                </div>
            </div>

            <!-- Main record card -->
            <div class="card">
                <div class="card-header" style="background:var(--black);border-radius:8px 8px 0 0;">
                    <h3 style="color:#fff;">Clinical Details</h3>
                </div>
                <div class="card-body">

                    <!-- Diagnosis -->
                    <div class="detail-section">
                        <label>Diagnosis</label>
                        <p><?= htmlspecialchars($record['diagnosis']) ?></p>
                    </div>

                    <!-- Prescription -->
                    <?php if (!empty($record['prescription'])): ?>
                    <div class="detail-section">
                        <label>Prescription</label>
                        <div class="rx-box">
                            <p>💊 <?= htmlspecialchars($record['prescription']) ?></p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="detail-section">
                        <label>Prescription</label>
                        <p style="color:var(--gray);font-style:italic;">No prescription issued for this visit.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Notes for patient -->
                    <?php if (!empty($record['notes_for_patient'])): ?>
                    <div class="detail-section">
                        <label>Notes for Patient</label>
                        <p style="background:#f0f4ff;padding:12px;border-radius:6px;border-left:3px solid #3b6dd8;">
                            <?= htmlspecialchars($record['notes_for_patient']) ?>
                        </p>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Attached Documents -->
            <div class="card">
                <div class="card-header">
                    <h3>Attached Documents & Files
                        <span style="font-size:13px;font-weight:400;color:var(--gray);margin-left:8px;">(<?= count($docs) ?>)</span>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($docs)): ?>
                        <p style="color:var(--gray);text-align:center;padding:1rem;">No files attached to this record.</p>
                    <?php else: ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;">
                            <?php foreach ($docs as $doc):
                                $icons = ['image'=>'🖼','pdf'=>'📄','audio'=>'🔊','video'=>'🎬','other'=>'📎'];
                                $icon  = $icons[$doc['file_type']] ?? '📎';
                                $ext   = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                                $fileUrl = '../uploads/' . htmlspecialchars($doc['file_name']);
                            ?>
                            <div class="doc-card">
                                <span class="doc-icon-lg"><?= $icon ?></span>
                                <p style="font-size:11px;font-weight:600;word-break:break-all;color:var(--black);">
                                    <?= htmlspecialchars($doc['file_name']) ?>
                                </p>
                                <p style="font-size:11px;color:var(--gray);margin-top:4px;text-transform:uppercase;"><?= $ext ?></p>
                                <p style="font-size:11px;color:var(--gray);"><?= date('d M Y', strtotime($doc['uploaded_at'])) ?></p>

                                <?php if ($doc['file_type'] === 'image'): ?>
                                    <!-- Show preview for images -->
                                    <img src="<?= $fileUrl ?>"
                                         alt="Preview"
                                         style="width:100%;max-height:100px;object-fit:cover;border-radius:4px;margin-top:8px;border:1px solid #eee;">
                                <?php endif; ?>

                                <a href="<?= $fileUrl ?>" target="_blank" download>
                                    ⬇ Download / View
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action buttons -->
            <div style="display:flex;gap:12px;margin-top:4px;">
                <a href="patient_profile.php?id=<?= $record['patient_id'] ?>"
                   class="btn btn-outline">← Back to Patient Profile</a>
                <a href="patient_profile.php?id=<?= $record['patient_id'] ?>"
                   data-modal-open="upload-modal"
                   class="btn btn-danger">+ Add New Record</a>
                <button onclick="window.print()" class="btn btn-dark">🖨 Print Record</button>
            </div>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
