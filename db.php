<?php
// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "car_rental_db";

// First connect without database to create it if needed
$temp_conn = mysqli_connect($db_host, $db_user, $db_pass);
if (!$temp_conn) {
    die("Initial connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$create_db = "CREATE DATABASE IF NOT EXISTS $db_name";
if (!mysqli_query($temp_conn, $create_db)) {
    die("Error creating database: " . mysqli_error($temp_conn));
}
mysqli_close($temp_conn);

// Now connect properly with database selected
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Rest of your original code...
// [The tables creation code and everything else remains the same]
