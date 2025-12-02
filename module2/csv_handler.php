<?php
session_start();
include("../database.php");

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

// ==========================
// 1. EXPORT FUNCTIONALITY
// ==========================
if ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=challenges_export_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    // Headers matching Create Challenge Form
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

    $sql = "SELECT 
                c.Title, 
                cat.CategoryName, 
                c.Points, 
                c.preview_description, 
                city.CityName, 
                c.Difficulty, 
                c.Start_Date, 
                c.End_Date, 
                c.status, 
                c.Detailed_Description, 
                c.photo_upload
            FROM challenge c
            LEFT JOIN category cat ON c.Category_ID = cat.CategoryID
            LEFT JOIN city city ON c.City_ID = city.CityID";
    
    $result = $con->query($sql);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// ==========================
// 2. IMPORT FUNCTIONALITY
// ==========================
if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['rows'])) {
        echo json_encode(['status' => 'error', 'message' => 'No data received']);
        exit();
    }

    $successCount = 0;
    $errors = [];

    // Prepared Statement for ALL fields
    $sql = "INSERT INTO challenge (
                Category_ID, City_ID, User_ID, Title, Points, 
                preview_description, Difficulty, Start_Date, End_Date, 
                status, Detailed_Description, photo_upload
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $con->prepare($sql);

    foreach ($input['rows'] as $index => $row) {
        $rowNum = $index + 1;

        // 1. Lookup Category ID (Case-Insensitive)
        $catName = $con->real_escape_string(trim($row['Category']));
        $catRes = $con->query("SELECT CategoryID FROM category WHERE LOWER(CategoryName) = LOWER('$catName') LIMIT 1");
        
        if ($catRes->num_rows > 0) {
            $catID = $catRes->fetch_assoc()['CategoryID'];
        } else {
            $errors[] = "Row $rowNum: Category '$catName' not found.";
            continue;
        }

        // 2. Lookup City ID (Case-Insensitive)
        $cityName = $con->real_escape_string(trim($row['City']));
        $cityRes = $con->query("SELECT CityID FROM city WHERE LOWER(CityName) = LOWER('$cityName') LIMIT 1");
        
        if ($cityRes->num_rows > 0) {
            $cityID = $cityRes->fetch_assoc()['CityID'];
        } else {
            $errors[] = "Row $rowNum: City '$cityName' not found.";
            continue;
        }

        // 3. Defaults & Sanitization
        $userID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to Admin ID 1 if session empty
        $title = $row['Title'];
        $points = (int)$row['Points'];
        $preview = $row['Preview'];
        $difficulty = ucfirst(strtolower($row['Difficulty'])); // Ensure Title Case (Easy, Medium)
        $start = $row['Start'];
        $end = $row['End'];
        $status = ucfirst(strtolower($row['Status'])); // Active, Draft
        $detailed = $row['Detailed'];
        $photo = $row['Photo'];

        // Bind Params
        // Types: i(int), i(int), i(int), s(string)...
        $stmt->bind_param("iiisisssssss", 
            $catID, $cityID, $userID, 
            $title, $points, 
            $preview, $difficulty, $start, $end, 
            $status, $detailed, $photo
        );

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = "Row $rowNum DB Error: " . $stmt->error;
        }
    }

    echo json_encode([
        'status' => 'success', 
        'message' => "Successfully imported $successCount challenges.",
        'errors' => $errors
    ]);
    exit();
}
?>