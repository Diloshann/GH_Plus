<?php
// doctor/patient_profile.php
require_once '../includes/config.php';
requireLogin('doctor');

$active_page = 'search';
$did = $_SESSION['user_id'];
$pid = (int)($_GET['id'] ?? 0);

if (!$pid) redirect('search.php');

$patient = $conn->query("SELECT u.*, p.diseases, p.description FROM users u LEFT JOIN patient_details p ON p.user_id=u.id WHERE u.id=$pid AND u.role='patient' AND u.status='active'")->fetch_assoc();
if (!$patient) { echo '<p style="padding:2rem;color:red;">Patient not found.</p>'; exit; }

$success = $error = '';

// Upload new record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visit_date  = sanitize($conn, $_POST['visit_date'] ?? '');
    $diagnosis   = sanitize($conn, $_POST['diagnosis'] ?? '');
    $prescription= sanitize($conn, $_POST['prescription'] ?? '');
    $notes       = sanitize($conn, $_POST['notes_for_patient'] ?? '');

    if (empty($visit_date) || empty($diagnosis)) {
        $error = 'Visit date and diagnosis are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, visit_date, diagnosis, prescription, notes_for_patient) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('iissss', $pid, $did, $visit_date, $diagnosis, $prescription, $notes);
        $stmt->execute();
        $rid = $conn->insert_id;
        $stmt->close();

        // Handle file uploads
        if (!empty($_FILES['upload_files']['name'][0])) {
            foreach ($_FILES['upload_files']['name'] as $i => $fname) {
                if ($_FILES['upload_files']['error'][$i] !== 0) continue;
                if (!allowedUpload($fname)) continue;
                $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                $newName = 'rec_' . $rid . '_' . time() . '_' . $i . '.' . $ext;
                move_uploaded_file($_FILES['upload_files']['tmp_name'][$i], UPLOAD_DIR . $newName);
                $ftype = uploadFileType($ext);
                $fn = $conn->real_escape_string($newName);
                $fp = $conn->real_escape_string('uploads/' . $newName);
                $conn->query("INSERT INTO medical_documents (record_id, file_name, file_path, file_type) VALUES ($rid, '$fn', '$fp', '$ftype')");
            }
        }

        $success = 'Medical record saved successfully.';
    }
}

$records = $conn->query("SELECT r.*, (SELECT COUNT(*) FROM medical_documents WHERE record_id=r.id) AS doc_count
    FROM medical_records r WHERE r.patient_id=$pid ORDER BY r.visit_date DESC")->fetch_all(MYSQLI_ASSOC);

$photoSrcc = (file_exists('../uploads/'.$patient['profile_photo'])) ? '../uploads/'.$patient['profile_photo'] : '../assets/default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($patient['full_name']) ?> — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Patient Profile</h1>
            <div class="topbar-right">
                <a href="search.php" style="font-size:13px;color:var(--gray);">← Back to Search</a>
            </div>
        </div>
        <div class="content-area">
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Patient Header -->
            <div class="profile-card">
                <img src="<?= htmlspecialchars($photoSrcc) ?>" alt="Patient Photo">
                <div class="info">
                    <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
                    <p style="font-size:13px;color:var(--gray);">Patient ID: <?= htmlspecialchars($patient['nic']) ?></p>
                    <p>DOB: <?= htmlspecialchars($patient['date_of_birth'] ?? '—') ?> &nbsp;|&nbsp; Age: <strong><?= ageLabel($patient['date_of_birth']) ?></strong> &nbsp;|&nbsp; City: <?= htmlspecialchars($patient['city'] ?? '—') ?></p>
                    <p style="font-size:12px;color:var(--red);font-weight:600;">Blood Type: <?= htmlspecialchars($patient['blood_type'] ?? '—') ?></p>
                    <?php if ($patient['diseases']): ?>
                    <div style="margin-top:8px;">
                        <span style="font-size:11px;color:var(--gray);">Known Conditions:</span>
                        <div style="margin-top:4px;">
                            <?php foreach (explode(',', $patient['diseases']) as $d): ?>
                                <span style="background:var(--red-light);color:var(--red);font-size:11px;padding:2px 8px;border-radius:20px;margin-right:4px;"><?= htmlspecialchars(trim($d)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($patient['description']): ?>
                    <p style="font-size:12px;color:var(--gray);margin-top:6px;"><strong>⚠ Notes:</strong> <?= htmlspecialchars($patient['description']) ?></p>
                    <?php endif; ?>
                </div>
                <div style="margin-left:auto;">
                    <button class="btn btn-danger" data-modal-open="upload-modal">+ Add New Record</button>
                </div>
            </div>

            <!-- Medical History -->
            <div class="tabs">
                <button class="tab-btn active" data-group="hist" data-tab="tab-history">Medical History (<?= count($records) ?>)</button>
            </div>

            <div id="tab-history" class="tab-content active" data-group="hist">
                <?php if (empty($records)): ?>
                    <div style="text-align:center;padding:2rem;color:var(--gray);">No records uploaded yet. Add the first record.</div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($records as $r): ?>
                        <div class="timeline-item">
                            <div class="timeline-date">🗓 <?= htmlspecialchars($r['visit_date']) ?></div>
                            <div class="timeline-title"><?= htmlspecialchars(substr($r['diagnosis'], 0, 80)) ?>...</div>
                            <?php if ($r['prescription']): ?>
                            <div style="font-size:12px;margin-top:4px;">💊 <em><?= htmlspecialchars(substr($r['prescription'],0,80)) ?></em></div>
                            <?php endif; ?>
                            <div style="margin-top:6px;display:flex;gap:8px;align-items:center;">
                                <a href="record_detail.php?id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">View Details</a>
                                <?php if ($r['doc_count'] > 0): ?>
                                    <span style="font-size:12px;color:var(--gray);">📎 <?= $r['doc_count'] ?> file(s)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Upload New Record Modal -->
<div class="modal-overlay" id="upload-modal">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Add New Medical Record — <?= htmlspecialchars($patient['full_name']) ?></h3>
            <button class="modal-close" data-modal-close="upload-modal">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Visit Date *</label>
                        <input type="date" name="visit_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group" style="align-self:end;">
                        <label style="color:var(--gray);">Doctor</label>
                        <input type="text" value="<?= htmlspecialchars($_SESSION['full_name']) ?>" disabled style="background:#f5f5f5;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Diagnosis *</label>
                    <textarea name="diagnosis" required placeholder="Clinical diagnosis and findings..." style="min-height:90px;"></textarea>
                </div>
                <div class="form-group">
                    <label>Prescription</label>
                    <textarea name="prescription" placeholder="Medicine name, dosage, frequency, duration..." style="min-height:70px;"></textarea>
                </div>
                <div class="form-group">
                    <label>Notes for Patient</label>
                    <textarea name="notes_for_patient" placeholder="Follow-up instructions, advice, dietary notes..." style="min-height:60px;"></textarea>
                </div>
                <div class="form-group">
                    <label>Attach Files (images, PDFs, audio, video)</label>
                    <input type="file" id="upload_files" name="upload_files[]" multiple accept="image/*,.pdf,audio/*,video/*">
                    <div id="file-preview" style="margin-top:8px;"></div>
                    <small style="color:var(--gray);">Accepted: JPG, PNG, PDF, MP3, MP4. Max per file: 20MB.</small>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline" data-modal-close="upload-modal">Cancel</button>
                <button type="submit" class="btn btn-danger">💾 Save Record</button>
            </div>
        </form>
    </div>
</div>

<script src="../js/main.js"></script>
</body>
</html>
