<?php
// doctor/search.php
require_once '../includes/config.php';
requireLogin('doctor');

$active_page = 'search';
$did = $_SESSION['user_id'];

$q      = sanitize($conn, $_GET['q'] ?? '');
$filter = sanitize($conn, $_GET['filter'] ?? 'name');
$results = [];

if ($q !== '') {
    $col = in_array($filter, ['nic','city','blood_type']) ? $filter : 'full_name';
    $like = "%$q%";
    $stmt = $conn->prepare("SELECT u.id, u.full_name, u.nic, u.city, u.blood_type, u.profile_photo,
        p.diseases, MAX(r.visit_date) AS last_visit
        FROM users u
        LEFT JOIN patient_details p ON p.user_id=u.id
        LEFT JOIN medical_records r ON r.patient_id=u.id
        WHERE u.role='patient' AND u.status='active' AND u.$col LIKE ?
        GROUP BY u.id
        ORDER BY u.full_name
        LIMIT 50");
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Patients — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Search Patients</h1>
            <div class="topbar-right">
                <span style="background:var(--red);color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;">Medical Staff</span>
            </div>
        </div>
        <div class="content-area">

            <form method="GET" action="">
                <div class="search-bar">
                    <select name="filter">
                        <option value="full_name" <?= $filter==='full_name'?'selected':'' ?>>Search by Name</option>
                        <option value="nic"       <?= $filter==='nic'?'selected':'' ?>>Search by NIC</option>
                        <option value="city"      <?= $filter==='city'?'selected':'' ?>>Search by City</option>
                        <option value="blood_type"<?= $filter==='blood_type'?'selected':'' ?>>Search by Blood Type</option>
                    </select>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Enter search term..." style="flex:2;">
                    <button type="submit">🔍 Search</button>
                </div>
            </form>

            <?php if ($q === ''): ?>
                <div style="text-align:center;padding:3rem;color:var(--gray);">
                    <div style="font-size:56px;margin-bottom:1rem;">🔍</div>
                    <h3>Search for a patient to get started</h3>
                    <p style="font-size:13px;margin-top:8px;">Search by name, NIC number, city, or blood type</p>
                </div>
            <?php elseif (empty($results)): ?>
                <div class="alert alert-info">No patients found matching "<?= htmlspecialchars($q) ?>".</div>
            <?php else: ?>
                <div class="table-wrap">
                    <div class="table-header">
                        <h3><?= count($results) ?> patient(s) found</h3>
                        <input type="text" id="live-search" placeholder="Filter results..." style="padding:6px 12px;border:1px solid #ccc;border-radius:6px;font-size:13px;">
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Patient Name</th>
                                <th>NIC</th>
                                <th>City</th>
                                <th>Blood Type</th>
                                <th>Conditions</th>
                                <th>Last Visit</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $p): ?>
                            <tr>
                                <td>
                                    <?php $ps = (file_exists('../uploads/'.$p['profile_photo'])) ? '../uploads/'.$p['profile_photo'] : '../assets/default.png'; ?>
                                    <img src="<?= htmlspecialchars($ps) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--red);">
                                </td>
                                <td style="font-weight:600;"><?= htmlspecialchars($p['full_name']) ?></td>
                                <td style="font-family:monospace;"><?= htmlspecialchars($p['nic']) ?></td>
                                <td><?= htmlspecialchars($p['city'] ?? '—') ?></td>
                                <td style="color:var(--red);font-weight:700;"><?= htmlspecialchars($p['blood_type'] ?? '—') ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars(substr($p['diseases'] ?? 'None', 0, 30)) ?></td>
                                <td style="font-size:12px;"><?= $p['last_visit'] ?? 'No visits' ?></td>
                                <td>
                                    <a href="patient_profile.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm">View Profile</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
