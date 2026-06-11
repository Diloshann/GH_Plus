<?php
// admin/support.php
require_once '../includes/config.php';
requireLogin('admin');

// ── Actions BEFORE any output ──────────────────────────────────
if (isset($_GET['resolve'])) {
    $rid = (int)$_GET['resolve'];
    $conn->query("UPDATE support_messages SET status='resolved' WHERE id=$rid");
    redirect('support.php?msg=resolved');
}
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM support_messages WHERE id=$did");
    redirect('support.php?msg=deleted');
}

$success = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'resolved') $success = 'Ticket marked as resolved.';
    if ($_GET['msg'] === 'deleted')  $success = 'Message deleted.';
}

$active_page = 'support';

// Filters
$filter_status   = sanitize($conn, $_GET['status']   ?? '');
$filter_category = sanitize($conn, $_GET['category'] ?? '');

$where = '1=1';
if ($filter_status)   $where .= " AND sm.status = '$filter_status'";
if ($filter_category) $where .= " AND sm.category = '$filter_category'";

$messages = $conn->query("
    SELECT sm.*, u.full_name, u.nic, u.role
    FROM   support_messages sm
    LEFT JOIN users u ON u.id = sm.user_id
    WHERE  $where
    ORDER  BY sm.submitted_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Count by status
$count_open     = $conn->query("SELECT COUNT(*) c FROM support_messages WHERE status='open'")->fetch_assoc()['c'];
$count_resolved = $conn->query("SELECT COUNT(*) c FROM support_messages WHERE status='resolved'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support Messages — GH+</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.msg-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.07);
    margin-bottom: 14px;
    border-left: 4px solid var(--red);
    overflow: hidden;
}
.msg-card.resolved {
    border-left-color: #4caf50;
    opacity: 0.85;
}
.msg-head {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    border-bottom: 1px solid #f0f0f0;
    background: #fafafa;
}
.msg-body {
    padding: 14px 16px;
}
.msg-meta {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    font-size: 12px;
    color: var(--gray);
}
.msg-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}
.msg-text {
    font-size: 14px;
    line-height: 1.7;
    color: var(--black);
    white-space: pre-wrap;
    margin-top: 6px;
}
</style>
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1>Support Messages</h1>
            <div class="topbar-right">
                <?php if ($count_open > 0): ?>
                <span style="background:var(--red);color:#fff;padding:3px 10px;
                             border-radius:20px;font-size:12px;font-weight:600;">
                    <?= $count_open ?> open
                </span>
                <?php else: ?>
                <span style="background:#4caf50;color:#fff;padding:3px 10px;
                             border-radius:20px;font-size:12px;">All resolved ✔</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="content-area">

            <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Stats row -->
            <div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
                <div class="stat-card">
                    <div class="stat-label">Total Messages</div>
                    <div class="stat-value"><?= count($messages) ?></div>
                </div>
                <div class="stat-card" style="border-left-color:orange;">
                    <div class="stat-label">Open</div>
                    <div class="stat-value" style="color:orange;"><?= $count_open ?></div>
                </div>
                <div class="stat-card" style="border-left-color:#4caf50;">
                    <div class="stat-label">Resolved</div>
                    <div class="stat-value" style="color:#4caf50;"><?= $count_resolved ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Categories</div>
                    <div class="stat-value" style="font-size:14px;margin-top:6px;">4 types</div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" style="margin-bottom:20px;">
                <div class="search-bar">
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="open"     <?= $filter_status==='open'    ?'selected':'' ?>>Open</option>
                        <option value="resolved" <?= $filter_status==='resolved'?'selected':'' ?>>Resolved</option>
                    </select>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach (['Technical','Medical Query','Complaint','Other'] as $cat): ?>
                        <option <?= $filter_category===$cat?'selected':'' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Filter</button>
                    <a href="support.php" class="btn btn-outline">Reset</a>
                </div>
            </form>

            <!-- Message list -->
            <?php if (empty($messages)): ?>
            <div style="text-align:center;padding:3rem;color:var(--gray);">
                <div style="font-size:48px;margin-bottom:1rem;">📭</div>
                <p>No messages found.</p>
            </div>
            <?php else: ?>

            <?php foreach ($messages as $m): ?>
            <div class="msg-card <?= $m['status']==='resolved'?'resolved':'' ?>">

                <div class="msg-head">
                    <div class="msg-meta">
                        <span>
                            👤 <strong><?= htmlspecialchars($m['full_name'] ?? 'Guest') ?></strong>
                            <?php if (!empty($m['nic'])): ?>
                                <span style="color:#aaa;">(<?= htmlspecialchars($m['nic']) ?>)</span>
                            <?php endif; ?>
                            <?php if (!empty($m['role'])): ?>
                                <span class="badge badge-<?= $m['role']==='patient'?'active':'pending' ?>"
                                      style="font-size:10px;padding:2px 6px;">
                                    <?= ucfirst($m['role']) ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <span>
                            <span class="badge badge-pending" style="font-size:10px;">
                                <?= htmlspecialchars($m['category']) ?>
                            </span>
                        </span>
                        <span>🗓 <?= date('d M Y, H:i', strtotime($m['submitted_at'])) ?></span>
                        <span>
                            <?php if ($m['status'] === 'open'): ?>
                            <span style="color:orange;font-weight:600;">● Open</span>
                            <?php else: ?>
                            <span style="color:#4caf50;font-weight:600;">✔ Resolved</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="msg-actions">
                        <?php if ($m['status'] === 'open'): ?>
                        <a href="?resolve=<?= $m['id'] ?>"
                           class="btn btn-dark btn-sm"
                           data-confirm="Mark this ticket as resolved?">
                           ✔ Resolve
                        </a>
                        <?php endif; ?>
                        <a href="?delete=<?= $m['id'] ?>"
                           class="btn btn-danger btn-sm"
                           data-confirm="Delete this message permanently?">
                           🗑 Delete
                        </a>
                    </div>
                </div>

                <div class="msg-body">
                    <?php if (!empty($m['subject'])): ?>
                    <p style="font-weight:600;font-size:14px;margin-bottom:8px;">
                        📌 <?= htmlspecialchars($m['subject']) ?>
                    </p>
                    <?php endif; ?>
                    <div class="msg-text"><?= htmlspecialchars($m['message']) ?></div>
                </div>

            </div>
            <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
