<?php
// admin/patients.php
require_once '../includes/config.php';
requireLogin('admin');

// ── Actions BEFORE any output ──────────────────────────────────
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$did AND role='patient'");
    redirect('patients.php?msg=deleted');
}

$success = $error = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $success = 'Patient account deleted successfully.';
    if ($_GET['msg'] === 'updated') $success = 'Patient details updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid    = (int)($_POST['edit_id']   ?? 0);
    $name   = sanitize($conn, $_POST['full_name']  ?? '');
    $nic    = sanitize($conn, $_POST['nic']        ?? '');
    $dob    = sanitize($conn, $_POST['dob']        ?? '');
    $city   = sanitize($conn, $_POST['city']       ?? '');
    $blood  = sanitize($conn, $_POST['blood_type'] ?? '');
    $status = sanitize($conn, $_POST['status']     ?? '');
    $conn->query("UPDATE users SET full_name='$name', nic='$nic', date_of_birth='$dob',
                  city='$city', blood_type='$blood', status='$status'
                  WHERE id=$eid AND role='patient'");
    redirect('patients.php?msg=updated');
}

$active_page = 'patients';
$search = sanitize($conn, $_GET['q'] ?? '');
$where  = "role='patient' AND status != 'pending'";
if ($search) $where .= " AND (full_name LIKE '%$search%' OR nic LIKE '%$search%' OR city LIKE '%$search%')";
$patients = $conn->query("SELECT * FROM users WHERE $where ORDER BY full_name LIMIT 100")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Patients — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Manage Patients</h1>
        </div>
        <div class="content-area">

            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="GET">
                <div class="search-bar">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search by name, NIC or city...">
                    <button type="submit">🔍 Search</button>
                    <?php if ($search): ?>
                    <a href="patients.php" class="btn btn-outline">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-wrap">
                <div class="table-header">
                    <h3><?= count($patients) ?> patient(s)</h3>
                    <input type="text" id="live-search" placeholder="Quick filter..."
                           style="padding:6px 12px;border:1px solid #ccc;
                                  border-radius:6px;font-size:13px;width:180px;">
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th><th>NIC</th><th>DOB</th><th>Age</th>
                            <th>City</th><th>Blood</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $p): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($p['full_name']) ?></td>
                            <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($p['nic']) ?></td>
                            <td style="font-size:12px;"><?= htmlspecialchars($p['date_of_birth'] ?? '—') ?></td>
                            <td style="font-size:12px;font-weight:600;"><?= ageLabel($p['date_of_birth']) ?></td>
                            <td><?= htmlspecialchars($p['city'] ?? '—') ?></td>
                            <td style="color:var(--red);font-weight:700;"><?= htmlspecialchars($p['blood_type'] ?? '—') ?></td>
                            <td><span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
                            <td style="white-space:nowrap;">
                                <button class="btn btn-outline btn-sm"
                                        data-modal-open="edit-<?= $p['id'] ?>">Edit</button>
                                <a href="?delete=<?= $p['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   data-confirm="Delete <?= htmlspecialchars($p['full_name']) ?>? This cannot be undone.">
                                   Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($patients)): ?>
                        <tr><td colspan="8" style="text-align:center;color:var(--gray);padding:20px;">No patients found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php foreach ($patients as $p): ?>
<div class="modal-overlay" id="edit-<?= $p['id'] ?>">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Edit Patient — <?= htmlspecialchars($p['full_name']) ?></h3>
            <button class="modal-close" data-modal-close="edit-<?= $p['id'] ?>">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="edit_id" value="<?= $p['id'] ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name"
                               value="<?= htmlspecialchars($p['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>NIC</label>
                        <input type="text" name="nic"
                               value="<?= htmlspecialchars($p['nic']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob"
                               value="<?= htmlspecialchars($p['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <select name="city">
                            <?php foreach ($sriLankaCities as $c): ?>
                            <option <?= $c===$p['city']?'selected':'' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Blood Type</label>
                        <select name="blood_type">
                            <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $b): ?>
                            <option <?= $b===$p['blood_type']?'selected':'' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="status">
                            <?php foreach (['active','inactive','rejected'] as $s): ?>
                            <option <?= $s===$p['status']?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline"
                        data-modal-close="edit-<?= $p['id'] ?>">Cancel</button>
                <button type="submit" class="btn btn-danger">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<script src="../js/main.js"></script>
</body>
</html>
