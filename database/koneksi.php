<?php
$conn = mysqli_connect('localhost', 'root', '', 'db_toko');

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed: " . mysqli_connect_error());
}
?>