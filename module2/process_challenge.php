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
$action = $_POST['action'] ?? 'create'; 
$challenge_id = isset($_POST['challenge_id']) ? (int)$_POST['challenge_id'] : 0;

$title          = htmlspecialchars(trim($_POST['title'] ?? ''));
$category_id    = htmlspecialchars($_POST['category_id'] ?? '');
$city_id        = htmlspecialchars($_POST['city_id'] ?? '');
$points         = isset($_POST['points']) ? (int)$_POST['points'] : 0;
$difficulty     = htmlspecialchars($_POST['difficulty'] ?? '');
$start_date     = $_POST['start_date'] ?? '';
$end_date       = $_POST['end_date'] ?? '';
$status         = htmlspecialchars($_POST['status'] ?? 'Draft');
$preview_desc   = htmlspecialchars(trim($_POST['preview_description'] ?? ''));
$detailed_desc  = htmlspecialchars(trim($_POST['detailed_description'] ?? ''));

// --- 2. USER ID LOGIC ---
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (!empty($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
} else {
    $user_check = $con->query("SELECT User_ID FROM user LIMIT 1");
    if ($user_check && $row = $user_check->fetch_assoc()) {
        $user_id = $row['User_ID'];
    } else {
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

if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_name = basename($_FILES["photo_upload"]["name"]);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    if (in_array($file_ext, $allowed)) {
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
elseif ($action == 'update') {
    $image_path = $_POST['existing_photo'] ?? '';
}

// --- 5. DATABASE OPERATIONS ---
if (empty($errors)) {
    
    if ($action == 'update') {
        // UPDATE
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
        
        $success_msg = "Challenge Updated Successfully!";
        $redirect_url = "manage_challenge.php?updated=true"; 

    } else {
        // CREATE
        $sql = "INSERT INTO challenge (Category_ID, City_ID, Created_by, Title, Preview_Description, 
                Detailed_Description, Difficulty, Points, Start_Date, End_Date, status, photo_upload)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param("iisssssissss", 
            $category_id, $city_id, $user_id, $title, $preview_desc, 
            $detailed_desc, $difficulty, $points, $start_date, $end_date, 
            $status, $image_path
        );

        $success_msg = "Challenge Created Successfully!";
        $redirect_url = "manage_challenge.php?created=true";
    }

    // Execute Query
    if ($stmt->execute()) {
        $stmt->close();
        $con->close();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Success</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <script>
                setTimeout(function(){
                    window.location.href = "<?php echo $redirect_url; ?>";
                }, 2000); 
            </script>
        </head>
        <body class="bg-gray-100 font-sans text-gray-800">
            
            <div class="main-wrapper flex flex-col items-center justify-center min-h-[80vh] px-4">
                
                <div class="bg-white p-10 rounded-2xl shadow-xl text-center max-w-md w-full border-t-4 border-green-500 relative z-10">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Awesome!</h2>
                    <p class="text-gray-500 mb-8"><?php echo $success_msg; ?></p>
                    
                    <a href="<?php echo $redirect_url; ?>" class="block w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                        Continue 
                    </a>
                    <div class="text-xs text-gray-400 mt-4">Redirecting in 2 seconds...</div>
                </div>

            </div>

            <?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
        </body>
        </html>
        <?php
        exit();
    } else {
        $errors[] = "Database Error: " . $stmt->error;
    }
}

// --- 6. ERROR DISPLAY (Clean Style) ---
$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Failed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans text-gray-800">
    
    <div class="main-wrapper flex flex-col items-center justify-center min-h-[80vh] px-4">
        
        <div class="bg-white p-8 rounded-xl shadow-lg max-w-lg w-full border-t-4 border-red-500 relative z-10">
            <h2 class="text-xl font-bold text-red-700 flex items-center gap-2 mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i> Submission Failed
            </h2>
            <p class="text-gray-600 mb-4">We couldn't save your challenge because of the following issues:</p>
            
            <ul class="bg-red-50 border border-red-100 rounded-lg p-4 text-red-800 text-sm mb-6 space-y-2">
                <?php foreach ($errors as $error): ?>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-xmark mt-1 text-red-500"></i>
                        <span><?php echo $error; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <button onclick="history.back()" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-medium py-3 px-4 rounded-lg transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Go Back to Form
            </button>
        </div>

    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>
</html>