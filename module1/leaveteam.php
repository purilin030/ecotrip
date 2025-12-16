<?php
session_start();
require 'database.php';

// 1. Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Get current user's Team_ID
$user_sql = "SELECT Team_ID FROM user WHERE User_ID = '$user_id'";
$user_res = mysqli_query($con, $user_sql);
$user_data = mysqli_fetch_assoc($user_res);
$team_id = $user_data['Team_ID'];

// If user has no team, redirect to team.php
if ($team_id == NULL || $team_id == 0) {
    header("Location: team.php");
    exit();
}

// 3. Get team info (to check owner and member count)
$team_sql = "SELECT * FROM team WHERE Team_ID = '$team_id'";
$team_res = mysqli_query($con, $team_sql);
$team_data = mysqli_fetch_assoc($team_res);

$is_owner = ($team_data['Owner_ID'] == $user_id);
$total_members = $team_data['Total_members'];

// ======================================================
// Core logic
// ======================================================

if ($is_owner) {
    // Scenario A: The captain departs
    
    if ($total_members <= 1) {
        // A1. Only the team leader remains in the team -> Immediately disband the team (delete the team)
        $delete_sql = "DELETE FROM team WHERE Team_ID = '$team_id'";
        mysqli_query($con, $delete_sql);
        
        $_SESSION['flash_success'] = "You left and the team has been disbanded.";
    } else {
        // A2. There are other members in the team -> The team captain must be transferred to the next person.
        
        // Find a team member who wasn't among the earliest to join.
        $next_owner_sql = "SELECT User_ID FROM user WHERE Team_ID = '$team_id' AND User_ID != '$user_id' LIMIT 1";
        $next_res = mysqli_query($con, $next_owner_sql);
        $next_data = mysqli_fetch_assoc($next_res);
        $new_owner_id = $next_data['User_ID'];
        
        // Update Squad: Set new squad leader, reduce squad size by 1
        $update_team_sql = "UPDATE team SET Owner_ID = '$new_owner_id', Total_members = Total_members - 1 WHERE Team_ID = '$team_id'";
        mysqli_query($con, $update_team_sql);
        
        $_SESSION['flash_success'] = "You left the team. Ownership transferred to the next member.";
    }

} else {
    // Scenario B: A regular member departs
    
    // 1. Update Team: Reduce member count by 1
    $update_team_sql = "UPDATE team SET Total_members = Total_members - 1 WHERE Team_ID = '$team_id'";
    mysqli_query($con, $update_team_sql);
    
    $_SESSION['flash_success'] = "You have successfully left the team.";
}

// 4. Update User: Clear user's Team_ID
$update_user_sql = "UPDATE user SET Team_ID = NULL WHERE User_ID = '$user_id'";
mysqli_query($con, $update_user_sql);

// 5. Redirect back to team.php
header("Location: team.php");
exit();
?>