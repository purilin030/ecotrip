<?php
include '../database.php';

$category_options = "";
$sql_cat = "SELECT CategoryID, CategoryName FROM category";
$result_cat = $con->query($sql_cat);

if ($result_cat->num_rows > 0) {
    while ($row = $result_cat->fetch_assoc()) {
        // This creates the <option> tag dynamically using your DB data
        $category_options .= "<option value='" . $row["CategoryID"] . "'>" . $row["CategoryName"] . "</option>";
    }
}

?>