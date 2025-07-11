<?php

$conn = mysqli_connect("localhost", "root", "", "db_toko");

function query($query)
{
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function getProdukTransaksiFilter($tgl)
{
    global $conn;

    // Validasi format tanggal
    if (!DateTime::createFromFormat('Y-m-d', $tgl)) {
        throw new Exception("Format tanggal tidak valid: $tgl");
    }

    // Query data transaksi
    $sql = "SELECT * FROM tb_transaksi 
            INNER JOIN tb_user ON tb_transaksi.user_id = tb_user.user_id 
            INNER JOIN tb_bank ON tb_transaksi.bank_id = tb_bank.bank_id 
            INNER JOIN tb_keranjang ON tb_transaksi.keranjang_grup = tb_keranjang.keranjang_id 
            INNER JOIN tb_product ON tb_keranjang.product_id = tb_product.product_id 
            WHERE DATE(tanggal_transaksi) = ?";
    
    // Gunakan prepared statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tgl);

    if (!$stmt->execute()) {
        error_log("Error executing query: " . $stmt->error);
        return [];
    }

    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();
    return $rows;
}


function update($data)
{
    global $conn;

    $id = htmlspecialchars($data["id"]);
    $product_name = htmlspecialchars($data["nama_produk"]);
    $product_price = htmlspecialchars($data["harga_produk"]);
    $product_description = htmlspecialchars($data["desc_produk"]);
    $stock_product = htmlspecialchars($data["stok_produk"]);
    $gambar_lama = htmlspecialchars($data["gambar_lama"]);

    // Cek apakah ada file baru diunggah
    if ($_FILES['gambar']['error'] === 4) {
        $gambar = $gambar_lama;
    } else {
        $gambar = upload();
        if (!$gambar) {
            return 0; // Gagal mengunggah file
        }
    }

    // Query pembaruan data
    $query = "UPDATE tb_product 
              SET product_name = ?, product_desc = ?, product_thumb = ?, product_stok = ?, product_price = ? 
              WHERE product_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssisi", $product_name, $product_description, $gambar, $stock_product, $product_price, $id);

    if (!$stmt->execute()) {
        error_log("Error updating product: " . $stmt->error);
        return 0;
    }

    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    return $affected_rows;
}
// upload func
function upload()
{
    $nama_file = $_FILES['gambar']['name'];
    $ukuran_file = $_FILES['gambar']['size'];
    $error = $_FILES['gambar']['error'];
    $tmp_name = $_FILES['gambar']['tmp_name'];

    // Validasi jika tidak ada file yang diunggah
    if ($error === 4) {
        echo "<script>window.location.href = './?response=imgfail';</script>";
        return false;
    }

    // Validasi ekstensi file
    $ekstensi_gambar_valid = ['jpg', 'jpeg', 'png', 'jfif'];
    $ekstensi_gambar = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
    if (!in_array($ekstensi_gambar, $ekstensi_gambar_valid)) {
        echo "<script>window.location.href = './?response=imgwarning';</script>";
        return false;
    }

    // Validasi ukuran file
    if ($ukuran_file > 1000000) {
        echo "<script>window.location.href = './?response=imgover';</script>";
        return false;
    }

    // Pastikan direktori penyimpanan ada
    $upload_dir = '../../img/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate nama file baru
    $nama_file_baru = uniqid() . '.' . $ekstensi_gambar;

    // Pindahkan file ke direktori tujuan
    if (!move_uploaded_file($tmp_name, $upload_dir . $nama_file_baru)) {
        echo "<script>window.location.href = './?response=imgfail';</script>";
        return false;
    }

    return $nama_file_baru;
}
