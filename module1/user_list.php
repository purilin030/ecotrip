<?php
session_start();
require 'database.php';

// 1. Security check (Only admin and moderator can enter)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
$auth_res = mysqli_query($con, $auth_sql);
$auth_row = mysqli_fetch_assoc($auth_res);

if ($auth_row['Role'] == 0) {
    header("Location: index.php");
    exit();
}

// 2. Get all user data (Straight forward SELECT *，不关联 Team 表)
$sql = "SELECT * FROM user ORDER BY User_ID ASC";
$result = mysqli_query($con, $sql);

$page_title = "User List (Raw Data)";
include '../header.php';
?>

<main class="flex-grow w-full px-4 py-8">
    <div class="w-full">

        <h1 class="text-2xl font-bold text-gray-900 mb-4">User List (Raw Database View)</h1>

        <div class="overflow-x-auto bg-white border border-gray-300 shadow-sm">
            <table class="min-w-full divide-y divide-gray-300 text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">User_ID</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">First_Name</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Last_Name</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Caption</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">User_DOB</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Avatar (Path)</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Email</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Phone_num</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Team_ID</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Point</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">RedeemPoint</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Password</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Register_Date</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-900 border-r">Role</th>
                        <th class="px-3 py-2 text-center font-bold text-gray-900">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="hover:bg-gray-50 whitespace-nowrap">
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo $row['User_ID']; ?></td>
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo htmlspecialchars($row['First_Name']); ?>
                                </td>
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo htmlspecialchars($row['Last_Name']); ?>
                                </td>
                                <td class="px-3 py-2 border-r text-gray-500"><?php echo htmlspecialchars($row['Caption']); ?>
                                </td>
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo htmlspecialchars($row['User_DOB']); ?>
                                </td>
                                <td class="px-3 py-2 border-r text-gray-500 text-xs truncate max-w-xs">
                                    <?php echo htmlspecialchars($row['Avatar']); ?></td>
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo $row['Phone_num']; ?></td>
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo $row['Team_ID']; ?></td>
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo $row['Point']; ?></td>
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo $row['RedeemPoint']; ?></td>
                                <td class="px-3 py-2 border-r text-gray-400 text-xs truncate max-w-xs">
                                    <?php echo $row['Password']; ?></td>
                                <td class="px-3 py-2 border-r text-gray-900"><?php echo $row['Register_Date']; ?></td>
                                <td class="px-3 py-2 border-r text-gray-900 font-bold"><?php echo $row['Role']; ?></td>
                                <td class="px-3 py-2 text-center">
                                    <a href="admin_edit_user.php?id=<?php echo $row['User_ID']; ?>"
                                        class="text-blue-600 hover:underline mr-2 font-semibold">Edit</a>

                                    <?php if ($row['User_ID'] != $_SESSION['user_id']): ?>
                                        <a href="admin_delete_user.php?id=<?php echo $row['User_ID']; ?>"
                                            class="text-red-600 hover:underline font-semibold"
                                            onclick="return confirm('WARNING: Are you sure you want to delete User ID <?php echo $row['User_ID']; ?>? This cannot be undone.');">
                                            Delete
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-300 cursor-not-allowed">Delete</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="15" class="px-6 py-4 text-center text-gray-500">No data found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="w-full py-8 px-8">
        <p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc. And you are in Admin page</p>
    </div>
</footer>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>

</html>