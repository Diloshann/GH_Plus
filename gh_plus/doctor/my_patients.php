<?php
// doctor/my_patients.php
require_once '../includes/config.php';
requireLogin('doctor');

$active_page = 'my_patients';
$did = $_SESSION['user_id'];

// Patients who have at least one record from this doctor
$patients = $conn->query("SELECT DISTINCT u.id, u.full_name, u.nic, u.city, u.blood_type, u.profile_photo,
    p.diseases, COUNT(r.id) AS total_visits, MAX(r.visit_date) AS last_visit
    FROM users u
    JOIN medical_records r ON r.patient_id = u.id AND r.doctor_id = $did
    LEFT JOIN patient_details p ON p.user_id = u.id
    WHERE u.role='patient' AND u.status='active'
    GROUP BY u.id
    ORDER BY last_visit DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Patients — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>My Patients</h1>
            <div class="topbar-right">
                <span style="font-size:13px;color:var(--gray);"><?= count($patients) ?> patients treated</span>
            </div>
        </div>
        <div class="content-area">

            <?php if (empty($patients)): ?>
                <div style="text-align:center;padding:3rem;color:var(--gray);">
                    <div style="font-size:48px;margin-bottom:1rem;">🧑‍⚕️</div>
                    <p>No patients yet. Start by searching and uploading a record.</p>
                    <a href="search.php" class="btn btn-danger" style="display:inline-block;margin-top:16px;">Search Patients</a>
                </div>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
                    <?php foreach ($patients as $p): ?>
                    <?php $ps = (file_exists('../uploads/'.$p['profile_photo'])) ? '../uploads/'.$p['profile_photo'] : '../assets/default.png'; ?>
                    <div class="card">
                        <div class="card-body" style="text-align:center;">
                            <img src="<?= htmlspecialchars($ps) ?>" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--red);margin-bottom:10px;">
                            <h3 style="font-size:14px;"><?= htmlspecialchars($p['full_name']) ?></h3>
                            <p style="font-size:12px;color:var(--gray);margin-bottom:4px;"><?= htmlspecialchars($p['nic']) ?></p>
                            <p style="font-size:12px;margin-bottom:4px;">
                                <span style="color:var(--red);font-weight:700;"><?= htmlspecialchars($p['blood_type'] ?? '—') ?></span>
                                &nbsp;|&nbsp; <?= htmlspecialchars($p['city'] ?? '—') ?>
                            </p>
                            <p style="font-size:11px;color:var(--gray);margin-bottom:10px;">
                                <?= $p['total_visits'] ?> visit(s) &nbsp;|&nbsp; Last: <?= htmlspecialchars($p['last_visit']) ?>
                            </p>
                            <?php if ($p['diseases']): ?>
                            <p style="font-size:11px;color:var(--red);margin-bottom:10px;"><?= htmlspecialchars(substr($p['diseases'],0,40)) ?></p>
                            <?php endif; ?>
                            <a href="patient_profile.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" style="width:100%;display:block;">View Profile</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
