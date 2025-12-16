<?php
// === module3/admin_process_approval.php ===
// connect database
require_once __DIR__ . '/../database.php';

// 开启 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// check login or not
if (!isset($_SESSION['user_id'])) {
    die("Error: Access denied. Please log in first.");
}
$admin_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sub_id = intval($_POST['submission_id']);
    $action = $_POST['action']; // 'approve' 或 'deny'
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    $action_date = date("Y-m-d");

    // Retrieve the status, user information, and challenge points from the current database
    $check_sql = "SELECT s.Status, s.User_ID, s.Team_ID, c.Points 
                  FROM submissions s 
                  JOIN challenge c ON s.Challenge_ID = c.Challenge_ID 
                  WHERE s.Submission_ID = ?";
    $check_stmt = $con->prepare($check_sql);
    $check_stmt->bind_param("i", $sub_id);
    $check_stmt->execute();
    $current_data = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if (!$current_data) {
        die("Error: Submission not found.");
    }

    $current_status = strtolower($current_data['Status']);
    $target_user_id = $current_data['User_ID'];
    $points = $current_data['Points'];
    $team_id = (!empty($current_data['Team_ID']) && $current_data['Team_ID'] != 0) ? $current_data['Team_ID'] : null;

    $status_text = "";
    
    // ======================================================
    // Logical branch processing: Preventing duplicate scoring and handling deductions
    // ======================================================

    if ($action == 'approve') {
        $status_text = "Approved";

        // Scenario A: Previously approved, now merely updating the remarks.
        if ($current_status === 'approved') {
            // No points operations; only updating Notes and QR codes.
        } 
        // Scenario B: Previously Pending or Denied, now changed to Approved -> Points awarded
        else {
            // renew user's point
            $update_user_sql = "UPDATE user SET Point = Point + ?, RedeemPoint = RedeemPoint + ? WHERE User_ID = ?";
            $update_user_stmt = $con->prepare($update_user_sql);
            $update_user_stmt->bind_param("iii", $points, $points, $target_user_id);
            $update_user_stmt->execute();
            $update_user_stmt->close();

            // 2. insert PointsLedger record
            $ledger_sql = "INSERT INTO pointsledger (Points_Earned, Earned_Date, User_ID, Submission_ID, Team_ID) 
                           VALUES (?, ?, ?, ?, ?)";
            $ledger_stmt = $con->prepare($ledger_sql);
            $ledger_stmt->bind_param("isiii", $points, $action_date, $target_user_id, $sub_id, $team_id);
            $ledger_stmt->execute();
            $ledger_stmt->close();
        }

        // --- QR Code generation
        $folder_path = "../qr_code/";
        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0777, true);
        }

        $file_name = "qr_" . $sub_id . "_" . time() . ".png";
        $local_file_path = $folder_path . $file_name;
        $db_qr_path = "../qr_code/" . $file_name;

        $qr_content = "Submission-" . $sub_id . "-Verified";
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_content);
        
        $image_data = file_get_contents($api_url);

        if ($image_data) {
            file_put_contents($local_file_path, $image_data);
            // renew Submission
            $sql = "UPDATE submissions SET Status = 'Approved', Verification_note = ?, QR_Code = ? WHERE Submission_ID = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssi", $note, $db_qr_path, $sub_id);
            $stmt->execute();
        } else {
            // Even if the QR code fails, the status must be updated.
             $sql = "UPDATE submissions SET Status = 'Approved', Verification_note = ? WHERE Submission_ID = ?";
             $stmt = $con->prepare($sql);
             $stmt->bind_param("si", $note, $sub_id);
             $stmt->execute();
        }

    } elseif ($action == 'deny') {
        $status_text = "Denied";

        // Scenario C: Previously Approved, now changed to Denied -> Points must be deducted (Revoke Points)
        if ($current_status === 'approved') {
          
            // reduce user's points
            $deduct_sql = "UPDATE user SET Point = Point - ?, RedeemPoint = RedeemPoint - ? WHERE User_ID = ?";
            $deduct_stmt = $con->prepare($deduct_sql);
            $deduct_stmt->bind_param("iii", $points, $points, $target_user_id);
            $deduct_stmt->execute();
            $deduct_stmt->close();

            // 2. execute Ledger that add point before 
            $del_ledger_sql = "DELETE FROM pointsledger WHERE Submission_ID = ? AND User_ID = ?";
            $del_ledger_stmt = $con->prepare($del_ledger_sql);
            $del_ledger_stmt->bind_param("ii", $sub_id, $target_user_id);
            $del_ledger_stmt->execute();
            $del_ledger_stmt->close();
        }
        
        // renew Submission's status
        $sql = "UPDATE submissions SET Status = 'Denied', Verification_note = ? WHERE Submission_ID = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("si", $note, $sub_id);
        $stmt->execute();
    }

    // --- record to Moderation ---
    if (!empty($status_text)) {
        $mod_sql = "INSERT INTO moderation (Submission_ID, User_ID, Action, Action_date) VALUES (?, ?, ?, ?)";
        $mod_stmt = $con->prepare($mod_sql);
        $mod_stmt->bind_param("iiss", $sub_id, $admin_id, $status_text, $action_date);
        $mod_stmt->execute();

        // jump
        header("Location: action_completed.php?id=" . $sub_id . "&status=" . strtolower($status_text));
        exit(); 
    }
}
?>