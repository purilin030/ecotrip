<?php
session_start();
require 'database.php';

// 1. 安全检查
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$current_user_id = $_SESSION['user_id'];
$auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
$auth_res = mysqli_query($con, $auth_sql);
$auth_row = mysqli_fetch_assoc($auth_res);

if ($auth_row['Role'] == 0) { header("Location: index.php"); exit(); }

// 2. 获取目标 ID
if (isset($_GET['id'])) {
    $target_team_id = intval($_GET['id']);

    // A. 先把该队伍所有成员的 Team_ID 设为 NULL (解散队员)
    $release_members_sql = "UPDATE user SET Team_ID = NULL WHERE Team_ID = '$target_team_id'";
    mysqli_query($con, $release_members_sql);

    // B. 删除队伍
    $delete_sql = "DELETE FROM team WHERE Team_ID = '$target_team_id'";
    
    if (mysqli_query($con, $delete_sql)) {
        echo "<script>alert('Team deleted and members released.'); window.location.href='team_list.php';</script>";
    } else {
        echo "Error deleting team: " . mysqli_error($con);
        echo "<br><a href='team_list.php'>Back to List</a>";
    }
} else {
    header("Location: team_list.php");
}
?>