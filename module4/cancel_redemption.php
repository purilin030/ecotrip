<?php
session_start();
require '../database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// 兼容 FormData 或 JSON
$record_id = 0;
if (isset($_POST['id'])) {
    $record_id = (int)$_POST['id'];
} else {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data) && isset($data['id'])) $record_id = (int)$data['id'];
}

if ($record_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid record id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 锁订单
    $stmtGet = $pdo->prepare("SELECT * FROM redeemrecord WHERE RedeemRecord_ID = ? FOR UPDATE");
    $stmtGet->execute([$record_id]);
    $order = $stmtGet->fetch(PDO::FETCH_ASSOC);

    if (!$order) throw new Exception("Record not found");
    if ((int)$order['Redeem_By'] !== $user_id) throw new Exception("Not allowed");
    if ($order['Status'] !== 'Pending') throw new Exception("Only Pending can be cancelled");

    // 锁 user
    $stmtUser = $pdo->prepare("SELECT RedeemPoint FROM user WHERE User_ID = ? FOR UPDATE");
    $stmtUser->execute([$user_id]);

    // 锁 reward + 取价格
    $stmtReward = $pdo->prepare("SELECT Points_Required FROM reward WHERE Reward_ID = ? FOR UPDATE");
    $stmtReward->execute([$order['Reward_ID']]);
    $reward = $stmtReward->fetch(PDO::FETCH_ASSOC);
    if (!$reward) throw new Exception("Reward not found");

    $qty = (int)$order['Redeem_Quantity'];
    $pointsToRefund = (int)$reward['Points_Required'] * $qty;

    // 还库存
    $stmtStock = $pdo->prepare("UPDATE reward SET Stock = Stock + ? WHERE Reward_ID = ?");
    $stmtStock->execute([$qty, $order['Reward_ID']]);

    // 退积分
    $stmtRefund = $pdo->prepare("UPDATE user SET RedeemPoint = RedeemPoint + ? WHERE User_ID = ?");
    $stmtRefund->execute([$pointsToRefund, $user_id]);

    // 删除记录（你的 database.sql 的 Status enum 没有 'Cancelled'）
    $stmtDel = $pdo->prepare("DELETE FROM redeemrecord WHERE RedeemRecord_ID = ?");
    $stmtDel->execute([$record_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Cancelled. Points refunded.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
