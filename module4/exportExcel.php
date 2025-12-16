<?php
require '../database.php';

// 1. set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="redemption_report_' . date('Y-m-d') . '.csv"');

// 2. open output stream
$output = fopen('php://output', 'w');

// 3. write CSV header row
fputcsv($output, ['Record ID', 'User ID', 'User Name', 'Reward Name', 'Quantity', 'Status', 'Date']);

// 4. Fetch data from database
$sql = "SELECT r.RedeemRecord_ID, r.Redeem_By, CONCAT(u.First_Name, ' ', u.Last_Name) as UserName, 
               r.Reward_Name, r.Redeem_Quantity, r.Status, r.Redeem_Date 
        FROM redeemrecord r 
        JOIN user u ON r.Redeem_By = u.User_ID 
        ORDER BY r.Redeem_Date DESC";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// 5. write data rows
foreach ($rows as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>