<?php
// 1. Start Output Buffering immediately to catch unwanted whitespace/errors
ob_start();

session_start();
include("../database.php");
require '../header.php';

// Set default response
$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    // Ensure POST request
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['challenge_id'])) {
        throw new Exception('No challenge ID provided');
    }

    $original_id = (int)$_POST['challenge_id'];

    // Fetch Original Data
    $sql = "SELECT * FROM challenge WHERE Challenge_ID = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $original_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Challenge not found');
    }

    $row = $result->fetch_assoc();

    // --- SMART DATE LOGIC ---
    $old_start = new DateTime($row['Start_Date']);
    $old_end = new DateTime($row['End_Date']);
    
    // Calculate original duration
    $duration = $old_start->diff($old_end); 

    // Calculate New Start (Next Month)
    $current_day = $old_start->format('d');
    $current_month = $old_start->format('m');
    $current_year = $old_start->format('Y');

    // Add 1 month
    $new_month = $current_month + 1;
    $new_year = $current_year;
    
    if ($new_month > 12) {
        $new_month = 1;
        $new_year++;
    }

    // Handle edge cases like Jan 31 -> Feb 28
    $days_in_new_month = cal_days_in_month(CAL_GREGORIAN, $new_month, $new_year);
    $new_day = min($current_day, $days_in_new_month);

    $new_start_str = sprintf("%04d-%02d-%02d", $new_year, $new_month, $new_day);
    $new_start = new DateTime($new_start_str);

    // Calculate New End (Start + Duration)
    $new_end = clone $new_start;
    $new_end->add($duration);

    $db_start_date = $new_start->format('Y-m-d');
    $db_end_date = $new_end->format('Y-m-d');

    // Create New Title
    $new_title = "Copy of " . $row['Title'];

    // Insert Duplicate Record
    $insert_sql = "INSERT INTO challenge (
        Category_ID, City_ID, User_ID, Title, 
        Preview_Description, Detailed_Description, Difficulty, Points, 
        Start_Date, End_Date, status, photo_upload
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', ?)";

    $stmt_insert = $con->prepare($insert_sql);
    
    // Check array keys (handle case sensitivity depending on DB fetch mode)
    $preview_desc = $row['preview_description'] ?? $row['Preview_Description'];
    $detail_desc = $row['Detailed_Description'] ?? $row['detailed_description'];
    $photo = $row['photo_upload'];

    $stmt_insert->bind_param(
        "iisssssisss",
        $row['Category_ID'],
        $row['City_ID'],
        $row['User_ID'],
        $new_title,
        $preview_desc,
        $detail_desc,
        $row['Difficulty'],
        $row['Points'],
        $db_start_date,
        $db_end_date,
        $photo
    );

    if ($stmt_insert->execute()) {
        $response = [
            'status' => 'success', 
            'message' => 'Challenge cloned successfully!', 
            'new_id' => $stmt_insert->insert_id,
            'new_dates' => "$db_start_date to $db_end_date"
        ];
    } else {
        throw new Exception('Database insert failed: ' . $stmt_insert->error);
    }

    $stmt->close();
    $stmt_insert->close();

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

// 2. Clear Buffer (Delete any warnings/spaces generated above)
ob_end_clean();

// 3. Send Clean JSON
header('Content-Type: application/json');
echo json_encode($response);
$con->close();
exit();
?>