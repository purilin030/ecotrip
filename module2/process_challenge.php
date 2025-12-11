<?php
session_start();
include("../database.php");

// Prevent direct access to this file (must be a POST request)
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: manage_challenge.php");
    exit();
}

$errors = [];

// --- 1. COLLECT & SANITIZE INPUTS ---
// We use the null coalescing operator (??) to prevent "Undefined index" errors
$action = $_POST['action'] ?? 'create'; // Default action is 'create'
$challenge_id = isset($_POST['challenge_id']) ? (int)$_POST['challenge_id'] : 0;

$title          = htmlspecialchars(trim($_POST['title'] ?? ''));
$category_id    = htmlspecialchars($_POST['category_id'] ?? '');
$city_id        = htmlspecialchars($_POST['city_id'] ?? '');
$points         = isset($_POST['points']) ? (int)$_POST['points'] : 0;
$difficulty     = htmlspecialchars($_POST['difficulty'] ?? '');
$start_date     = $_POST['start_date'] ?? '';
$end_date       = $_POST['end_date'] ?? '';
$status         = htmlspecialchars($_POST['status'] ?? 'Draft');
// Fix for "Column cannot be null": default to empty string if missing
$preview_desc   = htmlspecialchars(trim($_POST['preview_description'] ?? ''));
$detailed_desc  = htmlspecialchars(trim($_POST['detailed_description'] ?? ''));

// --- 2. USER ID LOGIC (Crucial for Foreign Key Constraints) ---
// We determine WHO is creating/updating this challenge.
// Priority: 1. Session (Logged in Admin) -> 2. Form Input -> 3. DB Fallback

if (isset($_SESSION['user_id'])) {
    // Best Case: Use the currently logged-in admin's ID
    $user_id = $_SESSION['user_id'];
} elseif (!empty($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
} else {
    // Fallback: Get the first valid user from the DB so the insert doesn't fail
    $user_check = $con->query("SELECT User_ID FROM user LIMIT 1");
    if ($user_check && $row = $user_check->fetch_assoc()) {
        $user_id = $row['User_ID'];
    } else {
        // If DB is empty, set to 0 (might fail if you have strict foreign keys, but better than NULL)
        $user_id = 0; 
        $errors[] = "System Error: No valid User found in database to assign this challenge.";
    }
}

// --- 3. VALIDATION ---
if (empty($title)) $errors[] = "Title is required.";
if (empty($category_id)) $errors[] = "Category is required.";
if (empty($city_id)) $errors[] = "City is required.";
if ($points <= 0) $errors[] = "Points must be a positive number.";
if (empty($start_date)) $errors[] = "Start Date is required.";
if (empty($end_date)) $errors[] = "End Date is required.";
if ($start_date > $end_date) $errors[] = "Start Date cannot be after End Date.";
if (empty($detailed_desc)) $errors[] = "Detailed description is required.";

// --- 4. IMAGE UPLOAD HANDLING ---
$image_path = "";

// Scenario A: User uploaded a NEW file
if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "uploads/";
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_name = basename($_FILES["photo_upload"]["name"]);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp','avif'];

    if (in_array($file_ext, $allowed)) {
        // Create unique name to prevent overwriting
        $new_filename = time() . "_" . uniqid() . "." . $file_ext;
        if (move_uploaded_file($_FILES["photo_upload"]["tmp_name"], $target_dir . $new_filename)) {
            $image_path = $new_filename;
        } else {
            $errors[] = "Failed to save uploaded image.";
        }
    } else {
        $errors[] = "Invalid image format. Only JPG, PNG, GIF, AVIF allowed.";
    }
} 
// Scenario B: No new file, but we are Updating (keep existing path from hidden input)
elseif ($action == 'update') {
    $image_path = $_POST['existing_photo'] ?? '';
}

// --- 5. DATABASE OPERATIONS ---
// Only proceed if there are no validation errors
if (empty($errors)) {
    
    if ($action == 'update') {
        // ==========================
        //      UPDATE FLOW
        // ==========================
        if ($challenge_id <= 0) die("Invalid Challenge ID for update.");

        $sql = "UPDATE challenge SET 
                Category_ID=?, City_ID=?, Created_by=?, Title=?, Preview_Description=?, 
                Detailed_Description=?, Difficulty=?, Points=?, Start_Date=?, End_Date=?, 
                status=?, photo_upload=? 
                WHERE Challenge_ID=?";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param("iisssssissssi", 
            $category_id, $city_id, $user_id, $title, $preview_desc, 
            $detailed_desc, $difficulty, $points, $start_date, $end_date, 
            $status, $image_path, $challenge_id
        );
        
        // --- CHANGED REDIRECT HERE ---
        // Old: view_challenge.php?id=...
        // New: manage_challenge.php?updated=true
        $redirect_url = "manage_challenge.php?updated=true"; 

    } else {
        // ==========================
        //      CREATE FLOW
        // ==========================
        
        $sql = "INSERT INTO challenge (Category_ID, City_ID, Created_by, Title, Preview_Description, 
                Detailed_Description, Difficulty, Points, Start_Date, End_Date, status, photo_upload)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param("iisssssissss", 
            $category_id, $city_id, $user_id, $title, $preview_desc, 
            $detailed_desc, $difficulty, $points, $start_date, $end_date, 
            $status, $image_path
        );

        // This was already correct, keeping it consistent
        $redirect_url = "manage_challenge.php?created=true";
    }

    // Execute Query
    if ($stmt->execute()) {
        $stmt->close();
        $con->close();
        header("Location: " . $redirect_url);
        exit();
    } else {
        $errors[] = "Database Error: " . $stmt->error;
    }
}

// --- 6. ERROR DISPLAY ---
// If code reaches here, it means there were errors. Show them nicely.
$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Failed</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f3f4f6; padding: 2rem; display: flex; justify-content: center; }
        .error-container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); max-width: 500px; width: 100%; border-top: 6px solid #ef4444; }
        h2 { color: #b91c1c; margin-top: 0; display: flex; align-items: center; gap: 10px; }
        .error-list { background: #fef2f2; border: 1px solid #fecaca; padding: 1rem 1rem 1rem 2rem; border-radius: 8px; color: #991b1b; margin: 1rem 0; }
        li { margin-bottom: 0.5rem; }
        .back-btn { background: #374151; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 1rem; width: 100%; transition: background 0.2s; }
        .back-btn:hover { background: #1f2937; }
    </style>
</head>
<body>
    <div class="error-container">
        <h2><i class="fa-solid fa-triangle-exclamation"></i> Submission Failed</h2>
        <p>We couldn't save your challenge because of the following issues:</p>
        
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>

        <button onclick="history.back()" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Go Back to Form
        </button>
    </div>
</body>
</html>