<?php
session_start();

// ==========================================
// 1. 修复路径：引入数据库
// ==========================================
// 使用 __DIR__ 确保无论从哪里调用都能找到上级目录的 database.php
$path_to_db = __DIR__ . '/../database.php';

if (file_exists($path_to_db)) {
    require $path_to_db;
} else {
    // 如果找不到，尝试直接引入（兼容文件在根目录的情况）
    if (file_exists('database.php')) {
        require 'database.php';
    } else {
        die("Error: Cannot find database.php");
    }
}

// 2. 安全检查
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); // 建议跳回根目录登录页
    exit();
}

// 3. 接收 Caption
$caption = '';
if (isset($_POST['caption'])) {
    $caption = trim($_POST['caption']);
}

/* 4. 图片上传逻辑 */
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
// 5. 修复路径：确保上传目录存在
// ==========================================
// 设定上传目录为项目根目录下的 avatars 文件夹
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

/* 6. 更新数据库 */
// 注意：这里存入 ../avatars/... 是为了让你当前的相对路径逻辑能跑通
// 如果以后首页头像不显示，需要修改这里或前端的读取逻辑

// 这里的路径必须以 / 开头，代表从网站根目录开始找
// 对应浏览器网址: http://localhost/ecotrip/avatars/图片名.jpg
$image_path = '/ecotrip/avatars/' . $fname;

$image_path_esc = mysqli_real_escape_string($con, $image_path);
$caption_esc = mysqli_real_escape_string($con, $caption);
$user_id = $_SESSION['user_id'];

$sql = "UPDATE user SET Avatar = '$image_path_esc', Caption = '$caption_esc' WHERE User_ID = '$user_id'";
$result = mysqli_query($con, $sql);

if ($result) {
    $_SESSION['flash'] = 'Profile updated successfully!';
    
    // 更新 Session 中的头像，这样不需要重新登录就能看到变化
    // (假设你的 Session 里并没有存 Avatar，通常只存 ID，这行是可选的)
    // $_SESSION['Avatar'] = $image_path; 
} else {
    $_SESSION['flash'] = 'Database error: ' . mysqli_error($con);
}

mysqli_close($con);
header('Location: profile.php');
exit;
?>