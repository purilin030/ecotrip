<?php
// --- CONFIGURATION ---
// 1. Disable display_errors to prevent HTML from breaking JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 2. Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../database.php");

// 3. Auto-detect Connection Variable ($con or $conn)
$db = isset($con) ? $con : (isset($conn) ? $conn : null);

// Set Header
header('Content-Type: application/json');

// Check DB Connection
if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

$action = $_REQUEST['action'] ?? '';

// ==========================
// IMPORT HANDLER
// ==========================
if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || empty($input['rows'])) {
        echo json_encode(['status' => 'error', 'message' => 'No data received']);
        exit();
    }

    $successCount = 0;
    $errors = [];
    
    // Default to 'Created_by' ID (Session or default 24/1)
    $creatorID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 

    // --- CORRECTED SQL QUERY ---
    // Mapped exactly to your database screenshot:
    // User_ID -> Created_by
    // Start_Date -> Start_date (lowercase d)
    // End_Date -> End_date (lowercase d)
    $sql = "INSERT INTO challenge (
                Category_ID, City_ID, Created_by, Title, Points, 
                preview_description, Difficulty, Start_date, End_date, 
                status, Detailed_Description, photo_upload
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'DB Prepare Error: ' . $db->error]);
        exit();
    }

    foreach ($input['rows'] as $index => $row) {
        $rowNum = $index + 1; 

        try {
            // 1. Category Lookup
            $catName = $db->real_escape_string(trim($row['Category']));
            // Try 'CategoryID' first (standard), fallback to 'Category_ID' if needed
            $catRes = $db->query("SELECT CategoryID FROM category WHERE LOWER(CategoryName) = LOWER('$catName') LIMIT 1");
            if (!$catRes) $catRes = $db->query("SELECT Category_ID FROM category WHERE LOWER(CategoryName) = LOWER('$catName') LIMIT 1");

            if ($catRes && $catRes->num_rows > 0) {
                $catData = $catRes->fetch_assoc();
                $catID = array_values($catData)[0]; // Grab first column value safely
            } else {
                $errors[] = "Row $rowNum: Category '$catName' not found.";
                continue; 
            }

            // 2. City Lookup
            $cityName = $db->real_escape_string(trim($row['City']));
            $cityRes = $db->query("SELECT CityID FROM city WHERE LOWER(CityName) = LOWER('$cityName') LIMIT 1");
            if (!$cityRes) $cityRes = $db->query("SELECT City_ID FROM city WHERE LOWER(CityName) = LOWER('$cityName') LIMIT 1");

            if ($cityRes && $cityRes->num_rows > 0) {
                $cityData = $cityRes->fetch_assoc();
                $cityID = array_values($cityData)[0];
            } else {
                $errors[] = "Row $rowNum: City '$cityName' not found.";
                continue;
            }

            // 3. Date Formatting (Fixes MM/DD/YYYY -> YYYY-MM-DD)
            $startStr = trim($row['Start']);
            $endStr = trim($row['End']);
            
            $startDate = date('Y-m-d', strtotime($startStr));
            $endDate = date('Y-m-d', strtotime($endStr));

            if (!$startDate || $startDate == '1970-01-01') {
                $errors[] = "Row $rowNum: Invalid Start Date ($startStr).";
                continue;
            }

            // 4. Data Setup
            $title = strip_tags(trim($row['Title']));
            $points = (int)$row['Points'];
            $preview = substr(trim($row['Preview']), 0, 255);
            $difficulty = ucfirst(strtolower(trim($row['Difficulty']))); 
            $status = ucfirst(strtolower(trim($row['Status'])));
            $detailed = trim($row['Detailed']);
            $photo = trim($row['Photo']);

            // 5. Bind & Execute
            // i=int, s=string
            $stmt->bind_param("iiisisssssss", 
                $catID, $cityID, $creatorID, 
                $title, $points, 
                $preview, $difficulty, $startDate, $endDate, 
                $status, $detailed, $photo
            );

            if ($stmt->execute()) {
                $successCount++;
            } else {
                $errors[] = "Row $rowNum SQL Error: " . $stmt->error;
            }

        } catch (Exception $e) {
            $errors[] = "Row $rowNum Exception: " . $e->getMessage();
        }
    }

    echo json_encode([
        'status' => 'success', 
        'message' => "Import Complete. Success: $successCount. Failed: " . count($errors),
        'errors' => $errors
    ]);
    exit();
}
?>