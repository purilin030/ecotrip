<?php
// === module3/admin_submission_detail.php ===
$path_to_db = __DIR__ . '/../database.php';
$path_to_header = __DIR__ . '/../header.php';
require_once $path_to_db;

if (session_status() === PHP_SESSION_NONE)
    session_start();

if (file_exists($path_to_header)) {
    $page_title = "Review Submission";
    include $path_to_header;
} else {
    echo '<!DOCTYPE html><html lang="en"><head><script src="https://cdn.tailwindcss.com"></script><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"></head><body class="bg-gray-50">';
}

// 权限检查 (简单示例)
if (!isset($_SESSION['user_id'])) {
    // header("Location: ../index.php"); 
    // exit(); 
}

// 获取 ID
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// 获取是否处于编辑模式
$edit_mode = isset($_GET['edit']) && $_GET['edit'] == '1';

// 查询数据
// 注意：这里已经查询了 u.Avatar，所以我们可以直接使用
$sql = "SELECT s.*, c.Title as Challenge_Title, c.Points, u.First_Name, u.Last_Name, u.Avatar 
        FROM submissions s 
        JOIN challenge c ON s.Challenge_ID = c.Challenge_ID
        JOIN user u ON s.User_ID = u.User_ID
        WHERE s.Submission_ID = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data)
    die("<div class='p-10 text-center text-red-500'>Submission not found.</div>");

// 判断当前状态
$is_pending = (strtolower($data['Status']) == 'pending');
// 决定是否显示表单
$show_form = $is_pending || $edit_mode;
?>

<main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <a href="admin_verification_list.php"
            class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back to Queue
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">

        <div class="md:col-span-7">
            <div class="bg-white shadow rounded-lg overflow-hidden h-full flex flex-col">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Proof Photo</h3>
                </div>
                <div class="p-4 flex-grow flex items-center justify-center bg-gray-100 min-h-[500px]">
                    <?php if (!empty($data['Photo'])): ?>
                        <img src="<?php echo htmlspecialchars($data['Photo']); ?>"
                            class="max-w-full max-h-[600px] object-contain rounded shadow-sm" alt="Proof">
                    <?php else: ?>
                        <div class="text-center text-gray-400">
                            <i class="fa-regular fa-image text-6xl mb-3"></i>
                            <p>No photo uploaded.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="md:col-span-5">
            <div class="bg-white shadow rounded-lg overflow-hidden sticky top-24">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="text-lg font-bold text-gray-900">Review Submission</h5>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        #<?php echo $submission_id; ?>
                    </span>
                </div>

                <div class="px-6 py-6 space-y-6">

                    <div class="flex items-center pb-4 border-b border-gray-100">
                        <?php
                        // === avatar ===
                        $fullName = $data['First_Name'] . ' ' . $data['Last_Name'];

                        // default avatar
                        $default_avatar = "https://ui-avatars.com/api/?name=" . urlencode($fullName) . "&background=random&color=fff&size=64";

                        // check if custom avatar is 
                        // 拼接物理路径进行检查
                        // 拼接物理路径进行检查
                        $phys_path = $_SERVER['DOCUMENT_ROOT'] . $data['Avatar']; // 改成 $data
                        
                        if (!empty($data['Avatar']) && file_exists($phys_path)) { // 改成 $data
                            // 数据库里已经是 /ecotrip/avatars/... 直接用
                            $display_avatar = $data['Avatar']; // 改成 $data
                        } else {
                            $display_avatar = $default_avatar;
                        }
                        ?>
                        <img class="h-10 w-10 rounded-full object-cover" src="<?php echo $display_avatar; ?>"
                            alt="User Avatar">

                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fullName); ?></p>
                            <p class="text-xs text-gray-500">Member</p>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Challenge</label>
                        <p class="text-gray-900 font-medium text-lg leading-tight">
                            <?php echo htmlspecialchars($data['Challenge_Title']); ?>
                        </p>
                        <span
                            class="inline-block mt-1 text-xs font-medium text-brand-600 bg-brand-50 px-2 py-0.5 rounded">
                            <?php echo $data['Points']; ?> Points
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">User
                            Caption</label>
                        <div class="text-sm text-gray-600 italic bg-gray-50 p-3 rounded border border-gray-100">
                            "<?php echo nl2br(htmlspecialchars($data['Caption'])); ?>"
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <?php if ($show_form): ?>
                            <form action="admin_process_approval.php" method="POST">
                                <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Moderator Note</label>
                                    <textarea name="note" rows="3"
                                        class="shadow-sm focus:ring-brand-500 focus:border-brand-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                        placeholder="Reason for approval or rejection..."><?php echo htmlspecialchars($data['Verification_note']); ?></textarea>
                                    <p class="mt-1 text-xs text-gray-400">This note will be visible to the user.</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <button type="submit" name="action" value="approve"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <i class="fa-solid fa-check mr-2"></i> Approve
                                    </button>
                                    <button type="submit" name="action" value="deny"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <i class="fa-solid fa-xmark mr-2"></i> Deny
                                    </button>
                                </div>

                                <?php if (!$is_pending): ?>
                                    <div class="mt-3 text-center">
                                        <a href="?id=<?php echo $submission_id; ?>"
                                            class="text-sm text-gray-500 hover:text-gray-900">Cancel Edit</a>
                                    </div>
                                <?php endif; ?>
                            </form>

                        <?php else: ?>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Current
                                    Decision</label>
                                <?php
                                $st = strtolower($data['Status']);
                                if ($st == 'approved') {
                                    echo '<div class="flex items-center text-green-700 bg-green-50 p-3 rounded-md border border-green-100"><i class="fa-solid fa-circle-check mr-2"></i> Approved</div>';
                                } else {
                                    echo '<div class="flex items-center text-red-700 bg-red-50 p-3 rounded-md border border-red-100"><i class="fa-solid fa-circle-xmark mr-2"></i> Denied</div>';
                                }
                                ?>
                            </div>

                            <div class="mb-6">
                                <label
                                    class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Note</label>
                                <p class="text-sm text-gray-700">
                                    <?php echo !empty($data['Verification_note']) ? htmlspecialchars($data['Verification_note']) : '<span class="text-gray-400 italic">No note provided.</span>'; ?>
                                </p>
                            </div>

                            <div class="mt-4">
                                <a href="?id=<?php echo $submission_id; ?>&edit=1"
                                    class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                    <i class="fa-solid fa-pen-to-square mr-2"></i> Change Decision
                                </a>
                            </div>

                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php';
include '../footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitter = e.submitter; // 获取触发提交的那个按钮
            if (!submitter) return;

            // 1. 创建一个隐藏 input 来传递被点击按钮的 value (approve/deny)
            // 因为禁用按钮后，它的 value 不会被 POST 提交
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = submitter.name;
            hiddenInput.value = submitter.value;
            this.appendChild(hiddenInput);

            // 2. 视觉反馈：禁用所有提交按钮
            const buttons = this.querySelectorAll('button[type="submit"]');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                
                // 给被点击的按钮添加 Loading 文字
                if (btn === submitter) {
                    const originalText = btn.innerText;
                    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Processing...';
                }
            });
        });
    });
});
</script>
</body>

</html>