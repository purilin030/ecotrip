<?php
session_start();
require 'database.php';

// 1. 安全检查
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. 获取当前用户的 Team_ID
$user_sql = "SELECT Team_ID FROM user WHERE User_ID = '$user_id'";
$user_res = mysqli_query($con, $user_sql);
$user_data = mysqli_fetch_assoc($user_res);
$team_id = $user_data['Team_ID'];

// 如果用户本来就没有队，直接回 team.php
if ($team_id == NULL || $team_id == 0) {
    header("Location: team.php");
    exit();
}

// 3. 获取队伍信息 (为了检查他是不是队长，以及队里还剩几个人)
$team_sql = "SELECT * FROM team WHERE Team_ID = '$team_id'";
$team_res = mysqli_query($con, $team_sql);
$team_data = mysqli_fetch_assoc($team_res);

$is_owner = ($team_data['Owner_ID'] == $user_id);
$total_members = $team_data['Total_members'];

// ======================================================
// 核心逻辑处理
// ======================================================

if ($is_owner) {
    // --- 情况 A: 队长离开 ---
    
    if ($total_members <= 1) {
        // A1. 队里只剩队长一个人 -> 直接解散队伍 (删除队伍)
        $delete_sql = "DELETE FROM team WHERE Team_ID = '$team_id'";
        mysqli_query($con, $delete_sql);
        
        $_SESSION['flash_success'] = "You left and the team has been disbanded.";
    } else {
        // A2. 队里还有别人 -> 必须把队长转让给下一个人
        
        // 找一个不是自己的最早加入的队员
        $next_owner_sql = "SELECT User_ID FROM user WHERE Team_ID = '$team_id' AND User_ID != '$user_id' LIMIT 1";
        $next_res = mysqli_query($con, $next_owner_sql);
        $next_data = mysqli_fetch_assoc($next_res);
        $new_owner_id = $next_data['User_ID'];
        
        // 更新队伍：设置新队长，人数 -1
        $update_team_sql = "UPDATE team SET Owner_ID = '$new_owner_id', Total_members = Total_members - 1 WHERE Team_ID = '$team_id'";
        mysqli_query($con, $update_team_sql);
        
        $_SESSION['flash_success'] = "You left the team. Ownership transferred to the next member.";
    }

} else {
    // --- 情况 B: 普通成员离开 ---
    
    // 队伍人数 -1
    $update_team_sql = "UPDATE team SET Total_members = Total_members - 1 WHERE Team_ID = '$team_id'";
    mysqli_query($con, $update_team_sql);
    
    $_SESSION['flash_success'] = "You have successfully left the team.";
}

// 4. 最后一步：清空用户的 Team_ID (设置为 NULL)
$update_user_sql = "UPDATE user SET Team_ID = NULL WHERE User_ID = '$user_id'";
mysqli_query($con, $update_user_sql);

// 5. 跳转回 team.php (这时候因为没 Team_ID 了，会显示 Join/Create 界面)
header("Location: team.php");
exit();
?>