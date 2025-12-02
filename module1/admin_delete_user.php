<?php
session_start();
require 'database.php';

// 1. 安全检查：只有 Admin(1) 或 Moderator(2) 能操作
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$current_user_id = $_SESSION['user_id'];
$auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
$auth_res = mysqli_query($con, $auth_sql);
$auth_row = mysqli_fetch_assoc($auth_res);

if ($auth_row['Role'] == 0) {
    header("Location: index.php");
    exit();
}

// 2. 获取要删除的目标 ID
if (isset($_GET['id'])) {
    $target_id = intval($_GET['id']);

    // 防止删掉自己
    if ($target_id == $current_user_id) {
        echo "<script>alert('You cannot delete yourself!'); window.location.href='user_list.php';</script>";
        exit();
    }

    // 执行删除
    $sql = "DELETE FROM user WHERE User_ID = '$target_id'";
    
    if (mysqli_query($con, $sql)) {
        // 删除成功，跳回列表
        header("Location: user_list.php"); 
    } else {
        // 删除失败 (可能是因为外键约束，比如他是队长)
        echo "Error deleting user: " . mysqli_error($con);
        echo "<br><a href='user_list.php'>Back to List</a>";
    }
} else {
    header("Location: user_list.php");
}
?>