<?php
// --- CONFIGURATION ---
// Disable display_errors to prevent HTML from breaking JSON/CSV output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../database.php");

// Auto-detect Connection Variable ($con or $conn)
$db = isset($con) ? $con : (isset($conn) ? $conn : null);

if (!$db) {
    die("Database connection failed. Check database.php");
}

$action = $_REQUEST['action'] ?? '';

// =========================================================
// 1. EXPORT FUNCTIONALITY (Modified to Fix Date Visibility)
// =========================================================
if ($action === 'export') {
    // Set headers to force download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=challenges_export_' . date('Y-m-d') . '.csv');

    // Open output stream
    $output = fopen('php://output', 'w');

    // CSV Column Headers
    fputcsv($output, [
        'Title', 
        'Category', 
        'Points', 
        'Preview_Description', 
        'City', 
        'Difficulty', 
        'Start_Date', 
        'End_Date', 
        'Status', 
        'Detailed_Description', 
        'Photo_Upload'
    ]);

    // SQL Query
    $sql = "SELECT 
                c.Title, 
                cat.CategoryName, 
                c.Points, 
                c.preview_description, 
                city.CityName, 
                c.Difficulty, 
                c.Start_date,
                c.End_date,
                c.status, 
                c.Detailed_Description, 
                c.photo_upload
            FROM challenge c
            LEFT JOIN category cat ON c.Category_ID = cat.CategoryID
            LEFT JOIN city city ON c.City_ID = city.CityID";
    
    $result = $db->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // --- FIX IS HERE ---
            // Prepend a space (" ") to dates. 
            // This forces Excel to treat them as 'Text' so they don't turn into hashtags (#######).
            if (!empty($row['Start_date'])) {
                $row['Start_date'] = " " . $row['Start_date']; 
            }
            if (!empty($row['End_date'])) {
                $row['End_date'] = " " . $row['End_date']; 
            }

            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ['Error exporting data', $db->error]);
    }

    fclose($output);
    exit();
}

// =========================================================
// 2. IMPORT FUNCTIONALITY
// =========================================================
if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || empty($input['rows'])) {
        echo json_encode(['status' => 'error', 'message' => 'No data received']);
        exit();
    }

    $successCount = 0;
    $errors = [];
    $creatorID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 

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
            $catRes = $db->query("SELECT CategoryID FROM category WHERE LOWER(CategoryName) = LOWER('$catName') LIMIT 1");
            if (!$catRes) $catRes = $db->query("SELECT Category_ID FROM category WHERE LOWER(CategoryName) = LOWER('$catName') LIMIT 1");

            if ($catRes && $catRes->num_rows > 0) {
                $catData = $catRes->fetch_assoc();
                $catID = array_values($catData)[0]; 
            } else {
                $errors[] = "Row $rowNum: Category '$catName' not found.";
                continue; 
            }

            // 2. City Lookup
            $cityName = $db->real_escape_string(trim($row['City']));
            $cityRes = $db->query("SELECT CityID FROM city WHERE LOWER(TRIM(CityName)) = LOWER('$cityName') LIMIT 1");
            if (!$cityRes) $cityRes = $db->query("SELECT City_ID FROM city WHERE LOWER(TRIM(CityName)) = LOWER('$cityName') LIMIT 1");

            if ($cityRes && $cityRes->num_rows > 0) {
                $cityData = $cityRes->fetch_assoc();
                $cityID = array_values($cityData)[0];
            } else {
                $errors[] = "Row $rowNum: City '$cityName' not found.";
                continue;
            }

            // 3. Dates (Handle CSV input properly)
            $startStr = trim($row['Start']);
            $endStr = trim($row['End']);
            
            $startDate = date('Y-m-d', strtotime($startStr));
            $endDate = date('Y-m-d', strtotime($endStr));

            if (!$startDate || $startDate == '1970-01-01') {
                $errors[] = "Row $rowNum: Invalid Start Date.";
                continue;
            }

            // 4. Other Fields
            $title = strip_tags(trim($row['Title']));
            $points = (int)$row['Points'];
            $preview = substr(trim($row['Preview']), 0, 255);
            $difficulty = ucfirst(strtolower(trim($row['Difficulty']))); 
            $status = ucfirst(strtolower(trim($row['Status'])));
            $detailed = trim($row['Detailed']);
            $photo = trim($row['Photo']);

            // 5. Execute
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