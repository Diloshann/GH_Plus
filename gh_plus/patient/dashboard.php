<?php
// patient/dashboard.php
require_once '../includes/config.php';
requireLogin('patient');

$uid = $_SESSION['user_id'];
$active_page = 'dashboard';

// Load patient data
$user = $conn->query("SELECT u.*, p.diseases, p.description FROM users u LEFT JOIN patient_details p ON p.user_id=u.id WHERE u.id=$uid")->fetch_assoc();

// Recent records
$records = $conn->query("SELECT r.*, u.full_name AS doctor_name FROM medical_records r JOIN users u ON u.id=r.doctor_id WHERE r.patient_id=$uid ORDER BY r.visit_date DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

// Recent docs
$docs = $conn->query("SELECT d.*, r.visit_date FROM medical_documents d JOIN medical_records r ON r.id=d.record_id WHERE r.patient_id=$uid ORDER BY d.uploaded_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

// Total counts
$totalRecords = $conn->query("SELECT COUNT(*) c FROM medical_records WHERE patient_id=$uid")->fetch_assoc()['c'];
$totalDocs    = $conn->query("SELECT COUNT(*) c FROM medical_documents d JOIN medical_records r ON r.id=d.record_id WHERE r.patient_id=$uid")->fetch_assoc()['c'];
$totalRx      = $conn->query("SELECT COUNT(*) c FROM medical_records WHERE patient_id=$uid AND prescription IS NOT NULL AND prescription != ''")->fetch_assoc()['c'];

$photoSrc = (file_exists('../uploads/' . $user['profile_photo'])) ? '../uploads/' . $user['profile_photo'] : '../assets/default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>My Dashboard</h1>
            <div class="topbar-right">
                <span>🗓 <?= date('D, d M Y') ?></span>
            </div>
        </div>
        <div class="content-area">

            <!-- Profile Card -->
            <div class="profile-card">
                <img src="<?= htmlspecialchars($photoSrc) ?>" alt="Photo" id="photo-preview">
                <div class="info">
                    <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                    <p>NIC: <?= htmlspecialchars($user['nic']) ?> &nbsp;|&nbsp;
                       DOB: <?= htmlspecialchars($user['date_of_birth'] ?? '—') ?> &nbsp;|&nbsp;  
                       City: <?= htmlspecialchars($user['city'] ?? '—') ?></p>
                    <div class="profile-meta" style="margin-top:8px;">
                        <div class="meta-item">
                            <label>Blood Type</label>
                            <span style="color:var(--red);font-size:18px;"><?= htmlspecialchars($user['blood_type'] ?? '—') ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Gender</label>
                            <span><?= ucfirst($user['gender'] ?? '—') ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Conditions</label>
                            <span style="font-size:12px;"><?= htmlspecialchars($user['diseases'] ?? 'None recorded') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Visits</div>
                    <div class="stat-value"><?= $totalRecords ?></div>
                    <div class="stat-sub">Medical records</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Prescriptions</div>
                    <div class="stat-value"><?= $totalRx ?></div>
                    <div class="stat-sub">Issued to you</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Documents</div>
                    <div class="stat-value"><?= $totalDocs ?></div>
                    <div class="stat-sub">Reports & files</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Account Status</div>
                    <div class="stat-value" style="font-size:16px;margin-top:4px;">
                        <span class="badge badge-active">Active</span>
                    </div>
                </div>
            </div>

            <!-- Recent Records -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Medical Records</h3>
                    <a href="history.php" style="font-size:13px;color:var(--red);">View all →</a>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($records)): ?>
                        <p style="padding:20px;color:var(--gray);text-align:center;">No medical records yet.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Diagnosis</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['visit_date']) ?></td>
                                    <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                                    <td><?= htmlspecialchars(substr($r['diagnosis'], 0, 60)) ?>...</td>
                                    <td><a href="history.php?record=<?= $r['id'] ?>" class="btn btn-outline btn-sm">View</a>
                                        <a href="record_pdf.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-danger btn-sm">📄 PDF</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Documents -->
            <?php if (!empty($docs)): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Recent Documents</h3>
                    <a href="documents.php" style="font-size:13px;color:var(--red);">View all →</a>
                </div>
                <div class="card-body">
                    <div class="docs-grid">
                        <?php foreach ($docs as $d): ?>
                        <div class="doc-thumb">
                            <span class="doc-icon">
                                <?= $d['file_type']==='image' ? '🖼' : ($d['file_type']==='pdf' ? '📄' : ($d['file_type']==='audio' ? '🔊' : '🎬')) ?>
                            </span>
                            <a href="../uploads/<?= htmlspecialchars($d['file_name']) ?>" target="_blank"><?= htmlspecialchars(substr($d['file_name'],0,20)) ?></a>
                            <div style="font-size:11px;color:var(--gray);margin-top:2px;"><?= $d['visit_date'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
