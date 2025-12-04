<?php
// ==========================================
// 1. 配置与连接
// ==========================================
$path_to_db = __DIR__ . '/../database.php';
$path_to_header = __DIR__ . '/../header.php';

if (!file_exists($path_to_db)) {
    if (file_exists('database.php')) {
        require_once 'database.php';
    } else {
        die("Error: Cannot find database.php");
    }
} else {
    require_once $path_to_db;
}

if (!isset($con)) {
    if (isset($conn)) { $con = $conn; } 
    else { die("Error: Database connection variable \$con is not set."); }
}

if (session_status() === PHP_SESSION_NONE) session_start();

if (file_exists($path_to_header)) {
    $page_title = "Verification Queue";
    include $path_to_header;
} else {
    echo '<!DOCTYPE html><html lang="en"><head><script src="https://cdn.tailwindcss.com"></script><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"></head><body class="bg-gray-50">';
}

// === 2. 筛选逻辑 ===
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'All';

// 构建 SQL 查询
$sql = "SELECT 
            s.Submission_ID, 
            s.Submission_date, 
            s.Status, 
            s.Verification_note,
            u.First_Name, 
            u.Last_Name, 
            u.Avatar,
            c.Title AS Challenge_Title
        FROM submissions s
        JOIN user u ON s.User_ID = u.User_ID
        JOIN challenge c ON s.Challenge_ID = c.Challenge_ID";

if ($filter_status != 'All') {
    $sql .= " WHERE s.Status = ?";
}

$sql .= " ORDER BY s.Submission_date DESC, s.Submission_ID DESC";

$stmt = $con->prepare($sql);
if (!$stmt) { die("SQL Prepare Error: " . $con->error); }

if ($filter_status != 'All') {
    $stmt->bind_param("s", $filter_status);
}

$stmt->execute();
$result = $stmt->get_result();

// === 3. 统计各状态数量 ===
$count_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN Status = 'Denied' OR Status = 'Rejected' THEN 1 ELSE 0 END) as denied
FROM submissions";

$stats_result = $con->query($count_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'pending' => 0, 'approved' => 0, 'denied' => 0];

// 确保默认值不为null
$stats['pending'] = $stats['pending'] ?? 0;
$stats['approved'] = $stats['approved'] ?? 0;
$stats['denied'] = $stats['denied'] ?? 0;
?>

<main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Verification Queue
            </h2>
            <p class="mt-1 text-sm text-gray-500">Review and manage user proofs.</p>
        </div>

        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <div class="flex flex-col items-center justify-center bg-white px-5 py-2 rounded-lg shadow-sm border border-gray-200">
                <span class="text-2xl font-bold text-yellow-500"><?php echo $stats['pending']; ?></span>
                <span class="text-xs text-gray-500 font-medium uppercase">Pending</span>
            </div>
            <div class="flex flex-col items-center justify-center bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-200">
                <span class="text-2xl font-bold text-green-500"><?php echo $stats['approved']; ?></span>
                <span class="text-xs text-gray-500 font-medium uppercase">Approved</span>
            </div>
            <div class="flex flex-col items-center justify-center bg-white px-6 py-2 rounded-lg shadow-sm border border-gray-200">
                <span class="text-2xl font-bold text-red-500"><?php echo $stats['denied']; ?></span>
                <span class="text-xs text-gray-500 font-medium uppercase">Denied</span>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg mb-6 overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex" aria-label="Tabs">
                <?php
                $tabs = [
                    'All' => ['label' => 'All Submissions', 'count' => $stats['total']],
                    'Pending' => ['label' => 'Pending', 'count' => $stats['pending']],
                    'Approved' => ['label' => 'Approved', 'count' => $stats['approved']],
                    'Denied' => ['label' => 'Denied', 'count' => $stats['denied']],
                ];

                foreach ($tabs as $key => $val) {
                    $active = ($filter_status == $key);
                    $borderClass = $active ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                    $bgClass = $active ? 'bg-brand-50 text-brand-700' : 'bg-gray-100 text-gray-600';

                    // 为 Denied 单独设置红色的 Badge 样式
                    if ($key == 'Denied') {
                        $bgClass = $active ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500';
                    }

                    echo '<a href="?status=' . $key . '" class="' . $borderClass . ' w-1/4 py-4 px-1 text-center border-b-2 font-medium text-sm flex justify-center items-center transition group">';
                    echo $val['label'];
                    
                    // 【关键修改】始终显示 Denied 的数量 (即使是 0)，其他只有 >0 才显示
                    // 或者统一都显示 >= 0，这里我按照你的习惯：Approved 和 Denied 如果是 0 也显示，方便看状态
                    // 原代码是跳过 Approved/Denied 的显示。现在我让它们都显示出来。
                    if ($val['count'] >= 0) {
                        echo '<span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium ' . $bgClass . '">' . $val['count'] . '</span>';
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Challenge</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>

                            <?php
                            // 头像处理
                            $fullName = $row['First_Name'] . ' ' . $row['Last_Name'];
                            $default_avatar = "https://ui-avatars.com/api/?name=" . urlencode($fullName) . "&background=random&color=fff&size=64";
                            
                            // 路径修正：如果数据库存的是相对路径，需要拼上 DOCUMENT_ROOT 检查是否存在
                            // 假设头像存在 /ecotrip/avatars/
                            // 这里做一个简单处理：如果有值就用，没值用默认
                            $display_avatar = !empty($row['Avatar']) ? $row['Avatar'] : $default_avatar;
                            ?>

                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-500">
                                    #<?php echo $row['Submission_ID']; ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($display_avatar); ?>" alt="User Avatar" class="h-full w-full object-cover">
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fullName); ?></div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="block truncate max-w-[200px]" title="<?php echo htmlspecialchars($row['Challenge_Title']); ?>">
                                        <?php echo htmlspecialchars($row['Challenge_Title']); ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <i class="fa-regular fa-clock mr-1.5 text-gray-400"></i>
                                        <?php echo date("M d, Y", strtotime($row['Submission_date'])); ?>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $st = strtolower($row['Status']);
                                    $badgeClass = 'bg-gray-100 text-gray-800';
                                    $dotClass = 'text-gray-400';

                                    if ($st == 'pending') {
                                        $badgeClass = 'bg-yellow-100 text-yellow-800'; $dotClass = 'text-yellow-400';
                                    } elseif ($st == 'approved') {
                                        $badgeClass = 'bg-green-100 text-green-800'; $dotClass = 'text-green-400';
                                    } elseif ($st == 'denied' || $st == 'rejected') {
                                        $badgeClass = 'bg-red-100 text-red-800'; $dotClass = 'text-red-400';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badgeClass; ?>">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 <?php echo $dotClass; ?>" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        <?php echo ucfirst($row['Status']); ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($st == 'pending'): ?>
                                        <a href="admin_submission_detail.php?id=<?php echo $row['Submission_ID']; ?>"
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                            Review <i class="fa-solid fa-pen-to-square ml-1.5"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="admin_submission_detail.php?id=<?php echo $row['Submission_ID']; ?>"
                                            class="text-gray-400 hover:text-brand-600 transition">
                                            Details <i class="fa-solid fa-chevron-right ml-1"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fa-solid fa-clipboard-check text-4xl text-gray-300 mb-3"></i>
                                <p>No submissions found for this filter.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<?php
if (isset($stmt)) $stmt->close();
if (isset($con)) $con->close();
?>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php';
include '../footer.php';
?>
</body>
</html>