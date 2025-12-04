<?php
include("../database.php");
require '../header.php';


// 1. Get Challenge ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_challenge.php");
    exit();
}

$current_id = (int) $_GET['id'];

// 2. Fetch Main Challenge Details
$sql = "SELECT c.*, cat.CategoryName, city.CityName, city.State 
        FROM challenge c
        LEFT JOIN category cat ON c.Category_ID = cat.CategoryID
        LEFT JOIN city city ON c.City_ID = city.CityID
        WHERE c.Challenge_ID = ? AND c.Status = 'Active'";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $current_id);
$stmt->execute();
$result = $stmt->get_result();
$challenge = $result->fetch_assoc();

if (!$challenge) {
    echo "Challenge not found or inactive.";
    exit();
}

// 3. Recommendation Logic (The "Tag" System)
// We calculate a 'relevance_score' based on matching Category, City, and Difficulty.
$rec_sql = "SELECT c.*, cat.CategoryName, city.CityName,
            (
                (CASE WHEN c.Category_ID = ? THEN 10 ELSE 0 END) +
                (CASE WHEN c.City_ID = ? THEN 5 ELSE 0 END) +
                (CASE WHEN c.Difficulty = ? THEN 3 ELSE 0 END)
            ) as relevance_score
            FROM challenge c
            LEFT JOIN category cat ON c.Category_ID = cat.CategoryID
            LEFT JOIN city city ON c.City_ID = city.CityID
            WHERE c.Challenge_ID != ? AND c.Status = 'Active'
            HAVING relevance_score > 0
            ORDER BY relevance_score DESC, c.Start_Date DESC
            LIMIT 3";

$rec_stmt = $con->prepare($rec_sql);
$rec_stmt->bind_param(
    "iisi",
    $challenge['Category_ID'],
    $challenge['City_ID'],
    $challenge['Difficulty'],
    $current_id
);
$rec_stmt->execute();
$recommendations = $rec_stmt->get_result();

// Helper variables for Main Challenge
$main_diff_class = strtolower($challenge['Difficulty']); // easy, medium, hard
$main_cat_class = strtolower(str_replace(' ', '-', $challenge['CategoryName']));
$main_image = !empty($challenge['photo_upload']) ? "uploads/" . $challenge['photo_upload'] : "https://images.unsplash.com/photo-1542838132-92c53300491e?w=800";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($challenge['Title']); ?> - ecoTrip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css1/style.css">
    <link rel="stylesheet" href="../css1/challenge_detail.css">
</head>

<body>

    <div class="fixed top-24 left-6 z-50">
        <a href="view_challenge.php"
            class="inline-flex items-center px-4 py-2.5 bg-white/90 backdrop-blur-md border border-gray-200 rounded-full shadow-sm text-sm font-semibold text-gray-700 hover:bg-white hover:text-green-600 hover:shadow-md transition-all duration-300 group">
            <i class="fa-solid fa-arrow-left mr-2 transition-transform duration-300 group-hover:-translate-x-1"></i>
            Back
        </a>
    </div>

    <div class="detail-container">

        <main class="main-column">
            <div class="detail-card">
                <div class="detail-image-wrapper">
                    <img src="<?php echo $main_image; ?>" alt="Challenge Cover" class="detail-image">
                    <span class="detail-location">
                        <i class="fa-solid fa-location-dot"></i>
                        <?php echo htmlspecialchars($challenge['CityName'] . ", " . $challenge['State']); ?>
                    </span>
                </div>

                <div class="detail-content">
                    <div class="badges-row">
                        <span class="badge badge-category <?php echo $main_cat_class; ?>">
                            <?php echo htmlspecialchars($challenge['CategoryName']); ?>
                        </span>
                        <span class="badge badge-difficulty difficulty-<?php echo $main_diff_class; ?>">
                            <?php echo htmlspecialchars($challenge['Difficulty']); ?>
                        </span>
                        <span class="badge badge-points">
                            <i class="fa-solid fa-coins"></i> <?php echo htmlspecialchars($challenge['Points']); ?> pts
                        </span>
                    </div>

                    <h1 class="detail-title"><?php echo htmlspecialchars($challenge['Title']); ?></h1>

                    <div class="meta-item">
                        <span class="meta-label">Start Date</span>
                        <span class="meta-value">
                            <?php echo date("d M Y", strtotime($challenge['Start_date'] ?? $challenge['Start_Date'])); ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">End Date</span>
                        <span class="meta-value">
                            <?php echo date("d M Y", strtotime($challenge['End_date'] ?? $challenge['End_Date'])); ?>
                        </span>
                    </div>

                    <hr class="divider">

                    <h2 class="section-title">Description</h2>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($challenge['Detailed_Description'])); ?>
                    </div>

                    <div class="action-area">
                        <a href="/ecotrip/module3/submit_proof.php?challenge_id=<?php echo $current_id; ?>"
                            class="join-btn" style="text-decoration: none; display: inline-block; text-align: center;">
                            Join Challenge Now
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <aside class="sidebar-column">
            <h3 class="sidebar-title">Similar Challenges</h3>
            <p class="sidebar-subtitle">Based on category & location</p>

            <div class="recommendation-list">
                <?php if ($recommendations->num_rows > 0): ?>
                    <?php while ($rec = $recommendations->fetch_assoc()):
                        $rec_image = !empty($rec['photo_upload']) ? "uploads/" . $rec['photo_upload'] : "https://images.unsplash.com/photo-1542838132-92c53300491e?w=400";
                        $rec_diff = strtolower($rec['Difficulty']);
                        ?>
                        <a href="challenge_detail.php?id=<?php echo $rec['Challenge_ID']; ?>" class="rec-card">
                            <div class="rec-image">
                                <img src="<?php echo $rec_image; ?>" alt="">
                            </div>
                            <div class="rec-content">
                                <span class="rec-category"><?php echo htmlspecialchars($rec['CategoryName']); ?></span>
                                <h4 class="rec-title"><?php echo htmlspecialchars($rec['Title']); ?></h4>
                                <div class="rec-footer">
                                    <span class="rec-difficulty difficulty-<?php echo $rec_diff; ?>">
                                        <?php echo htmlspecialchars($rec['Difficulty']); ?>
                                    </span>
                                    <span class="rec-points"><?php echo $rec['Points']; ?> pts</span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-rec">
                        <p>No similar challenges found at the moment.</p>
                        <a href="view_challenge.php" class="text-green-600 text-sm hover:underline">View all challenges</a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

    </div>

    <script src="../js/challenge_detail.js"></script>
</body>

</html>
<?php
$stmt->close();
$rec_stmt->close();
$con->close();
?>