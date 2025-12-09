<?php
include("../database.php");


// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    $delete_sql = "DELETE FROM challenge WHERE Challenge_ID = ?";
    $stmt = $con ->prepare($delete_sql);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        header("Location: manage_challenge.php?deleted=1");
        exit();
    }
    $stmt->close();
}

// --- PAGINATION LOGIC START ---
$results_per_page = 10; // Set limit to 10 challenges per page

// Determine which page number visitor is currently on
if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

// Determine the sql LIMIT starting number for the results on the displaying page
$this_page_first_result = ($page - 1) * $results_per_page;
// --- PAGINATION LOGIC END ---

// Count total challenges (Moved up to calculate total pages before main query)
$count_result = $con->query("SELECT COUNT(*) as total FROM challenge");
$total_challenges = $count_result->fetch_assoc()['total'];

// Determine number of total pages available
$number_of_pages = ceil($total_challenges / $results_per_page);

// Fetch challenges with LIMIT for pagination
$sql = "SELECT c.Challenge_ID, 
        c.Category_ID, 
        c.City_ID, 
        c.Created_by, 
        c.Title, 
        c.Preview_Description, 
        c.Detailed_Description, 
        c.Difficulty, 
        c.Points, 
        c.Start_Date, 
        c.End_Date, 
        c.status, 
        c.photo_upload,
        cat.CategoryName, 
        city.CityName, 
        city.State 
        FROM challenge c
        LEFT JOIN category cat ON c.Category_ID = cat.CategoryID
        LEFT JOIN city city ON c.City_ID = city.CityID
        ORDER BY c.Challenge_ID DESC
        LIMIT " . $this_page_first_result . ',' .  $results_per_page; // Added LIMIT clause

$result = $con->query($sql);

// Fetch categories for filter
$cat_sql = "SELECT DISTINCT CategoryName FROM category ORDER BY CategoryName";
$cat_result = $con->query($cat_sql);

// Fetch cities for filter
$city_sql = "SELECT DISTINCT CityName, State FROM city ORDER BY CityName";
$city_result = $con->query($city_sql);

require '../header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ecoTrip - Manage Challenges</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css1/manage_challenge.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { brand: { 500: '#22c55e', 600: '#16a34a' } }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans">


    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <?php if (isset($_GET['deleted'])): ?>
            <div
                class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fa-solid fa-check-circle"></i>
                <span>Challenge deleted successfully!</span>
            </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manage Challenges</h1>
                <p class="text-sm text-gray-500 mt-1">Create, edit, or archive system challenges.</p>
            </div>
            <div class="flex gap-3">
                <a href="import_challenge.php" class="bg-white px-4 py-2 rounded-lg border border-gray-300 ...">
                    <i class="fa-solid fa-file-import"></i> Import CSV
                </a>

                <a href="csv_handler.php?action=export" class="bg-white px-4 py-2 rounded-lg border border-gray-300 ...">
                    <i class="fa-solid fa-file-export"></i> Export CSV
                </a>
                <a href="create_challenge.php"
                    class="px-4 py-2 rounded-lg bg-brand-600 text-white text-sm font-medium hover:bg-brand-700 shadow-sm flex items-center gap-2 no-underline">
                    <i class="fa-solid fa-plus"></i> Add Challenge
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search challenges..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>

                <select id="categoryFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    <option value="">All Categories</option>
                    <?php
                    $cat_result->data_seek(0);
                    while ($cat = $cat_result->fetch_assoc()) {
                        echo "<option value='" . htmlspecialchars($cat['CategoryName']) . "'>" . htmlspecialchars($cat['CategoryName']) . "</option>";
                    }
                    ?>
                </select>

                <select id="cityFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    <option value="">All Cities</option>
                    <?php
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

                <select id="statusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Draft">Draft</option>
                    <option value="Archived">Archived</option>
                    <option value="Inactive">Inactive</option>
                </select>

                <select id="difficultyFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    <option value="">All Difficulty</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Challenge Name</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                City</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Difficulty</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Points</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Dates</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status = ucfirst(strtolower($row['status']));
                                $status_class = '';
                                switch (strtolower($row['status'])) {
                                    case 'active':
                                        $status_class = 'bg-green-100 text-green-800 border-green-200';
                                        break;
                                    case 'draft':
                                        $status_class = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                        break;
                                    case 'archived':
                                        $status_class = 'bg-gray-100 text-gray-800 border-gray-200';
                                        break;
                                    default:
                                        $status_class = 'bg-blue-100 text-blue-800 border-blue-200';
                                }

                                $start_date = !empty($row['Start_Date']) ? date('M d, Y', strtotime($row['Start_Date'])) : 'N/A';
                                $end_date = !empty($row['End_Date']) ? date('M d, Y', strtotime($row['End_Date'])) : 'N/A';

                                $city_display = htmlspecialchars($row['CityName']);
                                if (!empty($row['State'])) {
                                    $city_display .= ", " . htmlspecialchars($row['State']);
                                }
                                ?>
                                <tr class="hover:bg-gray-50 transition challenge-row"
                                    data-title="<?php echo htmlspecialchars(strtolower($row['Title'])); ?>"
                                    data-category="<?php echo htmlspecialchars($row['CategoryName']); ?>"
                                    data-city="<?php echo htmlspecialchars($row['CityName']); ?>"
                                    data-status="<?php echo htmlspecialchars($status); ?>"
                                    data-difficulty="<?php echo htmlspecialchars($row['Difficulty']); ?>">

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                        <div class="flex items-center gap-3">
                                            <button onclick="initiateSmartClone(<?php echo $row['Challenge_ID']; ?>)"
                                                class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-1 rounded transition-colors"
                                                title="Smart Clone (Next Month)">
                                                <i class="fa-regular fa-copy"></i>
                                            </button>
                                            <span><?php echo htmlspecialchars($row['Challenge_ID']); ?></span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold border <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($row['Title']); ?>
                                        </div>
                                        <?php if (!empty($row['Preview_Description'])): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <?php echo htmlspecialchars(substr($row['Preview_Description'], 0, 50)); ?>...
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($row['CategoryName']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $city_display; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?php echo htmlspecialchars($row['Difficulty']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700">
                                        <?php echo htmlspecialchars($row['Points']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                        <?php if (!empty($row['Start_Date']) && !empty($row['End_Date'])): ?>
                                            <div><?php echo $start_date; ?></div>
                                            <div>to <?php echo $end_date; ?></div>
                                        <?php else: ?>
                                            <div class="text-gray-400">No dates set</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="edit_challenge.php?id=<?php echo $row['Challenge_ID']; ?>"
                                            class="text-gray-400 hover:text-brand-600 mr-3" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <button
                                            onclick="confirmDelete(<?php echo $row['Challenge_ID']; ?>, '<?php echo htmlspecialchars($row['Title'], ENT_QUOTES); ?>')"
                                            class="text-gray-400 hover:text-red-600" title="Delete">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fa-solid fa-inbox text-4xl text-gray-300 mb-2"></i>
                                    <p class="text-sm">No challenges found.</p>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="bg-white px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4 rounded-b-xl">
                
                <div class="text-sm text-gray-700 font-medium" id="entryCount">
                    Showing 
                    <span class="font-bold text-gray-900">
                    <?php 
                        $start_display = ($total_challenges > 0) ? $this_page_first_result + 1 : 0;
                        $end_display = min(($this_page_first_result + $results_per_page), $total_challenges);
                        echo $start_display . ' - ' . $end_display; 
                    ?>
                    </span>
                    of 
                    <span class="font-bold text-gray-900"><?php echo $total_challenges; ?></span> 
                    entries
                </div>

                <div class="flex gap-2 items-center">
                    <?php 
                    // Common styling for all buttons (Increased padding: px-4 py-2, Larger Text: text-sm)
                    $btn_base = "relative inline-flex items-center px-4 py-2 text-sm font-medium border rounded-md transition-all duration-200 focus:z-20 focus:outline-offset-0 shadow-sm";
                    $btn_inactive = "text-gray-700 bg-white border-gray-300 hover:bg-gray-50 hover:text-brand-600";
                    $btn_active = "z-10 bg-brand-600 text-white border-brand-600 hover:bg-brand-700 shadow-md transform scale-105";

                    // Previous Button
                    if($page > 1){
                        echo '<a href="manage_challenge.php?page='.($page-1).'" class="'.$btn_base.' '.$btn_inactive.'">
                                <i class="fa-solid fa-chevron-left mr-2 text-xs"></i> Previous
                              </a>';
                    } else {
                        // Optional: Disabled state if you want to show it but unclickable
                        echo '<span class="'.$btn_base.' text-gray-300 bg-gray-50 border-gray-200 cursor-not-allowed">
                                <i class="fa-solid fa-chevron-left mr-2 text-xs"></i> Previous
                              </span>';
                    }

                    // Page Numbers
                    for ($page_num = 1; $page_num <= $number_of_pages; $page_num++) {
                        // Apply active or inactive style
                        $style = ($page_num == $page) ? $btn_active : $btn_inactive;
                        
                        echo '<a href="manage_challenge.php?page='.$page_num.'" class="'.$btn_base.' '.$style.'">
                                '.$page_num.'
                              </a>';
                    }

                    // Next Button
                    if($page < $number_of_pages){
                        echo '<a href="manage_challenge.php?page='.($page+1).'" class="'.$btn_base.' '.$btn_inactive.'">
                                Next <i class="fa-solid fa-chevron-right ml-2 text-xs"></i>
                              </a>';
                    } else {
                         echo '<span class="'.$btn_base.' text-gray-300 bg-gray-50 border-gray-200 cursor-not-allowed">
                                Next <i class="fa-solid fa-chevron-right ml-2 text-xs"></i>
                              </span>';
                    }
                    ?>
                </div>
            </div>
            </div>
        </div>
    </main>

    <script src="../js/manage_challenge.js"></script>
    <script src="../js/delete_challenge.js"></script>
    <script src="../js/smart_clone.js"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>
</html>
<?php
$con->close();
?>