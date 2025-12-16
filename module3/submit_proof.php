<?php
// === 1. Configuration & connection ===
$path_to_db = __DIR__ . '/../database.php';
$path_to_header = __DIR__ . '/../header.php';


// Check database file
if (!file_exists($path_to_db)) {
    die("Error: Cannot find database.php at " . $path_to_db);
}
require_once $path_to_db;

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];

    // 3. Query Role from database
    // Even if Role exists in Session, re-query DB in case admin changed permissions
    $auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
    $auth_res = mysqli_query($con, $auth_sql);
    
    if ($auth_row = mysqli_fetch_assoc($auth_res)) {
        
        
        if ($auth_row['Role'] == 1) {
            
            
            header("Location: /ecotrip/module3/submission_list.php");
            exit();
        }
    }
}

// Include Header
if (file_exists($path_to_header)) {
    $page_title = "Submit Proof";
    include $path_to_header;
} else {
    // Fallback for testing
    echo '<!DOCTYPE html><html lang="en"><head><script src="https://cdn.tailwindcss.com"></script><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"></head><body class="bg-gray-50">';
}

// === 2. User permission checks ===
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = '../index.php';</script>";
    exit();
}

// Get preselected Challenge ID (if any)
$preselected_challenge_id = isset($_GET['challenge_id']) ? (int) $_GET['challenge_id'] : 0;

$user_id = $_SESSION['user_id'];
$message = "";

// Get user's Team_ID
$team_id = NULL;
if (isset($con) && $con) {
    // Note: assume DB connection variable is $con (consistent with header/submission_list); original code used $conn, unify to $con
    $user_query = $con->query("SELECT Team_ID FROM user WHERE User_ID = '$user_id'");
    if ($user_query && $user_query->num_rows > 0) {
        $row = $user_query->fetch_assoc();
        $team_id = $row['Team_ID'];
    }
}

// === 3. Handle form submission ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $challenge_id = $_POST['challenge_id'];
    $caption = $con->real_escape_string($_POST['caption']);
    $submission_date = date("Y-m-d");
    $status = "Pending";

    // Prepare upload
    $target_dir = __DIR__ . "/../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Check if a file was uploaded
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        $file_tmp_path = $_FILES["photo"]["tmp_name"];
        $file_name = basename($_FILES["photo"]["name"]);
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Simple format validation
        $allowTypes = array('jpg', 'png', 'jpeg');
        if (in_array($file_type, $allowTypes)) {
            if ($_FILES["photo"]["size"] < 5000000) { // < 5MB (调大了点)

                // ==========================================
                // 🔥 Image hashing deduplication mechanism
                // ==========================================

                // 1. Calculate hash
                $image_hash = hash_file('sha256', $file_tmp_path);

                // 2. Check for duplicates
                $check_sql = "SELECT Submission_ID FROM submissions WHERE Image_Hash = '$image_hash'";
                // Note table name: submission_list uses 'submissions', original code used 'submission'.
                // Adjust according to your actual DB table name. Assuming 'submissions' here.
                // If your table name is indeed 'submission' (singular), change back.
                // To be safe, original upload logic used 'submission' (singular); please confirm your table name.
                // Change: based on submission_list.php, table may be `submissions` (plural).
                // Original upload code used `submission` (singular).
                // Using `submissions` (plural) here to match submission_list.php; modify if inconsistent.
                $table_name = "submissions";

                $check_result = $con->query("SELECT Submission_ID FROM $table_name WHERE Image_Hash = '$image_hash'");

                if ($check_result && $check_result->num_rows > 0) {
                    // 3. Duplicates
                    $message = "<div class='rounded-md bg-red-50 p-4 mb-6 border border-red-200'>
                        <div class='flex'>
                            <div class='flex-shrink-0'>
                                <i class='fa-solid fa-circle-exclamation text-red-400'></i>
                            </div>
                            <div class='ml-3'>
                                <h3 class='text-sm font-medium text-red-800'>Duplicate Image Detected!</h3>
                                <div class='mt-2 text-sm text-red-700'>
                                    <p>This photo has already been submitted. Please upload a unique proof.</p>
                                </div>
                            </div>
                        </div>
                    </div>";
                } else {
                    // 4. Upload
                    $final_file_name = time() . "_" . $user_id . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", $file_name);
                    $target_file_path = $target_dir . $final_file_name;
                    $db_file_path = "../uploads/" . $final_file_name; // Store relative path in DB

                    if (move_uploaded_file($file_tmp_path, $target_file_path)) {
                        $team_val = ($team_id) ? "'$team_id'" : "NULL";

                        $sql = "INSERT INTO $table_name (Challenge_ID, User_ID, Team_ID, Caption, Photo, Submission_date, Status, Verification_note, QR_Code, Image_Hash) 
                                VALUES ('$challenge_id', '$user_id', $team_val, '$caption', '$db_file_path', '$submission_date', '$status', '', '', '$image_hash')";

                        if ($con->query($sql) === TRUE) {
                            echo "<script>window.location.href = 'submission_success.php';</script>";
                            exit();
                        } else {
                            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Database Error: " . $con->error . "</div>";
                        }
                    } else {
                        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Error uploading file to server.</div>";
                    }
                }

            } else {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>File too large. Max 5MB.</div>";
            }
        } else {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Invalid file type. Only JPG & PNG allowed.</div>";
        }
    } else {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Please select a file to upload.</div>";
    }
}
?>

<main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="max-w-3xl mx-auto">

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6 bg-gray-50 flex items-center">
                <i class="fa-solid fa-camera text-brand-600 text-xl mr-3"></i>
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    New Submission
                </h3>
            </div>

            <div class="px-4 py-5 sm:p-6">

                <?php echo $message; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">

                    <div>
                        <label for="challenge_id" class="block text-sm font-medium text-gray-700">Select
                            Challenge</label>
                        <select id="challenge_id" name="challenge_id" required class="...">
                            <option value="" disabled <?php echo ($preselected_challenge_id == 0) ? 'selected' : ''; ?>>--
                                Pick a challenge --</option>
                            <?php
                            $challenge_sql = "SELECT Challenge_ID, Title, Points FROM challenge WHERE status = 'Active'";
                            $c_result = $con->query($challenge_sql);
                            if ($c_result && $c_result->num_rows > 0) {
                                while ($row = $c_result->fetch_assoc()) {
                                    // Check if it's the preselected ID
                                    $selected = ($row['Challenge_ID'] == $preselected_challenge_id) ? "selected" : "";

                                    echo "<option value='" . $row['Challenge_ID'] . "' $selected>" .
                                        htmlspecialchars($row['Title']) . " (" . $row['Points'] . " pts)</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Photo Proof</label>
                        <div
                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition">
                            <div class="space-y-1 text-center w-full">

                                <img id="preview" src="#" alt="Image Preview"
                                    class="mx-auto h-64 object-contain rounded-md hidden mb-4">

                                <div id="upload-placeholder">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48" aria-hidden="true">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="photoInput"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-brand-600 hover:text-brand-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-brand-500">
                                            <span>Upload a file</span>
                                            <input id="photoInput" name="photo" type="file" class="sr-only"
                                                accept="image/png, image/jpeg" required onchange="previewImage(this)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG up to 5MB</p>
                                </div>

                                <div class="mt-2 text-xs text-blue-600 bg-blue-50 py-1 px-2 rounded inline-block">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> Anti-Cheat: Duplicate photos will be
                                    rejected.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="caption" class="block text-sm font-medium text-gray-700">Caption</label>
                        <div class="mt-1">
                            <textarea id="caption" name="caption" rows="3" required
                                class="shadow-sm focus:ring-brand-500 focus:border-brand-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md"
                                placeholder="Describe your activity..."></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="/ecotrip/module2/view_challenge.php"
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                            Submit for Review
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</main>

<script>
    function previewImage(input) {
        var preview = document.getElementById('preview');
        var placeholder = document.getElementById('upload-placeholder');

        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                // Optionally hide placeholder, or keep it
                // placeholder.classList.add('hidden'); 
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php';
include '../footer.php';
?>
</body>

</html>