<?php
session_start();
require 'database.php';

// 1. 安全检查
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_user_id = $_SESSION['user_id'];
    $target_user_id = intval($_POST['target_user_id']); // 要操作的目标成员 ID
    $action = $_POST['action_type']; // 'kick' 或 'transfer'

    // 2. 获取当前用户的队伍信息，并确认他是队长
    $check_sql = "SELECT * FROM team WHERE Owner_ID = '$current_user_id'";
    $check_res = mysqli_query($con, $check_sql);

    if (mysqli_num_rows($check_res) == 0) {
        $_SESSION['flash_error'] = "You are not authorized to manage this team.";
        header("Location: team.php");
        exit();
    }

    $team = mysqli_fetch_assoc($check_res);
    $team_id = $team['Team_ID'];

    // 3. 再次确认目标成员确实在这个队里
    $target_check_sql = "SELECT User_ID FROM user WHERE User_ID = '$target_user_id' AND Team_ID = '$team_id'";
    $target_check_res = mysqli_query($con, $target_check_sql);

    if (mysqli_num_rows($target_check_res) == 0) {
        $_SESSION['flash_error'] = "Member not found in your team.";
        header("Location: team_edit.php");
        exit();
    }

    // ======================================================
    // 逻辑 A: 踢出成员 (Kick)
    // ======================================================
    if ($action === 'kick') {
        // 不允许踢自己
        if ($target_user_id == $current_user_id) {
            $_SESSION['flash_error'] = "You cannot kick yourself.";
        } else {
            // 1. 清空该用户的 Team_ID
            $update_user = "UPDATE user SET Team_ID = NULL WHERE User_ID = '$target_user_id'";
            mysqli_query($con, $update_user);

            // 2. 队伍人数 -1
            $update_team = "UPDATE team SET Total_members = Total_members - 1 WHERE Team_ID = '$team_id'";
            mysqli_query($con, $update_team);

            $_SESSION['flash_success'] = "Member removed successfully.";
        }
    }

    // ======================================================
    // 逻辑 B: 转让队长 (Transfer Ownership)
    // ======================================================
    elseif ($action === 'transfer') {
        // 1. 更新队伍表的 Owner_ID
        $update_owner = "UPDATE team SET Owner_ID = '$target_user_id' WHERE Team_ID = '$team_id'";
        
        if (mysqli_query($con, $update_owner)) {
            $_SESSION['flash_success'] = "Ownership transferred! You are now a regular member.";
            // 因为你不再是队长，不能留在 edit 页面，必须跳回 info 页面
            header("Location: team_information.php");
            exit();
        } else {
            $_SESSION['flash_error'] = "Transfer failed: " . mysqli_error($con);
        }
    }

    // 默认跳回编辑页
    header("Location: team_edit.php");
    exit();
}
?>