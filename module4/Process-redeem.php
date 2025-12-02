<?php
session_start();
require '../database.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$reward_id = $_POST['reward_id'] ?? null;

if (!$reward_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid Reward ID']);
    exit;
}

try{
    $pdo->beginTransaction();
    //NOTE:FOR UPDATE 是用在要读取数据并且修改他,但是在修改之前先锁住数据,防止其他人修改.Avoid Race Condition Case.
    //2.CHECK USER current point [FOR UPDATE]
    $stmtUser = $pdo->prepare("SELECT RedeemPoint FROM user WHERE User_ID = ? FOR UPDATE");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    //3.CHECK Reward Stock and Price FOR UPDATE
    $stmtReward = $pdo->prepare("SELECT Points_Required, Stock, Reward_name FROM reward WHERE Reward_ID = ? FOR UPDATE");
    $stmtReward->execute([$reward_id]);
    $reward = $stmtReward->fetch(PDO::FETCH_ASSOC);

    //4.Back-End Validation Check
    if (!$reward || $reward['Stock'] <= 0) {
        throw new Exception("Out of stock!");
    }
    if ($user['RedeemPoint'] < $reward['Points_Required']) {
        throw new Exception("Not enough points!");
    }

//5.deduct Stock
$updateStock = $pdo->prepare("UPDATE reward SET Stock = Stock - 1 WHERE Reward_ID = ?");
$updateStock -> execute([$reward_id]);

//6.deduct Points
$updatePoints= $pdo->prepare("UPDATE user SET RedeemPoint = RedeemPoint - ? WHERE User_ID = ?");
$updatePoints -> execute([$reward['Points_Required'],$user_id]);

//7.write record
$insertRecord = $pdo->prepare("INSERT INTO redeemrecord (Reward_ID, Reward_Name, Redeem_Quantity, Redeem_By, Redeem_Date) VALUES (?, ?, 1, ?, NOW())");
    $insertRecord->execute([$reward_id, $reward['Reward_name'], $user_id]);
// 提交事务
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Redemption Successful!']);
    
} catch (Exception $e) {
    // 发生错误，回滚所有操作
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
