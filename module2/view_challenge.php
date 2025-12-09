<?php
include("../database.php");
require '../header.php';

// Fetch all challenges from database
$sql = "SELECT c.*, 
        cat.CategoryName, 
        city.CityName, 
        city.State 
        FROM challenge c
        LEFT JOIN category cat ON c.Category_ID = cat.CategoryID
        LEFT JOIN city city ON c.City_ID = city.CityID
        WHERE c.Status = 'Active'
        ORDER BY c.Start_Date DESC";

$result = $con->query($sql);

// Fetch categories for filter
$cat_sql = "SELECT DISTINCT CategoryName FROM category ORDER BY CategoryName";
$cat_result = $con->query($cat_sql);

// Fetch cities for filter
$city_sql = "SELECT DISTINCT CityName, State FROM city ORDER BY CityName";
$city_result = $con->query($city_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ecoTrip - Challenges</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css1/style.css">
    <link rel="stylesheet" href="../css1/view_challenge.css">
</head>

<body>

    <main class="main-content">

        <div class="header-section">
            <h1 class="page-title">Eco Challenges</h1>
            <p class="page-subtitle">Join challenges, earn points, and make an impact.</p>

            <div class="filter-section">
                <div class="search-wrapper">
                    <div class="search-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <input type="text" class="search-input" placeholder="Search challenges..." id="searchInput">
                </div>

                <div class="select-wrapper">
                    <select class="category-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php
                        while ($cat = $cat_result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($cat['CategoryName']) . "'>" . htmlspecialchars($cat['CategoryName']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="select-wrapper">
                    <select class="category-select" id="cityFilter">
                        <option value="">All Cities</option>
                        <?php
                        // Reset pointer for safety if reused
                        $city_result->data_seek(0);
                        while ($city = $city_result->fetch_assoc()) {
                            $city_label = htmlspecialchars($city['CityName']);
                            if (!empty($city['State'])) {
                                $city_label .= ", " . htmlspecialchars($city['State']);
                            }
                            echo "<option value='" . htmlspecialchars($city['CityName']) . "'>" . $city_label . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="select-wrapper">
                    <select class="category-select" id="difficultyFilter">
                        <option value="">All Difficulties</option>
                        <option value="Easy">Easy</option>
                        <option value="Medium">Medium</option>
                        <option value="Hard">Hard</option>
                    </select>
                </div>

                <div class="select-wrapper">
                    <select class="category-select" id="pointsFilter">
                        <option value="">All Points</option>
                        <option value="0-200">0 - 200 pts</option>
                        <option value="201-500">201 - 500 pts</option>
                        <option value="501-1000">501 - 1000 pts</option>
                        <option value="1000+">1000+ pts</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="challenges-grid">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Category styling
                    $category_class = strtolower(str_replace(' ', '-', $row['CategoryName']));

                    // Difficulty styling (Easy -> difficulty-easy)
                    $difficulty_class = strtolower($row['Difficulty']);

                    // Image logic
                    $image_src = !empty($row['photo_upload']) ?
                        "uploads/" . htmlspecialchars($row['photo_upload']) :
                        "https://images.unsplash.com/photo-1542838132-92c53300491e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80";

                    // Location logic
                    $location = htmlspecialchars($row['CityName']);
                    if (!empty($row['State'])) {
                        $location .= ", " . htmlspecialchars($row['State']);
                    }
                    ?>
                    
                    <a href="challenge_detail.php?id=<?php echo $row['Challenge_ID']; ?>" class="card-link-wrapper">
                        <div class="challenge-card" 
                            data-category="<?php echo htmlspecialchars($row['CategoryName']); ?>"
                            data-title="<?php echo htmlspecialchars($row['Title']); ?>"
                            data-city="<?php echo htmlspecialchars($row['CityName']); ?>"
                            data-points="<?php echo htmlspecialchars($row['Points']); ?>"
                            data-difficulty="<?php echo htmlspecialchars($row['Difficulty']); ?>">

                            <div class="challenge-image">
                                <span class="challenge-location"><?php echo $location; ?></span>
                                <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($row['Title']); ?>">

                                <?php if (!empty($row['preview_description'])): ?>
                                    <div class="challenge-popover">
                                        <?php echo htmlspecialchars($row['preview_description']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="challenge-content">
                                <div class="flex gap-2 mb-3">
                                    <div class="challenge-category <?php echo $category_class; ?>">
                                        <?php echo htmlspecialchars($row['CategoryName']); ?>
                                    </div>
                                    <?php if (!empty($row['Difficulty'])): ?>
                                        <div class="challenge-difficulty difficulty-<?php echo $difficulty_class; ?>">
                                            <?php echo htmlspecialchars($row['Difficulty']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <h3 class="challenge-title"><?php echo htmlspecialchars($row['Title']); ?></h3>

                                <div class="challenge-footer">
                                    <div class="challenge-points">
                                        <i class="fa-solid fa-coins"></i> <?php echo htmlspecialchars($row['Points']); ?>
                                    </div>
                                    <i class="fa-solid fa-arrow-right challenge-arrow"></i>
                                </div>
                            </div>
                        </div>
                    </a>

                    <?php
                }
            } else {
                echo "<p style='grid-column: 1/-1; text-align: center; padding: 3rem; color: #64748b;'>No challenges available at the moment. Check back soon!</p>";
            }
            ?>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p class="footer-text">
                &copy; 2025 ecoTrip Inc. All rights reserved. Designed for a greener tomorrow.
            </p>
        </div>
    </footer>

    <script src="../js/view_challenge.js"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>

</html>
<?php
$con->close();
?>