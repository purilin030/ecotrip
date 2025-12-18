<?php
session_start();

// ==========================================
// 1. Fix path: include database
// ==========================================
// Use __DIR__ to ensure database.php is found regardless of call location
$path_to_db = __DIR__ . '/../database.php';

if (file_exists($path_to_db)) {
    require $path_to_db;
} else {
    // If not found, try direct include (compatible when file is in project root)
    if (file_exists('database.php')) {
        require 'database.php';
    } else {
        die("Error: Cannot find database.php");
    }
}

// 2. Security checks
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); // Suggest redirect back to root login page
    exit();
}

// 3. Receive Caption
$caption = '';
if (isset($_POST['caption'])) {
    $caption = trim($_POST['caption']);
}

/* 4. Image upload logic */
if (!isset($_FILES['photo'])) {
    $_SESSION['flash'] = 'Please choose a photo to upload.';
    header('Location: profile.php');
    exit;
}

$file = $_FILES['photo'];

if (!isset($file['error']) || is_array($file['error'])) {
    $_SESSION['flash'] = 'Invalid file upload.';
    header('Location: profile.php');
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash'] = 'Upload error code: ' . $file['error'];
    header('Location: profile.php');
    exit;
}

$maxBytes = 2 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    $_SESSION['flash'] = 'File too large (max 2MB).';
    header('Location: profile.php');
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$ext = '';
if ($mime === 'image/jpeg') { $ext = 'jpg'; } 
else if ($mime === 'image/png') { $ext = 'png'; } 
else {
    $_SESSION['flash'] = 'Only JPG or PNG images are allowed.';
    header('Location: profile.php');
    exit;
}

// ==========================================
// 5. Fix path: ensure upload directory exists
// ==========================================
// Set upload directory to the project's root avatars folder
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/avatars';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$base = date('Ymd_His') . '_' . mt_rand(1000, 9999);
$fname = $base . '.' . $ext;
$target = $uploadDir . '/' . $fname;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    $_SESSION['flash'] = 'Failed to save the uploaded file.';
    header('Location: profile.php');
    exit;
}

/* 6. Update database */
// Note: storing ../avatars/... keeps current relative path logic working
// If homepage avatar later fails to display, modify this or the frontend retrieval logic

// This path must start with / to be absolute from the website root
// Corresponding browser URL: http://localhost/ecotrip/avatars/image_name.jpg
$image_path = '/ecotrip/avatars/' . $fname;

$image_path_esc = mysqli_real_escape_string($con, $image_path);
$caption_esc = mysqli_real_escape_string($con, $caption);
$user_id = $_SESSION['user_id'];

$sql = "UPDATE user SET Avatar = '$image_path_esc', Caption = '$caption_esc' WHERE User_ID = '$user_id'";
$result = mysqli_query($con, $sql);

if ($result) {
    $_SESSION['flash'] = 'Profile updated successfully!';
    
    // Update avatar in Session so changes appear without re-login
    // (If your Session doesn't store Avatar, commonly only ID is stored; this line is optional)
    // $_SESSION['Avatar'] = $image_path; 
} else {
    $_SESSION['flash'] = 'Database error: ' . mysqli_error($con);
}

mysqli_close($con);
header('Location: profile.php');
exit;
?>