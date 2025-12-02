<?php
session_start();
require 'database.php';

// 1. 检查是否登录
if (!isset($_SESSION['Firstname'])) {
    header("Location: index.php");
    exit();
}

// 2. 接收数据
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name']);
    
    // 检查 Session 里的 user_id
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $_SESSION['flash_error'] = "Session expired. Please logout and login again.";
        header("Location: team.php");
        exit();
    }
    $owner_id = $_SESSION['user_id']; 

    // 3. 检查名字是否为空
    if (empty($team_name)) {
        $_SESSION['flash_error'] = "Team name cannot be empty.";
        header("Location: team.php");
        exit();
    }

    // 4. 检查名字是否重复
    $safe_name = mysqli_real_escape_string($con, $team_name);
    $check_sql = "SELECT Team_ID FROM team WHERE Team_name = '$safe_name'";
    $check_res = mysqli_query($con, $check_sql);

    if (mysqli_num_rows($check_res) > 0) {
        $_SESSION['flash_error'] = "Team name '$team_name' is already taken.";
        header("Location: team.php");
        exit();
    }

    // 5. 生成 Team Code
    $team_code = strtoupper(substr(md5(time() . rand()), 0, 6));

    // 6. 创建队伍 (注意：Total_members 初始设为 1)
    $insert_sql = "INSERT INTO team (Team_name, Team_code, Owner_ID, Total_members, Team_points) 
                   VALUES ('$safe_name', '$team_code', '$owner_id', 1, 0)";
    
    if (mysqli_query($con, $insert_sql)) {
        
        // ======================================================
        // 【新增关键步骤】自动把队长分配进这个新队伍
        // ======================================================
        
        // A. 获取刚刚创建的那个队伍的 ID (Get Last Insert ID)
        $new_team_id = mysqli_insert_id($con);

        // B. 更新队长的 User 表，把 Team_ID 填进去
        $update_owner_sql = "UPDATE user SET Team_ID = '$new_team_id' WHERE User_ID = '$owner_id'";
        mysqli_query($con, $update_owner_sql);

        $_SESSION['flash_success'] = "Team '$team_name' created! You are now the leader.";
    } else {
        $_SESSION['flash_error'] = "Database error: " . mysqli_error($con);
    }

    header("Location: team.php");
    exit();
}