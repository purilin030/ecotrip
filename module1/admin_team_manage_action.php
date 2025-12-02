<?php
session_start();
require 'database.php';

// 1. 安全检查：必须是 Admin (1) 或 Moderator (2)
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$current_user_id = $_SESSION['user_id'];
$auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
$auth_res = mysqli_query($con, $auth_sql);
$auth_row = mysqli_fetch_assoc($auth_res);

if ($auth_row['Role'] == 0) {
    header("Location: index.php");
    exit();
}

// 2. 接收数据
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = intval($_POST['target_user_id']);
    $team_id = intval($_POST['team_id']);
    $action = $_POST['action_type']; // 'kick' 或 'transfer'

    // ======================================================
    // 逻辑 A: 踢出成员 (Kick)
    // ======================================================
    if ($action === 'kick') {
        // 1. 清空该用户的 Team_ID
        $update_user = "UPDATE user SET Team_ID = NULL WHERE User_ID = '$target_user_id'";
        mysqli_query($con, $update_user);

        // 2. 队伍人数 -1
        $update_team = "UPDATE team SET Total_members = Total_members - 1 WHERE Team_ID = '$team_id'";
        mysqli_query($con, $update_team);

        // 3. 检查被踢的是不是队长，如果是，把队长位置置空 (防止数据不一致)
        $check_owner = mysqli_query($con, "SELECT Owner_ID FROM team WHERE Team_ID = '$team_id'");
        $team_info = mysqli_fetch_assoc($check_owner);
        if ($team_info['Owner_ID'] == $target_user_id) {
            mysqli_query($con, "UPDATE team SET Owner_ID = 0 WHERE Team_ID = '$team_id'");
        }
    }

    // ======================================================
    // 逻辑 B: 转让队长 (Transfer Ownership)
    // ======================================================
    elseif ($action === 'transfer') {
        // 直接更新队伍表的 Owner_ID
        $update_owner = "UPDATE team SET Owner_ID = '$target_user_id' WHERE Team_ID = '$team_id'";
        mysqli_query($con, $update_owner);
    }

    // 操作完成后，跳回该队伍的编辑页
    header("Location: admin_edit_team.php?id=" . $team_id);
    exit();
}
?>