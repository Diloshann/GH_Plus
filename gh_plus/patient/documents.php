<?php
// patient/documents.php
require_once '../includes/config.php';
requireLogin('patient');

$uid = $_SESSION['user_id'];
$active_page = 'documents';

$type_filter = sanitize($conn, $_GET['type'] ?? '');
$where = "r.patient_id = $uid";
if ($type_filter) $where .= " AND d.file_type = '$type_filter'";

$docs = $conn->query("SELECT d.*, r.visit_date, r.diagnosis, u.full_name AS doctor_name
    FROM medical_documents d
    JOIN medical_records r ON r.id = d.record_id
    JOIN users u ON u.id = r.doctor_id
    WHERE $where
    ORDER BY d.uploaded_at DESC")->fetch_all(MYSQLI_ASSOC);

$counts = $conn->query("SELECT d.file_type, COUNT(*) c FROM medical_documents d JOIN medical_records r ON r.id=d.record_id WHERE r.patient_id=$uid GROUP BY d.file_type")->fetch_all(MYSQLI_ASSOC);
$count_map = [];
foreach ($counts as $c) $count_map[$c['file_type']] = $c['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Documents — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button><h1>My Documents</h1></div>
        <div class="content-area">

            <!-- Filter buttons -->
            <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
                <a href="documents.php" class="btn <?= $type_filter===''?'btn-dark':'btn-outline' ?> btn-sm">All (<?= array_sum($count_map) ?>)</a>
                <a href="documents.php?type=image" class="btn <?= $type_filter==='image'?'btn-dark':'btn-outline' ?> btn-sm">🖼 Images (<?= $count_map['image'] ?? 0 ?>)</a>
                <a href="documents.php?type=pdf"   class="btn <?= $type_filter==='pdf'?'btn-dark':'btn-outline' ?> btn-sm">📄 PDFs (<?= $count_map['pdf'] ?? 0 ?>)</a>
                <a href="documents.php?type=audio" class="btn <?= $type_filter==='audio'?'btn-dark':'btn-outline' ?> btn-sm">🔊 Audio (<?= $count_map['audio'] ?? 0 ?>)</a>
                <a href="documents.php?type=video" class="btn <?= $type_filter==='video'?'btn-dark':'btn-outline' ?> btn-sm">🎬 Video (<?= $count_map['video'] ?? 0 ?>)</a>
            </div>

            <?php if (empty($docs)): ?>
                <div style="text-align:center;padding:3rem;color:var(--gray);">
                    <div style="font-size:48px;margin-bottom:1rem;">📁</div>
                    <p>No documents found.</p>
                </div>
            <?php else: ?>
                <div class="docs-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">
                    <?php foreach ($docs as $d): ?>
                    <div style="background:#fff;border:1px solid #e8e8e8;border-radius:8px;padding:14px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                        <div style="font-size:40px;margin-bottom:8px;">
                            <?php
                            $icons = ['image'=>'🖼','pdf'=>'📄','audio'=>'🔊','video'=>'🎬','other'=>'📎'];
                            echo $icons[$d['file_type']] ?? '📎';
                            ?>
                        </div>
                        <p style="font-size:12px;font-weight:600;word-break:break-all;margin-bottom:4px;"><?= htmlspecialchars($d['file_name']) ?></p>
                        <p style="font-size:11px;color:var(--gray);margin-bottom:8px;"><?= htmlspecialchars($d['visit_date']) ?></p>
                        <p style="font-size:11px;color:var(--gray);margin-bottom:10px;">Dr. <?= htmlspecialchars($d['doctor_name']) ?></p>
                        <a href="../uploads/<?= htmlspecialchars($d['file_name']) ?>" target="_blank" class="btn btn-danger btn-sm" style="width:100%;display:block;">Download</a>
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
