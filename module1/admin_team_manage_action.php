<?php
session_start();
require 'database.php';

// 1. Security Check: Only Admin can access
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$current_user_id = $_SESSION['user_id'];
$auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
$auth_res = mysqli_query($con, $auth_sql);
$auth_row = mysqli_fetch_assoc($auth_res);

if ($auth_row['Role'] != 1 ) {
    header("Location: index.php");
    exit();
}

// 2. Receive Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = intval($_POST['target_user_id']);
    $team_id = intval($_POST['team_id']);
    $action = $_POST['action_type']; // 'kick' or 'transfer'

    // ======================================================
    // Logic A: Kick Member
    // ======================================================
    if ($action === 'kick') {
        // 1. Clear user Team_ID
        $update_user = "UPDATE user SET Team_ID = NULL WHERE User_ID = '$target_user_id'";
        mysqli_query($con, $update_user);

        // 2. Team member -1
        $update_team = "UPDATE team SET Total_members = Total_members - 1 WHERE Team_ID = '$team_id'";
        mysqli_query($con, $update_team);

        // 3. Check if the kicked player is the captain. If so, set the captain position to empty (to prevent data inconsistency).
        $check_owner = mysqli_query($con, "SELECT Owner_ID FROM team WHERE Team_ID = '$team_id'");
        $team_info = mysqli_fetch_assoc($check_owner);
        if ($team_info['Owner_ID'] == $target_user_id) {
            mysqli_query($con, "UPDATE team SET Owner_ID = 0 WHERE Team_ID = '$team_id'");
        }
    }

    // ======================================================
    // Logic B: Transfer Ownership
    // ======================================================
    elseif ($action === 'transfer') {
        
        // 1. First, find out who the former captain of this team is (so we can demote him later).
        $old_owner_sql = "SELECT Owner_ID FROM team WHERE Team_ID = '$team_id'";
        $old_res = mysqli_query($con, $old_owner_sql);
        $old_row = mysqli_fetch_assoc($old_res);
        $old_owner_id = $old_row['Owner_ID'];

        // 2. Update Team table：swithc Owner to new person
        $update_owner = "UPDATE team SET Owner_ID = '$target_user_id' WHERE Team_ID = '$team_id'";
        
        if (mysqli_query($con, $update_owner)) {
            
            // 3. Role Change logic
            
            // A. (Target User) -> Role 2
            // if he is Admin (1)，keep 1；else 2
            $upgrade_new_sql = "UPDATE user 
                                SET Role = IF(Role = 1, 1, 2), Team_ID = '$team_id' 
                                WHERE User_ID = '$target_user_id'";
            mysqli_query($con, $upgrade_new_sql);

            // B. downgrade Old Owner -> Role 0 
            // If he is Admin (1)，keep 1；else 0
            if ($old_owner_id && $old_owner_id != $target_user_id) {
                $downgrade_old_sql = "UPDATE user 
                                      SET Role = IF(Role = 1, 1, 0) 
                                      WHERE User_ID = '$old_owner_id'";
                mysqli_query($con, $downgrade_old_sql);
            }
            
            // Success
        } else {
            // Fail
            // echo "Error: " . mysqli_error($con);
        }
    }

    // Redirect back to team edit page
    header("Location: admin_edit_team.php?id=" . $team_id);
    exit();
}
?>