<?php
// === module3/admin_process_approval.php ===
// 引入数据库连接
require_once __DIR__ . '/../database.php';

// 开启 Session 以获取当前登录的管理员 ID
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查是否登录 (假设 $_SESSION['user_id'] 是当前管理员)
if (!isset($_SESSION['user_id'])) {
    die("Error: Access denied. Please log in first.");
}
$admin_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sub_id = $_POST['submission_id'];
    $action = $_POST['action']; // 'approve' 或 'deny'
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    $action_date = date("Y-m-d");

    $check_sql = "SELECT Status FROM submissions WHERE Submission_ID = ?";
    $check_stmt = $con->prepare($check_sql);
    $check_stmt->bind_param("i", $sub_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $current_data = $check_result->fetch_assoc();
    $check_stmt->close();

    // 2. 如果状态已经不是 'Pending'，说明已经被处理过，直接终止
    // 注意：这里假设未处理状态必须是 'Pending'。如果不等于 Pending，说明已经是 Approved 或 Denied
    if (!$current_data || strtolower($current_data['Status']) !== 'pending') {
        echo "<script>
            alert('This submission has already been processed.'); 
            window.location.href='admin_verification_list.php';
        </script>";
        exit(); // 停止脚本运行，防止重复加分
    }
    // === 新增代码结束 ===

    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    // ... 后续原有逻辑保持不变 ...

    // 准备 SQL 语句变量
    $status_text = "";

    if ($action == 'approve') {
        $status_text = "Approved";

        // --- 0. 获取挑战积分和用户信息 (新增) ---
        // 我们需要知道这个 Submission 对应哪个 User，以及对应的 Challenge 有多少分
        $info_sql = "SELECT s.User_ID, s.Team_ID, c.Points 
                     FROM submissions s 
                     JOIN challenge c ON s.Challenge_ID = c.Challenge_ID 
                     WHERE s.Submission_ID = ?";
        $info_stmt = $con->prepare($info_sql);
        $info_stmt->bind_param("i", $sub_id);
        $info_stmt->execute();
        $info_result = $info_stmt->get_result();

        if ($info_result->num_rows > 0) {
            $info_data = $info_result->fetch_assoc();
            $target_user_id = $info_data['User_ID'];
            $points_to_add = $info_data['Points'];
            // 处理 Team_ID，如果为 NULL 则设为 0
            // If Team_ID is empty or 0, set it to NULL for the database
            $team_id = (!empty($info_data['Team_ID']) && $info_data['Team_ID'] != 0) ? $info_data['Team_ID'] : null;

            // --- 1. 更新 User 表积分 (新增) ---
            // 同时增加 Point (总分) 和 RedeemPoint (可兑换分)
            $update_user_sql = "UPDATE user SET Point = Point + ?, RedeemPoint = RedeemPoint + ? WHERE User_ID = ?";
            $update_user_stmt = $con->prepare($update_user_sql);
            $update_user_stmt->bind_param("iii", $points_to_add, $points_to_add, $target_user_id);
            $update_user_stmt->execute();
            $update_user_stmt->close();

            // --- 2. 插入 PointsLedger 记录 (新增) ---
            // 记录这次积分获取的来源
            $ledger_sql = "INSERT INTO pointsledger (Points_Earned, Earned_Date, User_ID, Submission_ID, Team_ID) 
                           VALUES (?, ?, ?, ?, ?)";
            $ledger_stmt = $con->prepare($ledger_sql);
            // 注意：Earned_Date 使用当前日期 $action_date
            $ledger_stmt->bind_param("isiii", $points_to_add, $action_date, $target_user_id, $sub_id, $team_id);
            $ledger_stmt->execute();
            $ledger_stmt->close();
        }
        $info_stmt->close();

        // --- 3. QR Code 生成逻辑 (保留原有逻辑) ---
        $folder_path = "../qr_code/";
        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0777, true);
        }

        // 准备文件名
        $file_name = "qr_" . $sub_id . "_" . time() . ".png";
        $local_file_path = $folder_path . $file_name;
        // 存入数据库的相对路径
        $db_qr_path = "../qr_code/" . $file_name;

        // 从 API 下载图片
        $qr_content = "Submission-" . $sub_id . "-Verified";
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_content);

        // 使用 file_get_contents
        $image_data = file_get_contents($api_url);

        if ($image_data) {
            file_put_contents($local_file_path, $image_data);

            // 更新 Submission 表 (状态 + QR Code + Note)
            $sql = "UPDATE submissions SET Status = 'Approved', Verification_note = ?, QR_Code = ? WHERE Submission_ID = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssi", $note, $db_qr_path, $sub_id);
            $stmt->execute();
        } else {
            die("Error generating QR Code API response.");
        }

    } elseif ($action == 'deny') {
        $status_text = "Denied";

        // 更新 Submission 表 (只更新状态和备注)
        $sql = "UPDATE submissions SET Status = 'Denied', Verification_note = ? WHERE Submission_ID = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("si", $note, $sub_id);
        $stmt->execute();
    }

    // --- 4. 插入记录到 Moderation 表 (保留原有逻辑) ---
    if (!empty($status_text)) {
        $mod_sql = "INSERT INTO moderation (Submission_ID, User_ID, Action, Action_date) VALUES (?, ?, ?, ?)";
        $mod_stmt = $con->prepare($mod_sql);
        $mod_stmt->bind_param("iiss", $sub_id, $admin_id, $status_text, $action_date);

        if ($mod_stmt->execute()) {
            // 成功后跳转回列表
            echo "<script>
                alert('Submission has been " . strtolower($status_text) . " successfully.'); 
                window.location.href='admin_verification_list.php';
            </script>";
        } else {
            echo "Error logging moderation history: " . $con->error;
        }
    }
}
?>