<?php
session_start();
require 'database.php';

// 1. Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// =================================================================
// Process form submission (Update Logic)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {
    
    // Received and clear data
    $first_name = mysqli_real_escape_string($con, trim($_POST['first_name']));
    $last_name  = mysqli_real_escape_string($con, trim($_POST['last_name']));
    $dob        = mysqli_real_escape_string($con, trim($_POST['dob']));
    $email      = mysqli_real_escape_string($con, trim($_POST['email']));
    $phone      = mysqli_real_escape_string($con, trim($_POST['phone']));
    
    // 1. Build Base SQL Query
    $update_sql = "UPDATE user SET 
                   First_Name = '$first_name', 
                   Last_Name = '$last_name', 
                   User_DOB = '$dob', 
                   Email = '$email', 
                   Phone_num = '$phone'";

    // 2. Check if Password field is NOT empty (Change Password Logic)
    if (!empty($_POST['password'])) {
        $new_password = mysqli_real_escape_string($con, $_POST['password']);
        $hashed_password = md5($new_password); // Encrypt using MD5
        
        // Append password update to SQL
        $update_sql .= ", Password = '$hashed_password'";
    }

    // 3. Finish SQL Query
    $update_sql .= " WHERE User_ID = '$user_id'";
                   
    if (mysqli_query($con, $update_sql)) {
        // Update Session
        $_SESSION['Firstname'] = $first_name;
        $_SESSION['Lastname'] = $last_name;
        $_SESSION['Email'] = $email;
        
        $_SESSION['flash'] = "Profile updated successfully!";
        header("Location: profile.php"); 
        exit();
    } else {
        $error_msg = "Error updating record: " . mysqli_error($con);
    }
}

// =================================================================
// Check current user data (for input box)
// =================================================================
$sql = "SELECT u.*, t.Team_name 
        FROM user u 
        LEFT JOIN team t ON u.Team_ID = t.Team_ID 
        WHERE u.User_ID = '$user_id'";

$result = mysqli_query($con, $sql);
$user_info = mysqli_fetch_assoc($result);

// Set title and Header
$page_title = "Edit Profile - ecoTrip";
include '../header.php';
?>

    <main class="flex-grow w-full px-8 py-12">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Edit Profile</h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

                <div class="lg:col-span-1 bg-white p-8 rounded-lg shadow-sm border border-gray-200 flex flex-col items-center text-center">
                    <div class="h-32 w-32 rounded-full bg-gray-200 overflow-hidden border-4 border-brand-100 shadow-md mb-4">
                        <img src="<?php echo $display_avatar; ?>" alt="User Avatar" class="h-full w-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-gray-900"><?php echo $user_info['First_Name'] . " " . $user_info['Last_Name']; ?></h3>
                    <p class="text-sm text-gray-500"><?php echo $user_info['Email']; ?></p>
                    <p class="text-xs text-gray-400 mt-4">To change your avatar, go back to the main Profile page.</p>
                </div>

                <div class="lg:col-span-2 bg-white p-8 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 pb-2 border-b border-gray-100">Update Information</h2>
                    
                    <form action="" method="POST">
                        <div class="space-y-6">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                    <input type="text" name="first_name" required
                                           value="<?php echo htmlspecialchars($user_info['First_Name']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                    <input type="text" name="last_name" required
                                           value="<?php echo htmlspecialchars($user_info['Last_Name']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                <input type="date" name="dob" 
                                       value="<?php echo htmlspecialchars($user_info['User_DOB']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" required
                                       value="<?php echo htmlspecialchars($user_info['Email']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" name="phone" 
                                       value="<?php echo htmlspecialchars($user_info['Phone_num']); ?>"
                                       placeholder="e.g. 0123456789"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white">
                            </div>

                            <div class="pt-4 mt-4 border-t border-gray-100">
                                <h3 class="text-md font-bold text-gray-800 mb-4">Security</h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Change Password</label>
                                    <input type="password" name="password" 
                                           placeholder="Enter new password to change (Leave empty to keep current)"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white placeholder-gray-400">
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-1">Current Team</label>
                                        <input type="text" disabled 
                                               value="<?php echo !empty($user_info['Team_name']) ? $user_info['Team_name'] : 'None'; ?>"
                                               class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-1">Role</label>
                                        
                                        <?php 
                                            // Role display logic
                                            $role_display_text = "Member"; 
                                            if ($user_info['Role'] == 1) {
                                                $role_display_text = "Admin";
                                            } elseif ($user_info['Role'] == 2) {
                                                $role_display_text = "Moderator";
                                            }
                                        ?>
                                        
                                        <input type="text" disabled 
                                               value="<?php echo $role_display_text; ?>"
                                               class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed">
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">Team and Role cannot be changed here.</p>
                            </div>

                            <div class="flex items-center gap-4 pt-4">
                                <button type="submit" name="save_changes" class="bg-brand-600 text-white font-bold py-2 px-6 rounded hover:bg-brand-700 transition duration-300 shadow-sm">
                                    Save Changes
                                </button>
                                <a href="profile.php" class="text-gray-500 font-medium hover:text-gray-800 hover:underline">Cancel</a>
                            </div>

                        </div>
                    </form>
                </div>

            </div>

        </div>
    </main>

    <footer class="bg-white border-t border-gray-200">
        <div class="w-full py-8 px-8">
            <p class="text-center text-sm text-gray-400">
                &copy; 2025 ecoTrip Inc. All rights reserved.
            </p>
        </div>
    </footer>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>
</html>