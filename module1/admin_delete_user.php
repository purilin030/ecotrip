<?php
session_start();
require 'database.php';

// 1. Safety Check：only Admin(1) or Moderator(2) can access
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$current_user_id = $_SESSION['user_id'];
$auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
$auth_res = mysqli_query($con, $auth_sql);
$auth_row = mysqli_fetch_assoc($auth_res);

if ($auth_row['Role'] != 1 ) {
    header("Location: index.php");
    exit();
}

// 2. fetch target ID
if (isset($_GET['id'])) {
    $target_id = intval($_GET['id']);

    // Prevent Admin from deleting themselves
    if ($target_id == $current_user_id) {
        echo "<script>alert('You cannot delete yourself!'); window.location.href='user_list.php';</script>";
        exit();
    }

    // Execute deletion
    $sql = "DELETE FROM user WHERE User_ID = '$target_id'";
    
    if (mysqli_query($con, $sql)) {
        // Delete successful back to user list
        header("Location: user_list.php"); 
    } else {
        // Delete fail (foreign key constraint such as Team Owner)
        echo "Error deleting user: " . mysqli_error($con);
        echo "<br><a href='user_list.php'>Back to List</a>";
    }
} else {
    header("Location: user_list.php");
}
?>