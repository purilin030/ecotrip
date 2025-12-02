<?php
session_start();
require 'database.php';

// 1. 安全检查
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['team_code']);
    $user_id = $_SESSION['user_id'];

    // 2. 检查代码为空
    if (empty($code)) {
        $_SESSION['flash_error'] = "Please enter a team code.";
        header("Location: team.php");
        exit();
    }

    // 3. 检查用户是否已有队伍
    $user_check_sql = "SELECT Team_ID FROM user WHERE User_ID = '$user_id'";
    $user_check_res = mysqli_query($con, $user_check_sql);
    $user_row = mysqli_fetch_assoc($user_check_res);

    if ($user_row['Team_ID'] != NULL && $user_row['Team_ID'] != 0) {
        $_SESSION['flash_error'] = "You are already in a team!";
        header("Location: team.php");
        exit();
    }

    // 4. 查找队伍
    $safe_code = mysqli_real_escape_string($con, $code);
    $sql = "SELECT * FROM team WHERE Team_code = '$safe_code'";
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0) {
        $team = mysqli_fetch_assoc($result);
        $team_id = $team['Team_ID'];
        $team_name = $team['Team_name'];
        $current_owner = $team['Owner_ID'];

        // ==========================================================
        // 【新增逻辑】自动成为队长 (Owner)
        // 如果这个队目前没有队长 (Owner_ID 为 0 或 NULL)
        // 或者 队员人数为 0 (空队)
        // ==========================================================
        $is_new_owner = false;
        if ($current_owner == 0 || $current_owner == NULL) {
            // 更新队伍表：把当前用户设为 Owner
            $update_owner_sql = "UPDATE team SET Owner_ID = '$user_id' WHERE Team_ID = '$team_id'";
            mysqli_query($con, $update_owner_sql);
            $is_new_owner = true;
        }

        // A. 更新用户的 Team_ID
        $update_user = "UPDATE user SET Team_ID = '$team_id' WHERE User_ID = '$user_id'";
        mysqli_query($con, $update_user);

        // B. 队伍人数 +1
        $update_team = "UPDATE team SET Total_members = Total_members + 1 WHERE Team_ID = '$team_id'";
        mysqli_query($con, $update_team);

        // C. 设置成功提示语
        if ($is_new_owner) {
            $_SESSION['flash_success'] = "You joined empty team '$team_name' and became the Team Owner!";
        } else {
            $_SESSION['flash_success'] = "Welcome! You have successfully joined team '$team_name'.";
        }

    } else {
        $_SESSION['flash_error'] = "Invalid Team Code.";
    }

    header("Location: team.php");
    exit();
}