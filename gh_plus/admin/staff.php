<?php
// admin/staff.php
require_once '../includes/config.php';
requireLogin('admin');

// ── Actions BEFORE any output ──────────────────────────────────
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$did AND role='doctor'");
    redirect('staff.php?msg=deleted');
}

$success = $error = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $success = 'Staff account deleted successfully.';
    if ($_GET['msg'] === 'updated') $success = 'Staff details updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid  = (int)($_POST['edit_id']   ?? 0);
    $name = sanitize($conn, $_POST['full_name']  ?? '');
    $stat = sanitize($conn, $_POST['status']     ?? '');
    $pos  = sanitize($conn, $_POST['position']   ?? '');
    $hosp = sanitize($conn, $_POST['hospital']   ?? '');
    $dept = sanitize($conn, $_POST['department'] ?? '');
    $conn->query("UPDATE users SET full_name='$name', status='$stat' WHERE id=$eid AND role='doctor'");
    $conn->query("UPDATE staff_details SET position='$pos', hospital='$hosp', department='$dept' WHERE user_id=$eid");
    redirect('staff.php?msg=updated');
}

$active_page = 'staff';
$search = sanitize($conn, $_GET['q'] ?? '');
$where  = "u.role='doctor' AND u.status != 'pending'";
if ($search) $where .= " AND (u.full_name LIKE '%$search%' OR u.nic LIKE '%$search%')";

$staff = $conn->query("
    SELECT u.*, sd.position, sd.hospital, sd.department, sd.slmc_no
    FROM   users u
    LEFT JOIN staff_details sd ON sd.user_id = u.id
    WHERE  $where
    ORDER  BY u.full_name LIMIT 100
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Staff — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Manage Medical Staff</h1>
        </div>
        <div class="content-area">

            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <form method="GET">
                <div class="search-bar">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search by name or NIC...">
                    <button type="submit">🔍 Search</button>
                    <?php if ($search): ?>
                    <a href="staff.php" class="btn btn-outline">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-wrap">
                <div class="table-header">
                    <h3><?= count($staff) ?> staff member(s)</h3>
                    <input type="text" id="live-search" placeholder="Quick filter..."
                           style="padding:6px 12px;border:1px solid #ccc;
                                  border-radius:6px;font-size:13px;width:180px;">
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th><th>NIC</th><th>Position</th>
                            <th>Hospital</th><th>Department</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $s): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($s['full_name']) ?></td>
                            <td style="font-size:12px;font-family:monospace;"><?= htmlspecialchars($s['nic']) ?></td>
                            <td><?= htmlspecialchars($s['position'] ?? '—') ?></td>
                            <td style="font-size:12px;"><?= htmlspecialchars($s['hospital'] ?? '—') ?></td>
                            <td style="font-size:12px;"><?= htmlspecialchars($s['department'] ?? '—') ?></td>
                            <td><span class="badge badge-<?= $s['status'] ?>"><?= $s['status'] ?></span></td>
                            <td style="white-space:nowrap;">
                                <button class="btn btn-outline btn-sm"
                                        data-modal-open="edit-staff-<?= $s['id'] ?>">Edit</button>
                                <a href="?delete=<?= $s['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   data-confirm="Delete <?= htmlspecialchars($s['full_name']) ?>? This cannot be undone.">
                                   Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($staff)): ?>
                        <tr><td colspan="7" style="text-align:center;color:var(--gray);padding:20px;">No staff found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php foreach ($staff as $s): ?>
<div class="modal-overlay" id="edit-staff-<?= $s['id'] ?>">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Edit Staff — <?= htmlspecialchars($s['full_name']) ?></h3>
            <button class="modal-close" data-modal-close="edit-staff-<?= $s['id'] ?>">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="edit_id" value="<?= $s['id'] ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name"
                               value="<?= htmlspecialchars($s['full_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <select name="position">
                            <?php foreach (['Doctor','Nurse','Surgeon','Radiologist',
                                            'Lab Technician','Pharmacist','Other Medical Staff'] as $pos): ?>
                            <option <?= $pos===$s['position']?'selected':'' ?>><?= $pos ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Hospital</label>
                        <select name="hospital">
                            <?php foreach ($hospitals as $h): ?>
                            <option <?= $h===$s['hospital']?'selected':'' ?>><?= $h ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department"
                               value="<?= htmlspecialchars($s['department'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Account Status</label>
                    <select name="status">
                        <?php foreach (['active','inactive','rejected'] as $st): ?>
                        <option <?= $st===$s['status']?'selected':'' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline"
                        data-modal-close="edit-staff-<?= $s['id'] ?>">Cancel</button>
                <button type="submit" class="btn btn-danger">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<script src="../js/main.js"></script>
</body>
</html>
