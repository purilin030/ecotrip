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
    // 【修改】这里多查一个 'Role' 字段，方便后面判断
    $target_check_sql = "SELECT User_ID, Role FROM user WHERE User_ID = '$target_user_id' AND Team_ID = '$team_id'";
    $target_check_res = mysqli_query($con, $target_check_sql);

    if (mysqli_num_rows($target_check_res) == 0) {
        $_SESSION['flash_error'] = "Member not found in your team.";
        header("Location: team_edit.php");
        exit();
    }
    
    // 获取目标成员的数据
    $target_member = mysqli_fetch_assoc($target_check_res);

    // ======================================================
    // 逻辑 A: 踢出成员 (Kick)
    // ======================================================
    if ($action === 'kick') {
        if ($target_user_id == $current_user_id) {
            $_SESSION['flash_error'] = "You cannot kick yourself.";
        } else {
            // 【可选优化】如果想禁止踢 Admin，也可以在这里加判断：
            // if ($target_member['Role'] == 1) { error... }

            $update_user = "UPDATE user SET Team_ID = NULL WHERE User_ID = '$target_user_id'";
            mysqli_query($con, $update_user);

            $update_team = "UPDATE team SET Total_members = Total_members - 1 WHERE Team_ID = '$team_id'";
            mysqli_query($con, $update_team);

            $_SESSION['flash_success'] = "Member removed successfully.";
        }
    }

    // ======================================================
    // 逻辑 B: 转让队长 (Transfer Ownership)
    // ======================================================
    elseif ($action === 'transfer') {
        
        // 【新增条件】如果目标是 Admin (Role=1)，禁止转让
        if ($target_member['Role'] == 1) {
            $_SESSION['flash_error'] = "Action Failed: You cannot transfer ownership to an Admin.";
            header("Location: team_edit.php");
            exit();
        }
        
        // 1. 更新 Team 表：把 Owner 换成新人
        $update_team_owner = "UPDATE team SET Owner_ID = '$target_user_id' WHERE Team_ID = '$team_id'";
        
        // 2. 更新 User 表：把旧队长降级 (Role=0)
        // 保护机制：如果当前用户是 Admin (Role=1)，则不降级，保持 Admin 身份
        $downgrade_old_owner = "UPDATE user SET Role = 0 WHERE User_ID = '$current_user_id' AND Role != 1";
        
        // 3. 更新 User 表：把新队长升级 (Role=2)
        // 保护机制：虽然上面已经检查了，但 SQL 里再加层保险 (Role != 1)
        $upgrade_new_owner = "UPDATE user SET Role = 2 WHERE User_ID = '$target_user_id' AND Role != 1";

        // 执行事务 (简单顺序执行)
        $success = true;
        if (!mysqli_query($con, $update_team_owner)) $success = false;
        if (!mysqli_query($con, $downgrade_old_owner)) $success = false;
        if (!mysqli_query($con, $upgrade_new_owner)) $success = false;

        if ($success) {
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