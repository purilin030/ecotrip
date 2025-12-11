<?php
session_start();
require '../database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$campaign_id = $_POST['campaign_id'] ?? 0;
$amount = intval($_POST['amount'] ?? 0);

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. 检查用户余额 (Lock Row)
    $stmtUser = $pdo->prepare("SELECT RedeemPoint FROM user WHERE User_ID = ? FOR UPDATE");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user['RedeemPoint'] < $amount) {
        throw new Exception("Insufficient points balance.");
    }

    // 2. 检查活动状态 (Lock Row)
    $stmtCamp = $pdo->prepare("SELECT Current_Points, Target_Points, Status FROM donation_campaign WHERE Campaign_ID = ? FOR UPDATE");
    $stmtCamp->execute([$campaign_id]);
    $camp = $stmtCamp->fetch(PDO::FETCH_ASSOC);

    if (!$camp || $camp['Status'] !== 'Active') {
        throw new Exception("This campaign is no longer active.");
    }

    // 3. 扣除用户积分
    $stmtDeduct = $pdo->prepare("UPDATE user SET RedeemPoint = RedeemPoint - ? WHERE User_ID = ?");
    $stmtDeduct->execute([$amount, $user_id]);

    // 4. 增加活动积分
    $new_current = $camp['Current_Points'] + $amount;
    $new_status = $camp['Status'];
    
    // 检查是否达标
    if ($new_current >= $camp['Target_Points']) {
        $new_status = 'Completed';
    }

    $stmtUpdateCamp = $pdo->prepare("UPDATE donation_campaign SET Current_Points = ?, Status = ? WHERE Campaign_ID = ?");
    $stmtUpdateCamp->execute([$new_current, $new_status, $campaign_id]);

    // 5. 记录捐赠流水
    $stmtLog = $pdo->prepare("INSERT INTO donation_record (Campaign_ID, User_ID, Amount, Donation_Date) VALUES (?, ?, ?, NOW())");
    $stmtLog->execute([$campaign_id, $user_id, $amount]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Donation successful!', 'completed' => ($new_status === 'Completed')]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>