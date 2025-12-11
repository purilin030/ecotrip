<?php
// 1. Database Connection
include("../database.php");
require '../header.php';


// 2. Fetch Categories & Cities
include("get_categories.php");
include("get_cities.php");
include("../background.php")
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Challenge - ecoTrip</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css1/create_challenge.css">
</head>

<body>
    

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Create New Challenge</h1>
            <p class="page-subtitle">Fill in the details below to create an eco-challenge</p>
        </div>

        <form id="challengeForm" action="process_challenge.php" method="POST" enctype="multipart/form-data">
            <div class="grid-item-title form-group">
                <label for="title">Challenge Title</label>
                <input type="text" id="title" name="title" placeholder="Enter challenge title" required>
            </div>

            <div class="grid-item-category form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php echo $category_options; ?>
                </select>
            </div>

            <div class="grid-item-points form-group">
                <label for="points">Points</label>
                <input type="number" id="points" name="points" placeholder="e.g., 200" required>
            </div>

            <div class="grid-item-preview-desc form-group">
                <label for="preview_description">Preview Description</label>
                <input type="text" id="preview_description" name="preview_description" placeholder="Short description for card preview">
            </div>

            <div class="grid-item-city form-group">
                <label for="city_id">City/Location</label>
                <select id="city_id" name="city_id" required>
                    <option value="">Select City</option>
                    <?php echo $city_options; ?>
                </select>
            </div>

            <div class="grid-item-difficulty form-group">
                <label for="difficulty">Difficulty Level</label>
                <select id="difficulty" name="difficulty" required>
                    <option value="">Select Difficulty</option>
                    <option value="Easy">Easy</option>
                    <option value="Medium">Medium</option>
                    <option value="Hard">Hard</option>
                </select>
            </div>
            
            <div class="grid-item-start-date form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>

            <div class="grid-item-end-date form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>

            <div class="grid-item-status form-group">
                <label for="status">Challenge Status</label>
                <select id="status" name="status" required>
                    <option value="Draft">Draft</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="grid-item-detailed-desc form-group">
                <label for="detailed_description">Detailed Description</label>
                <textarea id="detailed_description" name="detailed_description" placeholder="Provide a comprehensive description of the challenge, rules, and requirements..." required></textarea>
            </div>

            <div class="grid-item-photo-upload form-group">
                <label for="photo_upload">Challenge Photo</label>
                <div id="dropZone" class="upload-area">
                    <div id="uploadContent" class="upload-content">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p class="upload-text"><strong>Click to upload</strong> or drag and drop</p>
                        <p class="upload-subtext">PNG, JPG up to 10MB</p>
                    </div>
                    <div id="imagePreview" class="image-preview hidden">
                        <img src="" alt="Preview">
                        <button type="button" id="removeImageBtn" class="remove-image-btn">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <input type="file" id="photo_upload" name="photo_upload" accept="image/*" style="display: none;">
            </div>

            <input type="hidden" name="action" value="create">

            <div class="grid-item-buttons">
                <button type="reset" class="cancel-btn">Cancel</button>
                <button type="submit" class="create-btn">
                    <i class="fa-solid fa-plus"></i> Create Challenge
                </button>
            </div>
        </form>
    </div>

    <footer>
        <div class="footer-content">
            <p class="footer-text">&copy; 2025 ecoTrip Inc. All rights reserved. Designed for a greener tomorrow.</p>
        </div>
    </footer>

    <script src="../js/create_challenge.js"></script>
</body>
</html>