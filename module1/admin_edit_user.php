<?php
session_start();
require 'database.php';

// 1. Safety Check: Only Admin can access
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$current_user_id = $_SESSION['user_id'];
$auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
$auth_res = mysqli_query($con, $auth_sql);
$auth_row = mysqli_fetch_assoc($auth_res);

if ($auth_row['Role'] != 1 ) { header("Location: index.php"); exit(); }

// 2. fetch ID
if (!isset($_GET['id'])) { header("Location: user_list.php"); exit(); }
$target_id = intval($_GET['id']);

// 3. Process "Save" logic (update info)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    // basic info
    $fname = mysqli_real_escape_string($con, $_POST['firstname']);
    $lname = mysqli_real_escape_string($con, $_POST['lastname']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $dob   = mysqli_real_escape_string($con, $_POST['dob']);
    $caption = mysqli_real_escape_string($con, $_POST['caption']);
    $avatar_path = mysqli_real_escape_string($con, $_POST['avatar']); 
    
    // 权限与数值
    $new_role  = intval($_POST['role']);
    $points = intval($_POST['points']);
    $redeem_points = intval($_POST['redeem_points']);
    $new_team_id = intval($_POST['team_id']);
    
    // Process Team ID  (0 转 NULL)
    $team_sql_val = ($new_team_id == 0) ? "NULL" : "'$new_team_id'";

    // Update User table
    $update_sql = "UPDATE user SET 
                   First_Name='$fname', 
                   Last_Name='$lname', 
                   Email='$email', 
                   Phone_num='$phone',
                   User_DOB='$dob',
                   Caption='$caption',
                   Avatar='$avatar_path',
                   Role='$new_role', 
                   Point='$points', 
                   RedeemPoint='$redeem_points',
                   Team_ID=$team_sql_val";

    // Process Password Reset
    if (!empty($_POST['new_password'])) {
        $new_pass_hash = md5($_POST['new_password']); 
        $update_sql .= ", Password='$new_pass_hash'";
    }

    $update_sql .= " WHERE User_ID='$target_id'";

    if (mysqli_query($con, $update_sql)) {
        
        // ======================================================
        // 【NEW】Sync Team Owner Logic
        // ======================================================
        
        // Case A: User set as Team Owner (Role 2) but also has a Team ID
        if ($new_role == 2 && $new_team_id > 0) {
            
            // 1. Update Team table：Set Team ID to new Team owner
            $sync_team = "UPDATE team SET Owner_ID = '$target_id' WHERE Team_ID = '$new_team_id'";
            mysqli_query($con, $sync_team);

            // 2. Clash Process：if the team has other team owner，undergrade the Owner level (Role 0)
            // Protect Mechanic：Does Not Undergrade Admin level (Role 1)
            $downgrade_others = "UPDATE user SET Role = 0 
                                 WHERE Team_ID = '$new_team_id' 
                                 AND User_ID != '$target_id' 
                                 AND Role != 1";
            mysqli_query($con, $downgrade_others);

            // 3. Clear Old Record： If the user change the Team，change his previous Team Owner set as 0
            $clear_old_team = "UPDATE team SET Owner_ID = 0 
                               WHERE Owner_ID = '$target_id' 
                               AND Team_ID != '$new_team_id'";
            mysqli_query($con, $clear_old_team);
        }

        // Case B: User is not Team Owner any more (Downgrade as 0 or 1)
        elseif ($new_role != 2) {
            // Set his previous team's Owner as 0 (relaease the team)
            $release_team = "UPDATE team SET Owner_ID = 0 WHERE Owner_ID = '$target_id'";
            mysqli_query($con, $release_team);
        }

        echo "<script>alert('User updated and team data synchronized!'); window.location.href='user_list.php';</script>";
        exit();
    } else {
        $error_msg = "Error updating: " . mysqli_error($con);
    }
}

// 4. Read current user data
$sql = "SELECT * FROM user WHERE User_ID = '$target_id'";
$result = mysqli_query($con, $sql);
$user = mysqli_fetch_assoc($result);

if (!$user) { echo "User not found."; exit(); }

$page_title = "Edit User #" . $user['User_ID'];
include '../header.php';
?>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-4xl mx-auto">
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Edit User #<?php echo $user['User_ID']; ?></h1>
            <a href="user_list.php" class="text-sm text-gray-500 hover:text-gray-900">Back to List</a>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200">
            <form action="" method="POST">
                
                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">Personal Info</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">First Name</label>
                        <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['First_Name']); ?>" class="w-full mt-1 border-gray-300 rounded-md shadow-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Last Name</label>
                        <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['Last_Name']); ?>" class="w-full mt-1 border-gray-300 rounded-md shadow-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" class="w-full mt-1 border-gray-300 rounded-md shadow-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['Phone_num']); ?>" class="w-full mt-1 border-gray-300 rounded-md shadow-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($user['User_DOB']); ?>" class="w-full mt-1 border-gray-300 rounded-md shadow-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Avatar Path (Raw)</label>
                        <input type="text" name="avatar" value="<?php echo htmlspecialchars($user['Avatar']); ?>" class="w-full mt-1 border-gray-300 rounded-md shadow-sm border p-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700">Caption</label>
                        <input type="text" name="caption" value="<?php echo htmlspecialchars($user['Caption']); ?>" class="w-full mt-1 border-gray-300 rounded-md shadow-sm border p-2">
                    </div>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">System Data</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase">Role</label>
                        <select name="role" class="w-full mt-1 border-gray-300 rounded-md border p-2 bg-white">
                            <option value="0" <?php if($user['Role']==0) echo 'selected'; ?>>Member (0)</option>
                            <option value="1" <?php if($user['Role']==1) echo 'selected'; ?>>Admin (1)</option>
                            <option value="2" <?php if($user['Role']==2) echo 'selected'; ?>>Team Owner (2)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase">Team ID</label>
                        <input type="number" name="team_id" value="<?php echo $user['Team_ID']; ?>" placeholder="0" class="w-full mt-1 border-gray-300 rounded-md border p-2">
                        <p class="text-[10px] text-gray-400 mt-1">Set 0 to remove from team.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase">Total Points</label>
                        <input type="number" name="points" value="<?php echo $user['Point']; ?>" class="w-full mt-1 border-gray-300 rounded-md border p-2">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase">Redeem Points</label>
                        <input type="number" name="redeem_points" value="<?php echo $user['RedeemPoint']; ?>" class="w-full mt-1 border-gray-300 rounded-md border p-2">
                    </div>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">Security</h3>
                <div class="bg-red-50 border border-red-100 p-4 rounded-lg mb-6">
                    <label class="block text-sm font-bold text-red-800">Reset Password</label>
                    <input type="text" name="new_password" placeholder="Enter new password here to reset (Leave empty to keep current)" class="w-full mt-2 border-red-200 rounded-md shadow-sm border p-2 focus:ring-red-500 focus:border-red-500">
                    <p class="text-xs text-red-500 mt-1">Warning: Changing this will immediately affect the user's ability to login.</p>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3">
                    <a href="user_list.php" class="px-6 py-2.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium">Cancel</a>
                    <button type="submit" name="update_user" class="px-8 py-2.5 bg-blue-600 text-white rounded-md font-bold hover:bg-blue-700 shadow-md transition">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</main>

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="w-full py-8 px-8">
        <p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc. All rights reserved. Designed for a greener tomorrow.</p>
    </div>
</footer>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>
</html>