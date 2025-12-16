<?php
session_start();
require 'database.php';

// 1. Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_user_id = $_SESSION['user_id'];
    $target_user_id = intval($_POST['target_user_id']); // Target member ID to operate on
    $action = $_POST['action_type']; // 'kick' or 'transfer'

    // 2. Get the current user's team info and confirm they are the owner
    $check_sql = "SELECT * FROM team WHERE Owner_ID = '$current_user_id'";
    $check_res = mysqli_query($con, $check_sql);

    if (mysqli_num_rows($check_res) == 0) {
        $_SESSION['flash_error'] = "You are not authorized to manage this team.";
        header("Location: team.php");
        exit();
    }

    $team = mysqli_fetch_assoc($check_res);
    $team_id = $team['Team_ID'];

    // 3. Confirm the target member is indeed in this team
    // [change] Also fetch the 'Role' field for later checks
    $target_check_sql = "SELECT User_ID, Role FROM user WHERE User_ID = '$target_user_id' AND Team_ID = '$team_id'";
    $target_check_res = mysqli_query($con, $target_check_sql);

    if (mysqli_num_rows($target_check_res) == 0) {
        $_SESSION['flash_error'] = "Member not found in your team.";
        header("Location: team_edit.php");
        exit();
    }
    
    // Fetch target member data
    $target_member = mysqli_fetch_assoc($target_check_res);

    // ======================================================
    // Logic A: Kick member (Kick)
    // ======================================================
    if ($action === 'kick') {
        if ($target_user_id == $current_user_id) {
            $_SESSION['flash_error'] = "You cannot kick yourself.";
        } else {
            // [optional] To prevent kicking Admins, add a check here:
            // if ($target_member['Role'] == 1) { error... }

            $update_user = "UPDATE user SET Team_ID = NULL WHERE User_ID = '$target_user_id'";
            mysqli_query($con, $update_user);

            $update_team = "UPDATE team SET Total_members = Total_members - 1 WHERE Team_ID = '$team_id'";
            mysqli_query($con, $update_team);

            $_SESSION['flash_success'] = "Member removed successfully.";
        }
    }

    // ======================================================
    // Logic B: Transfer ownership (Transfer Ownership)
    // ======================================================
    elseif ($action === 'transfer') {
        
        // [new] If the target is an Admin (Role=1), forbid transfer
        if ($target_member['Role'] == 1) {
            $_SESSION['flash_error'] = "Action Failed: You cannot transfer ownership to an Admin.";
            header("Location: team_edit.php");
            exit();
        }
        
        // 1. Update Team: set Owner to the new user
        $update_team_owner = "UPDATE team SET Owner_ID = '$target_user_id' WHERE Team_ID = '$team_id'";
        
        // 2. Update User: downgrade the old owner (Role=0)
        // Safety: if the current user is Admin (Role=1), do not downgrade
        $downgrade_old_owner = "UPDATE user SET Role = 0 WHERE User_ID = '$current_user_id' AND Role != 1";
        
        // 3. Update User: upgrade the new owner (Role=2)
        // Safety: already checked above, but enforce with SQL (Role != 1)
        $upgrade_new_owner = "UPDATE user SET Role = 2 WHERE User_ID = '$target_user_id' AND Role != 1";

        // Execute operations (simple sequential execution)
        $success = true;
        if (!mysqli_query($con, $update_team_owner)) $success = false;
        if (!mysqli_query($con, $downgrade_old_owner)) $success = false;
        if (!mysqli_query($con, $upgrade_new_owner)) $success = false;

        if ($success) {
            $_SESSION['flash_success'] = "Ownership transferred! You are now a regular member.";
            // Since you are no longer the owner, redirect to the info page (cannot stay on edit page)
            header("Location: team_information.php");
            exit();
        } else {
            $_SESSION['flash_error'] = "Transfer failed: " . mysqli_error($con);
        }
    }

    // Default: go back to edit page
    header("Location: team_edit.php");
    exit();
}
?>