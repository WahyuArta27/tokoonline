<?php
$host = "localhost";
$username = "root"; 
$password = "";
$database = "nama_database_toko";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>