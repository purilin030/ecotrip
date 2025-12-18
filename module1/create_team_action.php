<?php
session_start();
require 'database.php';

// 1. Check Authentication
if (!isset($_SESSION['Firstname'])) {
    header("Location: index.php");
    exit();
}

// 2. Receive Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name']);
    
    // Check Session user_id
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $_SESSION['flash_error'] = "Session expired. Please logout and login again.";
        header("Location: team.php");
        exit();
    }
    $owner_id = $_SESSION['user_id']; 

    // 3. Check name is not empty
    if (empty($team_name)) {
        $_SESSION['flash_error'] = "Team name cannot be empty.";
        header("Location: team.php");
        exit();
    }

    // 4. Check name is not taken
    $safe_name = mysqli_real_escape_string($con, $team_name);
    $check_sql = "SELECT Team_ID FROM team WHERE Team_name = '$safe_name'";
    $check_res = mysqli_query($con, $check_sql);

    if (mysqli_num_rows($check_res) > 0) {
        $_SESSION['flash_error'] = "Team name '$team_name' is already taken.";
        header("Location: team.php");
        exit();
    }

    // 5. Generate Team Code
    $team_code = strtoupper(substr(md5(time() . rand()), 0, 6));

    // 6. Create team, Total_members is 1
    $insert_sql = "INSERT INTO team (Team_name, Team_code, Owner_ID, Total_members) 
                   VALUES ('$safe_name', '$team_code', '$owner_id', 1)";
    
    if (mysqli_query($con, $insert_sql)) {
        
        // ======================================================
        // Update User Role and Team_ID
        // ======================================================
        
        // A. Get the ID of the team that was just created (Get Last Insert ID)
        $new_team_id = mysqli_insert_id($con);

        // B. Update the User table for the captain and populate the Team_ID field.
        $update_user_sql = "UPDATE user SET Team_ID = '$new_team_id', Role = 2 WHERE User_ID = '$user_id'";
        mysqli_query($con, $update_owner_sql);

        if (mysqli_query($con, $update_user_sql)) {
            $_SESSION['flash_success'] = "Team '$team_name' created successfully! You are now a Team Owner.";
            header("Location: team_information.php"); // Redirect to their new team page
            exit();
        } else {
            // Rollback if user update fails (Optional but recommended)
            mysqli_query($con, "DELETE FROM team WHERE Team_ID = '$new_team_id'");
            $_SESSION['flash_error'] = "Error updating user role: " . mysqli_error($con);
        }
    } else {
        $_SESSION['flash_error'] = "Database error: " . mysqli_error($con);
    }

    header("Location: team.php");
    exit();
}