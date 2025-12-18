<?php
// database.php 
 
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ecotrip";
 
// --- 1. MySQLi Connect ---
$con = mysqli_connect($host, $user, $pass, $dbname);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
 
// --- 2. PDO Connect (For Module 4 ) ---

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}
?>