<?php
// === module3/action_completed.php ===
$path_to_db = __DIR__ . '/../database.php';
$path_to_header = __DIR__ . '/../header.php';

// 1. 确保连接数据库 (这对 Header 显示正确的头像/角色很重要)
if (file_exists($path_to_db)) {
    require_once $path_to_db;
}

if (session_status() === PHP_SESSION_NONE) session_start();

// 简单的权限检查
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); exit();
}

// 设置页面标题
$page_title = "Action Completed";

// 2. 加载 Header
if (file_exists($path_to_header)) {
    include $path_to_header;
} else {
    // 备用 Header，防止路径错误导致白屏
    echo '<!DOCTYPE html><html lang="en"><head><script src="https://cdn.tailwindcss.com"></script><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"></head><body class="bg-gray-50">';
}

// 获取参数
$status = isset($_GET['status']) ? strtolower($_GET['status']) : 'processed';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 根据状态定义颜色和图标
if ($status == 'approved') {
    $icon = 'fa-circle-check';
    $color_class = 'text-green-600';
    $bg_class = 'bg-green-100';
    $title_text = 'Submission Approved!';
    $desc_text = "Submission #$id has been successfully verified and points have been awarded.";
} elseif ($status == 'denied') {
    $icon = 'fa-circle-xmark';
    $color_class = 'text-red-600';
    $bg_class = 'bg-red-100';
    $title_text = 'Submission Denied';
    $desc_text = "Submission #$id has been denied. The user will see your moderation note.";
} else {
    $icon = 'fa-info-circle';
    $color_class = 'text-blue-600';
    $bg_class = 'bg-blue-100';
    $title_text = 'Action Completed';
    $desc_text = "Submission #$id has been processed.";
}
?>

<main class="flex-grow container mx-auto px-4 py-12 flex flex-col items-center justify-center min-h-[60vh]">
    <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md w-full border-t-4 <?php echo ($status == 'approved' ? 'border-green-500' : 'border-red-500'); ?>">
        
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full <?php echo $bg_class; ?> mb-6">
            <i class="fa-solid <?php echo $icon; ?> text-4xl <?php echo $color_class; ?>"></i>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2"><?php echo $title_text; ?></h2>
        <p class="text-gray-600 mb-8"><?php echo $desc_text; ?></p>

        <div class="space-y-3">
            <a href="admin_verification_list.php" class="block w-full px-4 py-2 bg-brand-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 focus:outline-none transition">
                <i class="fa-solid fa-list mr-2"></i> Return to Queue
            </a>
            
            <a href="admin_submission_detail.php?id=<?php echo $id; ?>" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition">
                <i class="fa-solid fa-eye mr-2"></i> View Submission Again
            </a>
        </div>
    </div>
</main>

<?php
// 3. 补充底部的背景和 Footer，保持页面完整
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php')) {
    include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php';
}
if (file_exists(__DIR__ . '/../footer.php')) {
    include __DIR__ . '/../footer.php';
}
?>

</body>
</html>