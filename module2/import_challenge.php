<?php
include("../database.php");
require '../header.php';

// 1. Fetch Valid Categories (Trimmed & Ready for JS)
$valid_categories = [];
$cat_res = $con->query("SELECT CategoryName FROM category");
while ($row = $cat_res->fetch_assoc()) {
    $valid_categories[] = trim($row['CategoryName']);
}

// 2. Fetch Valid Cities (Trimmed & Ready for JS)
$valid_cities = [];
$city_res = $con->query("SELECT CityName FROM city");
while ($row = $city_res->fetch_assoc()) {
    $valid_cities[] = trim($row['CityName']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Import Challenges</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css1/import_styles.css">
</head>
<body class="bg-gray-100 font-sans">

    <div class="max-w-[95%] mx-auto mt-10 p-6 bg-white shadow-lg rounded-xl">
        
        <!-- Header -->
        <div class="border-b pb-4 mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Admin — Import Challenges</h1>
            <p class="text-gray-500 text-sm mt-1">Bulk upload challenges via CSV. Columns must match the Create Challenge form.</p>
        </div>

        <!-- File Upload Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <label class="block font-semibold text-gray-700 mb-2">Select CSV File</label>
                    <p class="text-xs text-gray-500">
                        <strong>Required Columns:</strong> Title, Category, Points, Preview_Description, City, Difficulty, Start_Date, End_Date, Status, Detailed_Description, Photo_Upload
                    </p>
                </div>
                <input type="file" id="csvFileInput" accept=".csv" class="hidden">
                <button onclick="document.getElementById('csvFileInput').click()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded shadow-sm hover:bg-gray-50 text-sm font-medium">
                    <i class="fa-solid fa-folder-open mr-2"></i> Browse CSV...
                </button>
            </div>
            
            <!-- Counters -->
            <div class="flex gap-6 text-sm">
                <span class="text-gray-600 font-medium">Valid rows: <span id="validCount" class="text-green-600 font-bold">0</span></span>
                <span class="text-gray-600 font-medium">Errors: <span id="errorCount" class="text-red-600 font-bold">0</span></span>
            </div>
        </div>

        <!-- Preview Table (Scrollable) -->
        <div class="overflow-x-auto border rounded-lg max-h-[600px]">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead class="bg-blue-100 text-gray-700 text-xs uppercase font-bold sticky top-0 z-10">
                    <tr>
                        <th class="p-3 border-b">#</th>
                        <th class="p-3 border-b">Title</th>
                        <th class="p-3 border-b">Category</th>
                        <th class="p-3 border-b">Pts</th>
                        <th class="p-3 border-b">Preview Desc</th>
                        <th class="p-3 border-b">City</th>
                        <th class="p-3 border-b">Difficulty</th>
                        <th class="p-3 border-b">Start</th>
                        <th class="p-3 border-b">End</th>
                        <th class="p-3 border-b">Status</th>
                        <th class="p-3 border-b">Detailed Desc</th>
                        <th class="p-3 border-b">Photo</th>
                        <th class="p-3 border-b sticky right-0 bg-blue-100 shadow-l text-center">Validation</th>
                    </tr>
                </thead>
                <tbody id="previewTableBody" class="text-sm text-gray-700 bg-blue-50/30">
                    <tr>
                        <td colspan="13" class="p-8 text-center text-gray-400">
                            No file selected. Please upload a CSV file to preview.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 mt-6">
            <a href="manage_challenge.php" class="px-5 py-2 border border-gray-300 rounded text-gray-600 hover:bg-gray-50 font-medium">Cancel</a>
            <button id="importBtn" onclick="processImport()" disabled class="px-5 py-2 bg-green-600 text-white rounded font-medium opacity-50 cursor-not-allowed transition-colors">
                Import Challenges
            </button>
        </div>

    </div>

    <!-- Pass PHP Data to JS -->
    <script>
        const validCategories = <?php echo json_encode($valid_categories); ?>;
        const validCities = <?php echo json_encode($valid_cities); ?>;
    </script>
    <script src="../js/import_logic.js?v=4"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>
</html>