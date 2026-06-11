<?php
// signup.php
require_once 'includes/config.php';

if (isLoggedIn()) redirect('index.php');

$step    = $_GET['type'] ?? '';
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitize($conn, $_POST['type'] ?? '');

    // --- Shared fields ---
    $full_name = sanitize($conn, $_POST['full_name'] ?? '');
    $nic       = strtoupper(sanitize($conn, $_POST['nic'] ?? ''));
    $dob       = sanitize($conn, $_POST['dob'] ?? '');
    $gender    = sanitize($conn, $_POST['gender'] ?? '');
    $city      = sanitize($conn, $_POST['city'] ?? '');
    $blood     = sanitize($conn, $_POST['blood_type'] ?? '');
    $email     = sanitize($conn, $_POST['email'] ?? '');
    $phone     = sanitize($conn, $_POST['phone'] ?? '');
    $pass      = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $rawDiseases = isset($_POST['diseases']) ? $_POST['diseases'] : [];
    // If "Other" checked and custom text provided, replace "Other" with the typed value
    $otherText = trim($_POST['other_disease'] ?? '');
    if (in_array('Other', $rawDiseases) && $otherText !== '') {
        $rawDiseases = array_map(fn($d) => $d === 'Other' ? $otherText : $d, $rawDiseases);
    }
    $diseases = implode(',', array_map(fn($d) => $conn->real_escape_string($d), $rawDiseases));
    $desc      = sanitize($conn, $_POST['description'] ?? '');

    // Validation
    if (empty($full_name) || empty($nic) || empty($pass)) {
        $error = 'Please fill in all required fields.';
    } elseif (!preg_match('/^\d{9}[Vv]$|^\d{12}$/', $nic)) {
        $error = 'Invalid NIC format. Use 9-digit+V or 12-digit format.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate NIC
        $chk = $conn->prepare("SELECT id FROM users WHERE nic = ?");
        $chk->bind_param('s', $nic);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = 'An account with this NIC already exists.';
        } else {
            $chk->close();
            $hashed = hashPassword($pass);
            $role   = ($type === 'staff') ? 'doctor' : 'patient';

            $stmt = $conn->prepare("INSERT INTO users (nic, full_name, password, role, email, phone, date_of_birth, gender, city, blood_type, status)
                                    VALUES (?,?,?,?,?,?,?,?,?,?,'pending')");
            $stmt->bind_param('ssssssssss', $nic, $full_name, $hashed, $role, $email, $phone, $dob, $gender, $city, $blood);
            $stmt->execute();
            $uid = $conn->insert_id;
            $stmt->close();

            if ($type === 'patient') {
                $s2 = $conn->prepare("INSERT INTO patient_details (user_id, diseases, description) VALUES (?,?,?)");
                $s2->bind_param('iss', $uid, $diseases, $desc);
                $s2->execute(); $s2->close();
            } else {
                $position = sanitize($conn, $_POST['position'] ?? '');
                $slmc     = sanitize($conn, $_POST['slmc_no'] ?? '');
                $hospital = sanitize($conn, $_POST['hospital'] ?? '');
                $dept     = sanitize($conn, $_POST['department'] ?? '');
                $s2 = $conn->prepare("INSERT INTO staff_details (user_id, position, slmc_no, hospital, department, description) VALUES (?,?,?,?,?,?)");
                $s2->bind_param('isssss', $uid, $position, $slmc, $hospital, $dept, $desc);
                $s2->execute(); $s2->close();
            }

            $success = 'Your registration request has been submitted! An admin will review and activate your account shortly.';
            $step = '';
        }
    }
}

$diseaseList = ['Diabetes','Hypertension','Asthma','Heart Disease','Chronic Kidney Disease','Cancer','Thyroid Disorder','Epilepsy','HIV/AIDS','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="signup-page">

    <div class="signup-header">
        <img src="assets/logo.png" alt="GH+">
        <h1>Government Hospital <span>Plus</span></h1>
        <div style="flex:1;"></div>
        <a href="index.php" style="color:#aaa;font-size:13px;">← Back to Login</a>
    </div>

    <?php if ($success): ?>
        <div class="signup-form-card" style="text-align:center;padding:3rem;">
            <div style="font-size:56px;margin-bottom:1rem;">✅</div>
            <h2 style="color:green;margin-bottom:0.5rem;">Request Submitted!</h2>
            <p style="color:var(--gray);max-width:400px;margin:0 auto 1.5rem;"><?= htmlspecialchars($success) ?></p>
            <a href="index.php" class="btn btn-dark" style="width:auto;display:inline-block;">Go to Login</a>
        </div>

    <?php elseif ($step === ''): ?>
        <!-- Step 1: Choose account type -->
        <h2 style="text-align:center;margin-bottom:0.5rem;">Choose Account Type</h2>
        <p style="text-align:center;color:var(--gray);margin-bottom:2rem;font-size:13px;">Select the type of account you want to create</p>

        <?php if ($error): ?><div class="alert alert-error" style="max-width:600px;margin:0 auto 1rem;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="type-select">
            <div class="type-card" onclick="location.href='signup.php?type=patient'">
                <div class="icon">🧑‍⚕️</div>
                <h3>I am a Patient</h3>
                <p>Register to access your personal medical records and history</p>
            </div>
            <div class="type-card" onclick="location.href='signup.php?type=staff'">
                <div class="icon">🏥</div>
                <h3>I am Medical Staff</h3>
                <p>Register as a doctor, nurse, or other medical professional</p>
            </div>
        </div>

    <?php elseif ($step === 'patient'): ?>
        <!-- Patient registration form -->
        <div class="signup-form-card">
            <h2>🧑‍⚕️ Patient Registration</h2>

            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="type" value="patient">

                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required placeholder="First and Last Name">
                    </div>
                    <div class="form-group">
                        <label>NIC Number *</label>
                        <input type="text" id="nic" name="nic" required placeholder="987654321V or 200012345678" maxlength="12">
                        <small id="nic-msg" style="font-size:11px;"></small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth *</label>
                        <input type="date" name="dob" required>
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <div class="gender-group">
                            <?php $pg = $_POST['gender'] ?? ''; ?>
                            <label class="gender-pill <?= $pg==='male'   ? 'gender-active':'' ?>">
                                <input type="radio" name="gender" value="male" required
                                       <?= $pg==='male'   ? 'checked':'' ?>> ♂ Male
                            </label>
                            <label class="gender-pill <?= $pg==='female' ? 'gender-active':'' ?>">
                                <input type="radio" name="gender" value="female"
                                       <?= $pg==='female' ? 'checked':'' ?>> ♀ Female
                            </label>
                            <label class="gender-pill <?= $pg==='other'  ? 'gender-active':'' ?>">
                                <input type="radio" name="gender" value="other"
                                       <?= $pg==='other'  ? 'checked':'' ?>> ⚧ Other
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>City *</label>
                        <select name="city" required>
                            <option value="">-- Select City --</option>
                            <?php foreach ($sriLankaCities as $c): ?>
                                <option value="<?= $c ?>"><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Blood Type *</label>
                        <select name="blood_type" required>
                            <option value="">-- Select --</option>
                            <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $b): ?>
                                <option><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="07X XXX XXXX">
                    </div>
                </div>

                <div class="form-group">
                    <label>Medical Conditions (select all that apply)</label>
                    <div id="disease-box" style="display:flex;flex-wrap:wrap;gap:10px;padding:12px;
                         background:#f9f9f9;border:1px solid #7b1414;border-radius:6px;">
                        <?php
                        $posted = isset($_POST['diseases']) ? $_POST['diseases'] : [];
                        foreach ($diseaseList as $d):
                            $checked = in_array($d, $posted) ? 'checked' : '';
                        ?>
                        <label class="cb-label">
                            <input type="checkbox" name="diseases[]" value="<?= $d ?>"
                                   <?= $checked ?>
                                   onchange="toggleCb(this)"
                                   <?= $d==='Other' ? 'id="cb-other" onchange="toggleCb(this);toggleOther(this)"' : '' ?>>
                            <?= $d ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <!-- Other text field — shown only when Other is checked -->
                    <div id="other-wrap" style="margin-top:10px;display:<?= in_array('Other',$posted??[]) ? 'block' : 'none' ?>;">
                        <input type="text" name="other_disease"
                               id="other-disease-input"
                               value="<?= htmlspecialchars($_POST['other_disease'] ?? '') ?>"
                               placeholder="Please specify your condition..."
                               style="width:100%;padding:9px 12px;border:1px solid #ccc;
                                      border-radius:6px;font-size:14px;">
                    </div>
                </div>

                <div class="form-group">
                    <label>Additional Notes / Allergies</label>
                    <textarea name="description" placeholder="Any allergies, previous surgeries, or important notes..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <div class="pw-wrap">
                            <input type="password" name="password" required placeholder="Min. 8 characters">
                            <button type="button" class="pw-toggle">👁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <div class="pw-wrap">
                            <input type="password" name="confirm_password" required placeholder="Repeat password">
                            <button type="button" class="pw-toggle">👁</button>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:8px;">
                    <a href="signup.php" class="btn btn-outline" style="flex:1;text-align:center;">← Back</a>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Submit for Approval</button>
                </div>
            </form>
        </div>

    <?php elseif ($step === 'staff'): ?>
        <!-- Staff registration form -->
        <div class="signup-form-card">
            <h2>🏥 Medical Staff Registration</h2>

            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="type" value="staff">

                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required placeholder="Dr. / Full Name">
                    </div>
                    <div class="form-group">
                        <label>Position *</label>
                        <select name="position" required>
                            <option value="">-- Select Position --</option>
                            <?php foreach (['Doctor','Nurse','Surgeon','Radiologist','Lab Technician','Pharmacist','Other Medical Staff'] as $p): ?>
                                <option><?= $p ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>SLMC Registration No. *</label>
                        <input type="text" name="slmc_no" placeholder="Sri Lanka Medical Council No.">
                    </div>
                    <div class="form-group">
                        <label>Hospital *</label>
                        <select name="hospital" required>
                            <option value="">-- Select Hospital --</option>
                            <?php foreach ($hospitals as $h): ?>
                                <option><?= $h ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Department *</label>
                        <input type="text" name="department" required placeholder="e.g. Cardiology, Pediatrics">
                    </div>
                    <div class="form-group">
                        <label>NIC Number *</label>
                        <input type="text" id="nic" name="nic" required placeholder="987654321V or 200012345678" maxlength="12">
                        <small id="nic-msg" style="font-size:11px;"></small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth *</label>
                        <input type="date" name="dob" required>
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <div class="gender-group">
                            <?php $sg = $_POST['gender'] ?? ''; ?>
                            <label class="gender-pill <?= $sg==='male'   ? 'gender-active':'' ?>">
                                <input type="radio" name="gender" value="male" required
                                       <?= $sg==='male'   ? 'checked':'' ?>> ♂ Male
                            </label>
                            <label class="gender-pill <?= $sg==='female' ? 'gender-active':'' ?>">
                                <input type="radio" name="gender" value="female"
                                       <?= $sg==='female' ? 'checked':'' ?>> ♀ Female
                            </label>
                            <label class="gender-pill <?= $sg==='other'  ? 'gender-active':'' ?>">
                                <input type="radio" name="gender" value="other"
                                       <?= $sg==='other'  ? 'checked':'' ?>> ⚧ Other
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>City *</label>
                        <select name="city" required>
                            <option value="">-- Select City --</option>
                            <?php foreach ($sriLankaCities as $c): ?>
                                <option><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Blood Type</label>
                        <select name="blood_type">
                            <option value="">-- Optional --</option>
                            <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $b): ?>
                                <option><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="email@hospital.lk">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="07X XXX XXXX">
                    </div>
                </div>

                <div class="form-group">
                    <label>Qualifications / Credentials</label>
                    <textarea name="description" placeholder="MBBS, MD, specialist certifications..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <div class="pw-wrap">
                            <input type="password" name="password" required placeholder="Min. 8 characters">
                            <button type="button" class="pw-toggle">👁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <div class="pw-wrap">
                            <input type="password" name="confirm_password" required placeholder="Repeat password">
                            <button type="button" class="pw-toggle">👁</button>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:8px;">
                    <a href="signup.php" class="btn btn-outline" style="flex:1;text-align:center;">← Back</a>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Submit for Admin Approval</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

</div>
<script src="js/main.js"></script>
<script>
document.querySelectorAll('.gender-pill input[type="radio"]').forEach(function(rb) {
    rb.addEventListener('change', function() {
        // Deactivate all pills in same group
        document.querySelectorAll('.gender-pill input[name="' + this.name + '"]').forEach(function(r) {
            r.closest('.gender-pill').classList.remove('gender-active');
            // Style radio button when unchecked
            r.style.cssText = `
                appearance: none;
                width: 16px;
                height: 16px;
                border: 2px solid #ddd;
                border-radius: 50%;
                background: white;
            `;
        });
        
        // Activate selected
        this.closest('.gender-pill').classList.add('gender-active');
        
        // Style radio button when checked
        this.style.cssText = `
            appearance: none;
            width: 16px;
            height: 16px;
            border: 2px solid #C8102E;
            border-radius: 50%;
            background: #C8102E;
            box-shadow: inset 0 0 0 3px white;
        `;
    });
});
// ── Checkbox pill toggle ─────────────────────────────────────
function toggleCb(cb) {
    var label = cb.closest('label');
    if (!label) return;
    if (cb.checked) {
        label.style.background    = '#C8102E';
        label.style.color         = '#fff';
        label.style.borderColor   = '#C8102E';
    } else {
        label.style.background    = '#fff';
        label.style.color         = '#1a1a1a';
        label.style.borderColor   = '#ddd';
    }
}

// ── Show/hide the "Other" text field ────────────────────────
function toggleOther(cb) {
    var wrap  = document.getElementById('other-wrap');
    var input = document.getElementById('other-disease-input');
    if (!wrap) return;
    if (cb.checked) {
        wrap.style.display = 'block';
        if (input) input.focus();
    } else {
        wrap.style.display = 'none';
        if (input) input.value = '';
    }
}

// ── Apply pill styles on page load (for validation re-render) ─
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#disease-box input[type="checkbox"]').forEach(function(cb) {
        toggleCb(cb);
        // Wire up the Other checkbox separately if not already set
        if (cb.id === 'cb-other') {
            cb.addEventListener('change', function() { toggleOther(this); });
        }
    });

    document.querySelectorAll('.gender-pill input[type="radio"]').forEach(function(rb) {
        if (rb.checked) {
            rb.closest('.gender-pill').classList.add('gender-active');
        }
    });
});
</script>
</body>
</html>
