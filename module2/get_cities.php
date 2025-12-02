<?php
// get_cities.php
include '../database.php';

$city_options = "";
$sql_city = "SELECT CityID, CityName FROM city";
$result_city = $con->query($sql_city);

if ($result_city->num_rows > 0) {
    while ($row = $result_city->fetch_assoc()) {
        // This creates the <option> tag dynamically using your DB data
        $city_options .= "<option value='" . $row["CityID"] . "'>" . $row["CityName"] . "</option>";
    }
}
?>