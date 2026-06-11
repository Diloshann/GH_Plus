<?php
// patient/contact.php
require_once '../includes/config.php';
requireLogin('patient');

$uid = $_SESSION['user_id'];
$active_page = 'contact';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject  = sanitize($conn, $_POST['subject'] ?? '');
    $message  = sanitize($conn, $_POST['message'] ?? '');
    $category = sanitize($conn, $_POST['category'] ?? 'Other');

    if (empty($message)) {
        $error = 'Please enter a message.';
    } else {
        $stmt = $conn->prepare("INSERT INTO support_messages (user_id, subject, message, category) VALUES (?,?,?,?)");
        $stmt->bind_param('isss', $uid, $subject, $message, $category);
        $stmt->execute();
        $stmt->close();
        $success = 'Your message has been submitted. We will respond within 24 hours.';
    }
}

$hospitals_contact = [
    ['name'=>'Colombo National Hospital',       'phone'=>'011 2691111', 'city'=>'Colombo'],
    ['name'=>'Kandy Teaching Hospital',          'phone'=>'081 2222261', 'city'=>'Kandy'],
    ['name'=>'Galle Teaching Hospital',          'phone'=>'091 2222261', 'city'=>'Galle'],
    ['name'=>'Jaffna Teaching Hospital',         'phone'=>'021 2222261', 'city'=>'Jaffna'],
    ['name'=>'Trincomalee General Hospital',     'phone'=>'026 2222261', 'city'=>'Trincomalee'],
    ['name'=>'Anuradhapura Teaching Hospital',   'phone'=>'025 2222261', 'city'=>'Anuradhapura'],
    ['name'=>'Batticaloa Teaching Hospital',     'phone'=>'065 2222261', 'city'=>'Batticaloa'],
    ['name'=>'Kurunegala Teaching Hospital',     'phone'=>'037 2222261', 'city'=>'Kurunegala'],
];

$faqs = [
    'How do I view my medical history?' => 'Click "Medical History" in the left sidebar to see all your past visits, diagnoses, and records.',
    'Can I edit my personal information?' => 'You can update your contact info, city, email, phone, and profile photo. Name, NIC, DOB, and blood type changes must be requested through Admin.',
    'How do I get a new account?' => 'Click "Sign Up" on the login page and submit a registration request. An admin will review and approve it.',
    'How do I download my documents?' => 'Go to "Documents" in the sidebar to view and download all files uploaded by your doctors.',
    'Who can see my medical records?' => 'Only you, authorised medical staff, and system admins can access your records. All access is logged.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — GH+</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button><h1>Contact Us</h1></div>
        <div class="content-area">

            <!-- Helpline Banner -->
            <div style="background:var(--black);color:#fff;border-radius:8px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;">
                <span style="font-size:32px;">📞</span>
                <div>
                    <div style="font-size:12px;color:#aaa;text-transform:uppercase;letter-spacing:1px;">GH+ Helpline — Available 24/7</div>
                    <div style="font-size:24px;font-weight:700;color:var(--red);">1990</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

                <!-- Support Form -->
                <div class="card">
                    <div class="card-header"><h3>Send a Message</h3></div>
                    <div class="card-body">
                        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                        <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category">
                                    <option>Technical</option>
                                    <option>Medical Query</option>
                                    <option>Complaint</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Subject</label>
                                <input type="text" name="subject" placeholder="Brief subject...">
                            </div>
                            <div class="form-group">
                                <label>Message *</label>
                                <textarea name="message" required placeholder="Describe your issue or question..." style="min-height:120px;"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="card">
                    <div class="card-header"><h3>Frequently Asked Questions</h3></div>
                    <div class="card-body">
                        <?php foreach ($faqs as $q => $a): ?>
                        <div style="margin-bottom:16px;border-bottom:1px solid #f0f0f0;padding-bottom:14px;">
                            <p style="font-weight:600;color:var(--black);margin-bottom:6px;">Q: <?= htmlspecialchars($q) ?></p>
                            <p style="font-size:13px;color:var(--gray);"><?= htmlspecialchars($a) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Hospital Contacts -->
            <div class="card" style="margin-top:20px;">
                <div class="card-header"><h3>Hospital Directory</h3></div>
                <div class="card-body" style="padding:0;">
                    <table>
                        <thead>
                            <tr><th>Hospital</th><th>City</th><th>Phone</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hospitals_contact as $h): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($h['name']) ?></td>
                                <td><?= htmlspecialchars($h['city']) ?></td>
                                <td style="color:var(--red);font-weight:600;"><?= htmlspecialchars($h['phone']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>
