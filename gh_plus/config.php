<?php
// includes/config.php
// GH+ Database Configuration — edit before first run

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'gh_plus');

define('SITE_NAME', 'Government Hospital Plus');
define('SITE_SHORT', 'GH+');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');

// Session
session_start();

// Connect
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:2rem;color:#C8102E;">
        <h2>Database Connection Failed</h2>
        <p>Please check your config in <code>includes/config.php</code></p>
        <p>Error: ' . htmlspecialchars($conn->connect_error) . '</p>
    </div>');
}
$conn->set_charset('utf8mb4');

// ============================================================
// Helper functions
// ============================================================

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null) {
    if (!isLoggedIn()) redirect('../index.php');
    if ($role && $_SESSION['role'] !== $role) redirect('../index.php');
}

function sanitize($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

function hashPassword($pass) {
    return password_hash($pass, PASSWORD_BCRYPT);
}

function verifyPassword($pass, $hash) {
    return password_verify($pass, $hash);
}

function calcAge($dob) {
    if (empty($dob) || $dob === '0000-00-00') return null;
    try {
        $birth = new DateTime($dob);
        $today = new DateTime();
        return (int)$birth->diff($today)->y;
    } catch (Exception $e) {
        return null;
    }
}

function ageLabel($dob) {
    $age = calcAge($dob);
    return $age !== null ? $age . ' yrs' : '—';
}

function allowedUpload($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg','jpeg','png','gif','pdf','mp3','mp4','wav','avi','mov']);
}

function uploadFileType($ext) {
    $images = ['jpg','jpeg','png','gif'];
    $pdfs   = ['pdf'];
    $audio  = ['mp3','wav'];
    $video  = ['mp4','avi','mov'];
    if (in_array($ext, $images)) return 'image';
    if (in_array($ext, $pdfs))   return 'pdf';
    if (in_array($ext, $audio))  return 'audio';
    if (in_array($ext, $video))  return 'video';
    return 'other';
}

$sriLankaCities = [
    'Colombo','Kandy','Galle','Jaffna','Trincomalee','Batticaloa',
    'Negombo','Anuradhapura','Polonnaruwa','Ratnapura','Kurunegala',
    'Matara','Badulla','Nuwara Eliya','Vavuniya','Mannar','Kilinochchi',
    'Mullaitivu','Puttalam','Chilaw','Kegalle','Gampaha','Kalutara',
    'Hambantota','Ampara','Monaragala','Matale'
];

$hospitals = [
    'Colombo National Hospital','Kandy Teaching Hospital',
    'Galle Teaching Hospital','Jaffna Teaching Hospital',
    'Trincomalee General Hospital','Anuradhapura Teaching Hospital',
    'Batticaloa Teaching Hospital','Kurunegala Teaching Hospital',
    'Ratnapura General Hospital','Badulla General Hospital',
    'Negombo General Hospital','Kalutara General Hospital',
    'Matara General Hospital','Kegalle General Hospital'
];
