# GH+ Government Hospital Plus
## Installation Guide (XAMPP / localhost)

---

## Requirements
- XAMPP (Apache + MySQL + PHP 7.4+)
- PHP extensions: mysqli, fileinfo, gd

---

## Step 1 — Copy Files
Place the `gh_plus` folder inside:
```
C:\xampp\htdocs\gh_plus\        (Windows)
/Applications/XAMPP/htdocs/gh_plus/    (Mac)
```

---

## Step 2 — Create the Database
1. Start XAMPP — start **Apache** and **MySQL**
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Click **Import** → choose `gh_plus.sql` → click **Go**

OR run in MySQL console:
```bash
mysql -u root -p < gh_plus.sql
```

---

## Step 3 — Configure Database
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // your MySQL username
define('DB_PASS', '');          // your MySQL password (blank for default XAMPP)
define('DB_NAME', 'gh_plus');
```

---

## Step 4 — Set Uploads Folder Permissions
Make sure the `uploads/` folder is writable:
- Windows: Right-click → Properties → Security → Full Control
- Linux/Mac: `chmod 775 uploads/`

---

## Step 5 — Add the Logo
Copy your `logo.png` file into the `assets/` folder:
```
gh_plus/assets/logo.png
```
Also add a `default.png` (placeholder avatar) in the same folder.

---

## Step 6 — Open the System
Visit: **http://localhost/gh_plus/**

---

## Default Admin Login
| Field    | Value         |
|----------|---------------|
| NIC      | 000000000V    |
| Password | Admin@1234    |


## Default Patient Login
| Field    | Value         |
|----------|---------------|
| NIC      | 000000000001    |
| Password | 000000000001   |

| NIC      | 000000000002    |
| Password | 000000000002   |

| NIC      | 000000000003    |
| Password | 000000000003   |

| NIC      | 000000000004    |
| Password | 000000000004   |


## Default Patient Login
| Field    | Value         |
|----------|---------------|
| NIC      | 000000000005    |
| Password | 000000000005   |

| NIC      | 000000000006    |
| Password | 000000000006   |

| NIC      | 000000000007    |
| Password | 000000000007   |

| NIC      | 000000000008    |
| Password | 000000000008   |



**⚠ Change the admin password immediately after first login!**

---

## Project File Structure
```
gh_plus/
├── index.php               ← Login page
├── signup.php              ← Registration (patient + staff)
├── logout.php
├── forgot_password.php
├── gh_plus.sql             ← Database schema + default admin
│
├── includes/
│   ├── config.php          ← DB config + helper functions
│   └── sidebar.php         ← Reusable sidebar for all dashboards
│
├── css/
│   └── style.css           ← Red/White/Black theme
│
├── js/
│   └── main.js             ← Frontend: tabs, modals, NIC validate, etc.
│
├── patient/
│   ├── dashboard.php       ← Patient home
│   ├── history.php         ← Medical history timeline
│   ├── prescriptions.php   ← All prescriptions
│   ├── documents.php       ← All files/docs
│   ├── profile.php         ← Profile settings (photo, password, contact)
│   └── contact.php         ← Support form + hospital directory
│
├── doctor/
│   ├── search.php          ← Search patients
│   ├── patient_profile.php ← View patient + upload records
│   ├── my_patients.php     ← Grid of patients treated
│   └── profile.php         ← Doctor profile + password
│
├── admin/
│   ├── dashboard.php       ← Admin home + stats
│   ├── patients.php        ← Manage + edit + delete patients
│   ├── staff.php           ← Manage + edit + delete staff
│   ├── approvals.php       ← Approve/reject registrations
│   ├── reports.php         ← System reports + support messages
│   └── audit.php           ← Full audit log
│
├── uploads/                ← All uploaded files (photos, docs)
│   └── .htaccess           ← Security: blocks PHP execution in uploads
│
└── assets/
    ├── logo.png            ← Your GH+ logo
    └── default.png         ← Default avatar photo
```

---

## Role Access Summary
| Action                        | Patient | Doctor | Admin |
|-------------------------------|:-------:|:------:|:-----:|
| View own medical history      | ✅      | —      | —     |
| View any patient history      | ❌      | ✅     | ✅    |
| Upload new medical records    | ❌      | ✅     | ✅    |
| Edit existing records         | ❌      | ❌     | ✅    |
| Delete records/accounts       | ❌      | ❌     | ✅    |
| Approve registrations         | ❌      | ❌     | ✅    |
| Change own password/photo     | ✅      | ✅     | ✅    |

---

## Security Notes
- Passwords hashed with PHP `password_hash()` (bcrypt)
- All inputs sanitized with `mysqli_real_escape_string()`
- File uploads restricted to images, PDFs, audio, video only
- PHP execution blocked inside `uploads/` folder via `.htaccess`
- All user actions are recorded in `audit_log` table
- Session-based authentication with role enforcement on every page

---

*GH+ — Government Hospital Plus | Sri Lanka*
