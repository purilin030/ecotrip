<?php
// ==========================================
// 1. 配置与数据库连接
// ==========================================
$path_to_db = __DIR__ . '/../database.php';
$path_to_header = __DIR__ . '/../header.php';


// 检查数据库文件是否存在
if (!file_exists($path_to_db)) {
    if (file_exists('database.php')) {
        require_once 'database.php';
    } else {
        die("Error: Cannot find database.php at " . $path_to_db);
    }
} else {
    require_once $path_to_db;
}

// 确保数据库连接变量 $con 存在
if (!isset($con)) {
    die("Error: Database connection variable \$con is not set. Please check database.php.");
}

// ==========================================
// 2. 会话与权限
// ==========================================
// 开启 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 引入顶部导航栏
// header.php 通常会处理部分登录检查，但为了安全，我们在下面再做一次 ID 检查
if (file_exists($path_to_header)) {
    // 设置页面标题
    $page_title = "Submission List";
    include $path_to_header;
} else {
    // 仅用于测试时的 fallback
    echo '<!DOCTYPE html><html lang="en"><head><script src="https://cdn.tailwindcss.com"></script><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"></head><body class="bg-gray-50">';
}

// --- 关键修改：获取当前登录 User ID ---

// 1. 检查是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. 获取当前用户 ID
$user_id = $_SESSION['user_id'];

// (可选) 如果你以后确实想限制只有特定角色能看，可以在这里加 Role 判断
// 但对于“查看我的提交”功能，通常所有 Role 都可以访问。

// ==========================================
// 3. 数据处理逻辑
// ==========================================

// 获取筛选状态，默认为 'All'
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'All';

// --- 构建主查询 SQL (submissions 表) ---
// 这里使用 User_ID = ? 确保只查出当前用户的数据
$sql = "SELECT 
            s.Submission_ID, 
            s.Submission_date, 
            c.Title AS Challenge_Title, 
            s.Status, 
            s.Verification_note,
            s.QR_Code
        FROM submissions s
        JOIN challenge c ON s.Challenge_ID = c.Challenge_ID
        WHERE s.User_ID = ?";

// 根据筛选条件追加 SQL
if ($filter_status != 'All') {
    $sql .= " AND s.Status = ?";
}

$sql .= " ORDER BY s.Submission_date DESC";

// 预处理 SQL
$stmt = $con->prepare($sql);
if (!$stmt) {
    die("SQL Prepare Error: " . $con->error);
}

// 绑定参数
if ($filter_status != 'All') {
    $stmt->bind_param("is", $user_id, $filter_status);
} else {
    $stmt->bind_param("i", $user_id);
}

// 执行查询
$stmt->execute();
$result = $stmt->get_result();

// --- 统计各状态数量 (用于顶部 Tab 显示) ---
$count_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN Status = 'Denied' OR Status = 'Rejected' THEN 1 ELSE 0 END) as denied
    FROM submissions WHERE User_ID = $user_id";

$stats_result = $con->query($count_sql);
if ($stats_result) {
    $stats = $stats_result->fetch_assoc();
} else {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'denied' => 0];
}
?>

<main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Submission List</h1>
            <p class="mt-1 text-sm text-gray-500">Track the status of your sustainability challenges.</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg mb-6 overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex" aria-label="Tabs">
                <?php
                // 定义 Tab 生成逻辑
                $tabs = [
                    'All' => ['label' => 'All Submissions', 'count' => $stats['total']],
                    'Pending' => ['label' => 'Pending', 'count' => $stats['pending']],
                    'Approved' => ['label' => 'Approved', 'count' => $stats['approved']],
                    'Denied' => ['label' => 'Denied', 'count' => $stats['denied']],
                ];

                foreach ($tabs as $key => $tab) {
                    $isActive = ($filter_status == $key);
                    // 动态设置样式
                    $linkClass = $isActive 
                        ? "border-brand-500 text-brand-600" 
                        : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
                    
                    $badgeClass = $isActive 
                        ? "bg-brand-100 text-brand-600" 
                        : "bg-gray-100 text-gray-900";

                    echo '<a href="?status=' . $key . '" class="' . $linkClass . ' w-1/4 py-4 px-1 text-center border-b-2 font-medium text-sm flex justify-center items-center transition">';
                    echo $tab['label'];
                    if ($tab['count'] > 0) {
                        echo '<span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium ' . $badgeClass . '">' . $tab['count'] . '</span>';
                    }
                    echo '</a>';
                }
                ?>
            </nav>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Challenge</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comments</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php 
                                $status = strtolower($row['Status']);
                                // 状态颜色配置
                                if ($status == 'approved') {
                                    $badge = "bg-green-100 text-green-800";
                                    $dot = "text-green-400";
                                } elseif ($status == 'pending') {
                                    $badge = "bg-yellow-100 text-yellow-800";
                                    $dot = "text-yellow-400";
                                } else {
                                    $badge = "bg-red-100 text-red-800";
                                    $dot = "text-red-400";
                                }
                            ?>
                            <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='submission_detail.php?id=<?php echo $row['Submission_ID']; ?>'">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-brand-600">
                                    #<?php echo $row['Submission_ID']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date("d M Y", strtotime($row['Submission_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    <?php echo htmlspecialchars($row['Challenge_Title']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badge; ?>">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 <?php echo $dot; ?>" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        <?php echo ucfirst($row['Status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    <?php echo $row['Verification_note'] ? htmlspecialchars($row['Verification_note']) : '<span class="text-gray-300">-</span>'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($status == 'approved'): ?>
                                        <button 
                                            onclick="event.stopPropagation(); openQrModal('<?php echo $row['Submission_ID']; ?>', '<?php echo $row['QR_Code']; ?>')"
                                            class="text-gray-600 hover:text-brand-600 transition"
                                            title="View QR Code">
                                            <i class="fa-solid fa-qrcode text-lg"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-300"><i class="fa-solid fa-ban"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg font-medium">No submissions found</p>
                                    <p class="text-sm text-gray-400 mb-4">You haven't submitted any proofs for this category yet.</p>
                                    <a href="submit_proof.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                        <i class="fa-solid fa-plus mr-2"></i> Submit Proof
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<div id="qrModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start justify-center">
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-base font-semibold leading-6 text-gray-900 text-center" id="modal-title">Submission Verified</h3>
                            <div class="mt-4 flex flex-col items-center">
                                <p class="text-sm text-gray-500 mb-4">Scan this QR code to verify this achievement.</p>
                                
                                <div class="p-2 bg-white border border-gray-200 rounded-lg shadow-sm">
                                    <img id="modalQrImage" src="" alt="QR Code" class="w-48 h-48 object-contain">
                                </div>
                                
                                <p class="mt-4 text-brand-600 font-bold flex items-center">
                                    <i class="fa-solid fa-circle-check mr-2"></i> Verified
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" onclick="closeQrModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // QR Code Modal Logic
    function openQrModal(id, localPath) {
        const modal = document.getElementById('qrModal');
        const img = document.getElementById('modalQrImage');
        const title = document.getElementById('modal-title');

        // Determine Image Source
        let src = "";
        if (localPath && localPath.trim() !== "") {
             src = localPath;
        } else {
             src = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Submission-" + id + "-Verified";
        }
        
        img.src = src;
        title.innerText = "Submission #" + id + " Verified";
        
        // Show Modal
        modal.classList.remove('hidden');
    }

    function closeQrModal() {
        document.getElementById('qrModal').classList.add('hidden');
    }

    // Close on click outside
    window.onclick = function(event) {
        // 如果点击的是半透明背景层 (bg-opacity-75 的兄弟或其本身，取决于布局)，这里简单检查类名
        if (event.target.classList.contains('bg-opacity-75')) {
             closeQrModal();
        }
    }
</script>

<?php
// ==========================================
// 5. 资源清理
// ==========================================
if (isset($stmt)) $stmt->close();
if (isset($con)) $con->close();

// 结束 HTML 标签
?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php';
include '../footer.php';
?>
</body>
</html>