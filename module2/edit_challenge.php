<?php
// edit_challenge.php
include("../database.php");
require '../header.php';

// 1. Check for ID and Fetch Challenge Data
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect if no valid ID provided
    header("Location: manage_challenge.php");
    exit();
}

$challenge_id = (int) $_GET['id'];
$sql = "SELECT * FROM challenge WHERE Challenge_ID = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $challenge_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Challenge not found.";
    exit();
}

$challenge = $result->fetch_assoc();

// 2. Fetch Categories for Dropdown (with selection logic)
$cat_sql = "SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName";
$cat_result = $con->query($cat_sql);

// 3. Fetch Cities for Dropdown (with selection logic)
$city_sql = "SELECT CityID, CityName, State FROM city ORDER BY CityName";
$city_result = $con->query($city_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Challenge - ecoTrip</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css1/create_challenge.css">
</head>

<body>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Edit Challenge</h1>
            <p class="page-subtitle">Update the details below to modify the eco-challenge</p>
        </div>

        <form id="challengeForm" action="process_challenge.php" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="action" value="update">
            <input type="hidden" name="challenge_id" value="<?php echo $challenge['Challenge_ID']; ?>">
            <input type="hidden" name="existing_photo"
                value="<?php echo htmlspecialchars($challenge['photo_upload']); ?>">

            <div class="grid-item-title form-group">
                <label for="title">Challenge Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($challenge['Title']); ?>"
                    placeholder="Enter challenge title" required>
            </div>

            <div class="grid-item-category form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php
                    if ($cat_result->num_rows > 0) {
                        while ($row = $cat_result->fetch_assoc()) {
                            $selected = ($row['CategoryID'] == $challenge['Category_ID']) ? 'selected' : '';
                            echo '<option value="' . $row['CategoryID'] . '" ' . $selected . '>' . htmlspecialchars($row['CategoryName']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="grid-item-points form-group">
                <label for="points">Points</label>
                <input type="number" id="points" name="points"
                    value="<?php echo htmlspecialchars($challenge['Points']); ?>" placeholder="e.g., 200" required>
            </div>

            <div class="grid-item-preview-desc form-group">
                <label for="preview_description">Preview Description</label>
                <input type="text" id="preview_description" name="preview_description"
                    value="<?php echo htmlspecialchars($challenge['preview_description']); ?>"
                    placeholder="Short description for card preview">
            </div>

            <div class="grid-item-city form-group">
                <label for="city_id">City/Location</label>
                <select id="city_id" name="city_id" required>
                    <option value="">Select City</option>
                    <?php
                    if ($city_result->num_rows > 0) {
                        while ($row = $city_result->fetch_assoc()) {
                            $city_display = htmlspecialchars($row['CityName']);
                            if (!empty($row['State'])) {
                                $city_display .= ", " . htmlspecialchars($row['State']);
                            }
                            $selected = ($row['CityID'] == $challenge['City_ID']) ? 'selected' : '';
                            echo '<option value="' . $row['CityID'] . '" ' . $selected . '>' . $city_display . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="grid-item-difficulty form-group">
                <label for="difficulty">Difficulty Level</label>
                <select id="difficulty" name="difficulty" required>
                    <option value="">Select Difficulty</option>
                    <option value="Easy" <?php echo ($challenge['Difficulty'] == 'Easy') ? 'selected' : ''; ?>>Easy
                    </option>
                    <option value="Medium" <?php echo ($challenge['Difficulty'] == 'Medium') ? 'selected' : ''; ?>>Medium
                    </option>
                    <option value="Hard" <?php echo ($challenge['Difficulty'] == 'Hard') ? 'selected' : ''; ?>>Hard
                    </option>
                </select>
            </div>

            <div class="grid-item-start-date form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date"
                    value="<?php echo htmlspecialchars($challenge['Start_date']); ?>" required>
            </div>

            <div class="grid-item-end-date form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date"
                    value="<?php echo htmlspecialchars($challenge['End_date']); ?>" required>
            </div>

            <div class="grid-item-status form-group">
                <label for="status">Challenge Status</label>
                <select id="status" name="status" required>
                    <option value="Draft" <?php echo ($challenge['status'] == 'Draft') ? 'selected' : ''; ?>>Draft
                    </option>
                    <option value="Active" <?php echo ($challenge['status'] == 'Active') ? 'selected' : ''; ?>>Active
                    </option>
                    <option value="Inactive" <?php echo ($challenge['status'] == 'Inactive') ? 'selected' : ''; ?>>
                        Inactive</option>
                </select>
            </div>

            <div class="grid-item-detailed-desc form-group">
                <label for="detailed_description">Detailed Description</label>
                <textarea id="detailed_description" name="detailed_description"
                    placeholder="Provide a comprehensive description..."
                    required><?php echo htmlspecialchars($challenge['Detailed_Description']); ?></textarea>
            </div>

            <div class="grid-item-photo-upload form-group">
                <label for="photo_upload">Challenge Photo</label>

                <?php
                // 1. Check if we have a photo value from the database
                // Make sure the key matches your DB column exactly (e.g., 'photo_upload' or 'Photo_Upload')
                $db_photo = $challenge['photo_upload'] ?? '';
                $has_image = !empty($db_photo);

                // 2. Fix the path: If the DB value doesn't already allow for the folder, add it.
                // Assuming your images are in the 'uploads/' folder relative to this file:
                if ($has_image && strpos($db_photo, 'uploads/') === false) {
                    $image_path = 'uploads/' . htmlspecialchars($db_photo);
                } else {
                    $image_path = htmlspecialchars($db_photo);
                }
                ?>

                <div id="dropZone" class="upload-area">
                    <div id="uploadContent" class="upload-content <?php echo $has_image ? 'hidden' : ''; ?>">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p class="upload-text">
                            <strong>Click to change</strong> or drag and drop
                        </p>
                        <p class="upload-subtext">PNG, JPG up to 10MB</p>
                    </div>

                    <div id="imagePreview" class="image-preview <?php echo $has_image ? '' : 'hidden'; ?>">
                        <img src="<?php echo $image_path; ?>" alt="Preview" id="previewImgElement">
                        <button type="button" id="removeImageBtn" class="remove-image-btn">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <input type="file" id="photo_upload" name="photo_upload" accept="image/*" style="display: none;">
            </div>

            <input type="hidden" name="action" value="update">
            <input type="hidden" name="challenge_id" value="<?php echo $challenge['Challenge_ID']; ?>">
            <input type="hidden" name="existing_photo"
                value="<?php echo htmlspecialchars($challenge['photo_upload']); ?>">

            <div class="grid-item-buttons">
                <button type="button" class="cancel-btn" onclick="window.location.href='manage_challenge.php'">Cancel</button>
                <button type="submit" class="create-btn">
                    <i class="fa-solid fa-plus"></i> Edit Challenge
                </button>
            </div>
        </form>
    </div>

    <footer>
        <div class="footer-content">
            <p class="footer-text">&copy; 2025 ecoTrip Inc. All rights reserved. Designed for a greener tomorrow.</p>
        </div>
    </footer>

    <script src="../js/edit_challenge.js"></script>

    <script>
        // Additional Logic for Edit Mode
        document.addEventListener('DOMContentLoaded', function () {
            // If we are in edit mode and have an image, we need to ensure the JS variables in create_challenge.js
            // interact correctly if the user decides to remove the existing image.

            const previewImg = document.querySelector('#imagePreview img');
            const uploadContent = document.getElementById('uploadContent');
            const imagePreview = document.getElementById('imagePreview');
            const photoInput = document.getElementById('photo_upload');

            // Allow form submission without a NEW file if it's an update
            const form = document.getElementById('challengeForm');

            // We need to override the specific validation logic from create_challenge.js 
            // that prevents submission if photoInput is empty.
            // Since we can't easily edit the external JS file, we attach a new submit listener
            // that runs BEFORE the external one (if possible) or we rely on the backend to handle "no new file".

            // However, create_challenge.js has a strict check: if (!photoInput.files || photoInput.files.length === 0)
            // We need to trick it or bypass it if an existing photo is present.
            // A clean way is to remove the "required" check if an existing photo is visually present.

            // NOTE: Since create_challenge.js prevents default on submit if no file is selected, 
            // you might need to modify create_challenge.js to check if we are in "edit mode" 
            // OR we can inject a dummy file object (complex) 
            // OR simpler: we remove the event listener from the external file (hard)

            // BEST APPROACH without touching external JS:
            // Remove the 'required' check via a flag or modification. 
            // Since I cannot change your JS file in this prompt, 
            // I will assume you will modify `create_challenge.js` slightly 
            // to allow submission if a hidden input "existing_photo" has a value.
        });
    </script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>

</html>
<?php
$con->close();
?>