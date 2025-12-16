<?php
session_start();
require '../database.php'; // 确保这里不仅有 $con 还有 $pdo

// ==================================================
// 1. 🛡️ 安全检查：必须是 Admin (Role 1)
// ==================================================
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='../module1/login.php';</script>"; 
    exit;
}

// 再次核对数据库权限 (防止 Session 伪造)
$stmtAuth = $pdo->prepare("SELECT Role FROM user WHERE User_ID = ?");
$stmtAuth->execute([$_SESSION['user_id']]);
$currentUser = $stmtAuth->fetch();

if (!$currentUser || $currentUser['Role'] != 1) {
    die("<div class='p-10 text-center text-red-600 font-bold bg-white'>⛔ Access Denied: Administrators only.</div>");
}

// ==================================================
// 2. ⚙️ 处理 POST 请求 (发货 / 退单)
// ==================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // 响应 JSON
    
    $action = $_POST['action'] ?? '';
    $record_id = $_POST['record_id'] ?? 0;

    try {
        if ($action === 'fulfill') {
            // --- 发货逻辑 ---
            $status = "Delivered";
            $admin_note = $_POST['admin_note'] ?? 'Order Fulfilled';
            $proof_path = null;

            // 图片上传
            if (isset($_FILES['proof_photo']) && $_FILES['proof_photo']['error'] == 0) {
                if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                $ext = pathinfo($_FILES["proof_photo"]["name"], PATHINFO_EXTENSION);
                $file_name = "proof_" . time() . "_" . $record_id . "." . $ext;
                $target_file = "uploads/" . $file_name;
                
                if (move_uploaded_file($_FILES["proof_photo"]["tmp_name"], $target_file)) {
                    $proof_path = $target_file; // 存入数据库的路径 (相对路径)
                }
            }

            // 更新数据库
            $sql = "UPDATE redeemrecord SET Status = ?, Admin_Note = ?";
            $params = [$status, $admin_note];
            
            if ($proof_path) {
                $sql .= ", Proof_Photo = ?";
                $params[] = $proof_path;
            }
            
            $sql .= " WHERE RedeemRecord_ID = ?";
            $params[] = $record_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Order marked as Delivered!']);
            exit;

        } elseif ($action === 'reject') {
            // --- 🛑 退单逻辑 (带事务) ---
            $pdo->beginTransaction();

            // A. 获取订单详情 (锁行)
            $stmtGet = $pdo->prepare("SELECT * FROM redeemrecord WHERE RedeemRecord_ID = ? FOR UPDATE");
            $stmtGet->execute([$record_id]);
            $order = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$order || $order['Status'] == 'Delivered') {
                throw new Exception("Cannot reject this order (Already delivered or invalid).");
            }

            // 获取商品价格
            $stmtReward = $pdo->prepare("SELECT Points_Required FROM reward WHERE Reward_ID = ?");
            $stmtReward->execute([$order['Reward_ID']]);
            $reward = $stmtReward->fetch(PDO::FETCH_ASSOC);
            $pointsToRefund = $reward['Points_Required'] * $order['Redeem_Quantity'];

            // B. 退还库存
            $stmtStock = $pdo->prepare("UPDATE reward SET Stock = Stock + ? WHERE Reward_ID = ?");
            $stmtStock->execute([$order['Redeem_Quantity'], $order['Reward_ID']]);

            // C. 退还积分
            $stmtRefund = $pdo->prepare("UPDATE user SET RedeemPoint = RedeemPoint + ? WHERE User_ID = ?");
            $stmtRefund->execute([$pointsToRefund, $order['Redeem_By']]);

            // D. 删除记录 (或者设为 Cancelled，取决于你的数据库设计。这里为了简单直接删除，或者你可以改 Status='Cancelled' 如果数据库 Enum 支持)
            // 假设 database.sql 里 Status Enum 不包含 'Cancelled'，我们这里选择删除记录，
            // 或者更新 Admin_Note 并保留记录但状态仍为 Pending (不推荐)。
            // 最佳实践是修改数据库 Enum。这里演示 **删除记录** (硬取消)。
            $stmtDel = $pdo->prepare("DELETE FROM redeemrecord WHERE RedeemRecord_ID = ?");
            $stmtDel->execute([$record_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Order rejected & points refunded!']);
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// ==================================================
// 3. 🔍 查询与筛选 (服务端处理)
// ==================================================
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT r.*, u.First_Name, u.Last_Name, u.Email, u.Avatar, rw.Type as RewardType 
        FROM redeemrecord r
        JOIN user u ON r.Redeem_By = u.User_ID 
        JOIN reward rw ON r.Reward_ID = rw.Reward_ID 
        WHERE 1=1";

$params = [];

// 状态筛选
if ($filter == 'pending') {
    $sql .= " AND r.Status != 'Delivered'";
} elseif ($filter == 'delivered') {
    $sql .= " AND r.Status = 'Delivered'";
}

// 关键词搜索 (搜名字、邮箱或物品名)
if (!empty($search)) {
    $sql .= " AND (u.First_Name LIKE ? OR u.Last_Name LIKE ? OR r.Reward_Name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY r.Redeem_Date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

require '../header.php';
require '../background.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Redemption Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans text-gray-800">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
   <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">📦 Redemption Management</h1>
            <p class="text-gray-500 mt-1">Process requests, manage shipping, and handle refunds.</p>
            
            <a href="exportExcel.php" class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2 w-fit transition">
                <i class="fa-solid fa-file-csv"></i> Export Report
            </a>
        </div>
        
        <form method="GET" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto bg-white p-2 rounded-xl shadow-sm border border-gray-200">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search user or item..." 
                       class="pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none w-full sm:w-64">
            </div>
            
            <div class="flex bg-gray-100 p-1 rounded-lg">
                <button type="button" onclick="window.location.href='?filter=all'" 
                        class="px-4 py-1.5 rounded-md text-sm font-bold transition <?= $filter=='all' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                    All
                </button>
                <button type="button" onclick="window.location.href='?filter=pending'"
                        class="px-4 py-1.5 rounded-md text-sm font-bold transition <?= $filter=='pending' ? 'bg-white text-yellow-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                    Pending
                </button>
                <button type="button" onclick="window.location.href='?filter=delivered'"
                        class="px-4 py-1.5 rounded-md text-sm font-bold transition <?= $filter=='delivered' ? 'bg-white text-green-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                    Delivered
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold tracking-wider">
                    <tr>
                        <th class="p-4 border-b">ID</th>
                        <th class="p-4 border-b">User</th>
                        <th class="p-4 border-b">Reward Details</th>
                        <th class="p-4 border-b">Date</th>
                        <th class="p-4 border-b">Status</th>
                        <th class="p-4 border-b text-right min-w-[150px]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (count($rows) == 0): ?>
                        <tr><td colspan="6" class="p-12 text-center text-gray-400 text-sm">No redemption records found matching your criteria.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row): 
                        $avatar = !empty($row['Avatar']) ? $row['Avatar'] : "https://ui-avatars.com/api/?name=" . $row['First_Name'];
                    ?>
                        <tr class="hover:bg-blue-50/30 transition-colors group">
                            <td class="p-4 text-gray-400 text-xs font-mono">#<?= $row['RedeemRecord_ID']; ?></td>
                            
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <img src="<?= htmlspecialchars($avatar) ?>" class="h-8 w-8 rounded-full object-cover border border-gray-200">
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></div>
                                        <div class="text-xs text-gray-400"><?= htmlspecialchars($row['Email']); ?></div>
                                    </div>
                                </div>
                            </td>

                            <td class="p-4">
                                <div class="text-gray-900 font-medium text-sm"><?= htmlspecialchars($row['Reward_Name']); ?></div>
                                <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                    <span class="bg-gray-100 px-1.5 py-0.5 rounded border">x<?= $row['Redeem_Quantity']; ?></span>
                                    <span class="<?= $row['RewardType'] == 'Physical' ? 'text-orange-500' : 'text-purple-500' ?>">
                                        <?= $row['RewardType'] == 'Physical' ? '<i class="fa-solid fa-box"></i> Physical' : '<i class="fa-solid fa-ticket"></i> Virtual' ?>
                                    </span>
                                </div>
                            </td>

                            <td class="p-4 text-sm text-gray-500 whitespace-nowrap">
                                <?= date("M d, Y", strtotime($row['Redeem_Date'])); ?>
                            </td>

                           <td class="p-4">
                                <?php if ($row['Status'] == 'Delivered'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                        <i class="fa-solid fa-check mr-1"></i> Delivered
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200">
                                        <i class="fa-regular fa-clock mr-1"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-2 opacity-100 sm:opacity-80 group-hover:opacity-100 transition-opacity">
                                    
                                    <?php if ($row['Status'] != 'Delivered'): ?>
                                        <button type="button" 
                                                onclick="rejectOrder(<?= $row['RedeemRecord_ID'] ?>)"
                                                class="text-red-500 hover:text-red-700 bg-white border border-red-200 hover:bg-red-50 p-2 rounded-lg transition"
                                                title="Reject & Refund">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>

                                        <button type="button" 
                                                onclick="fulfillOrder(<?= $row['RedeemRecord_ID'] ?>, '<?= htmlspecialchars($row['Reward_Name'], ENT_QUOTES) ?>', '<?= $row['RewardType'] ?>')"
                                                class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-2 rounded-lg font-bold shadow-sm transition flex items-center gap-1">
                                            <span>Fulfill</span> <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                    <?php else: ?>
                                        <?php if ($row['Proof_Photo']): ?>
                                            <button onclick="showProof('<?= htmlspecialchars($row['Proof_Photo']) ?>')" 
                                                    class="text-blue-500 hover:text-blue-700 text-xs font-bold underline">
                                                View Proof
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">Completed</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // 1. 发货逻辑 (Fulfill)
    async function fulfillOrder(recordId, rewardName, rewardType) {
        let formData = new FormData();
        formData.append('action', 'fulfill');
        formData.append('record_id', recordId);

        if (rewardType === 'Physical') {
            // 物理商品：必须上传照片
            const { value: file } = await Swal.fire({
                title: '📦 Ship Physical Item',
                text: `Upload shipping proof for "${rewardName}"`,
                input: 'file',
                inputAttributes: { 'accept': 'image/*', 'aria-label': 'Upload proof photo' },
                showCancelButton: true,
                confirmButtonText: 'Upload & Complete',
                confirmButtonColor: '#10b981',
                inputValidator: (value) => {
                    return !value && 'You need to upload a proof photo!'
                }
            });
            
            if (!file) return;
            formData.append('proof_photo', file);
            formData.append('admin_note', 'Shipped via Courier');

        } else {
            // 虚拟商品：填写备注/卡密
            const { value: note } = await Swal.fire({
                title: '🎟️ Issue Virtual Reward',
                text: `Enter voucher code or note for "${rewardName}"`,
                input: 'text',
                inputValue: 'Auto-Generated Code',
                showCancelButton: true,
                confirmButtonText: 'Issue Reward',
                confirmButtonColor: '#3b82f6'
            });

            if (note === undefined) return; // Cancelled
            formData.append('admin_note', note || 'Sent via Email');
        }

        submitForm(formData);
    }

    // 2. 退单逻辑 (Reject)
    function rejectOrder(recordId) {
        Swal.fire({
            title: 'Reject & Refund?',
            text: "This will remove the record, refund points to user, and restore stock.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#cbd5e1',
            confirmButtonText: 'Yes, Reject it!'
        }).then((result) => {
            if (result.isConfirmed) {
                let formData = new FormData();
                formData.append('action', 'reject');
                formData.append('record_id', recordId);
                submitForm(formData);
            }
        });
    }

    // 3. 通用提交函数
    function submitForm(formData) {
        Swal.fire({ title: 'Processing...', didOpen: () => Swal.showLoading() });

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                Swal.fire('Success', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Network request failed.', 'error');
        });
    }

    // 4. 查看凭证
    function showProof(url) {
        Swal.fire({
            imageUrl: url,
            imageHeight: 400,
            imageAlt: 'Proof',
            showConfirmButton: false,
            showCloseButton: true
        });
    }
</script>

<?php include '../footer.php'; ?>
</body>
</html>