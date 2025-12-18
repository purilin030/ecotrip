<?php
session_start();
require 'database.php';

// 1. Security checks
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['team_code']);
    $user_id = $_SESSION['user_id'];

    // 2. Check code is not empty
    if (empty($code)) {
        $_SESSION['flash_error'] = "Please enter a team code.";
        header("Location: team.php");
        exit();
    }

    // 3. Check whether user is already in a team
    $user_check_sql = "SELECT Team_ID FROM user WHERE User_ID = '$user_id'";
    $user_check_res = mysqli_query($con, $user_check_sql);
    $user_row = mysqli_fetch_assoc($user_check_res);

    if ($user_row['Team_ID'] != NULL && $user_row['Team_ID'] != 0) {
        $_SESSION['flash_error'] = "You are already in a team!";
        header("Location: team.php");
        exit();
    }

    // 4. Find team
    $safe_code = mysqli_real_escape_string($con, $code);
    $sql = "SELECT * FROM team WHERE Team_code = '$safe_code'";
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0) {
        $team = mysqli_fetch_assoc($result);
        $team_id = $team['Team_ID'];
        $team_name = $team['Team_name'];
        $current_owner = $team['Owner_ID'];

        // ==========================================================
        // [New] Automatically become Owner if applicable
        // If the team currently has no owner (Owner_ID is 0 or NULL)
        // Or if member count is 0 (empty team)
        // ==========================================================
        $is_new_owner = false;
        if ($current_owner == 0 || $current_owner == NULL) {
            // Update team table: set current user as Owner
            $update_owner_sql = "UPDATE team SET Owner_ID = '$user_id' WHERE Team_ID = '$team_id'";
            mysqli_query($con, $update_owner_sql);
            $is_new_owner = true;
        }

        // A. Update user's Team_ID
        $update_user = "UPDATE user SET Team_ID = '$team_id' WHERE User_ID = '$user_id'";
        mysqli_query($con, $update_user);

        // B. Increment team member count
        $update_team = "UPDATE team SET Total_members = Total_members + 1 WHERE Team_ID = '$team_id'";
        mysqli_query($con, $update_team);

        // C. Set success message
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