<?php
// 1. Start Output Buffering immediately
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

    // --- CASE SENSITIVITY FIX ---
    // Your DB uses 'Start_date' and 'End_date' (Based on your screenshot)
    // We check multiple variations just to be safe.
    function get_db_val($row, $keys) {
        foreach ($keys as $k) {
            if (isset($row[$k])) return $row[$k];
        }
        return null;
    }

    $orig_start_str = get_db_val($row, ['Start_date', 'Start_Date', 'start_date']);
    $orig_end_str   = get_db_val($row, ['End_date',   'End_Date',   'end_date']);

    if (!$orig_start_str || !$orig_end_str) {
        // Log the actual keys found to help debug if it fails again
        throw new Exception("Date columns empty. Found keys: " . implode(", ", array_keys($row)));
    }

    // --- SMART DATE LOGIC ---

    function getNextMonthDate($dateString) {
        $date = new DateTime($dateString);
        
        $current_day   = (int)$date->format('d');
        $current_month = (int)$date->format('m');
        $current_year  = (int)$date->format('Y');

        // Add 1 month
        $new_month = $current_month + 1;
        $new_year  = $current_year;

        if ($new_month > 12) {
            $new_month = 1;
            $new_year++;
        }

        // Handle edge cases (e.g. Jan 31 -> Feb 28)
        $days_in_new_month = cal_days_in_month(CAL_GREGORIAN, $new_month, $new_year);
        $new_day = min($current_day, $days_in_new_month);

        return sprintf("%04d-%02d-%02d", $new_year, $new_month, $new_day);
    }

    // Calculate New Dates independently
    $db_start_date = getNextMonthDate($orig_start_str);
    $db_end_date   = getNextMonthDate($orig_end_str);

    // Create New Title
    $original_title = get_db_val($row, ['Title', 'title']);
    $new_title = "Copy of " . $original_title;

    // Insert Duplicate Record
    $insert_sql = "INSERT INTO challenge (
        Category_ID, City_ID, Created_by, Title, 
        Preview_Description, Detailed_Description, Difficulty, Points, 
        Start_Date, End_Date, status, photo_upload
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', ?)";

    $stmt_insert = $con->prepare($insert_sql);
    
    // Check array keys for other fields too
    $cat_id = get_db_val($row, ['Category_ID', 'category_id']);
    $city_id = get_db_val($row, ['City_ID', 'city_id']);
    $created_by = get_db_val($row, ['Created_by', 'created_by']);
    $diff = get_db_val($row, ['Difficulty', 'difficulty']);
    $points = get_db_val($row, ['Points', 'points']);
    $preview_desc = get_db_val($row, ['preview_description', 'Preview_Description']);
    $detail_desc = get_db_val($row, ['Detailed_Description', 'detailed_description']);
    $photo = get_db_val($row, ['photo_upload', 'Photo_Upload']);

    $stmt_insert->bind_param(
        "iisssssisss",
        $cat_id,
        $city_id,
        $created_by,
        $new_title,
        $preview_desc,
        $detail_desc,
        $diff,
        $points,
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

ob_end_clean();
header('Content-Type: application/json');
echo json_encode($response);
$con->close();
exit();
?>