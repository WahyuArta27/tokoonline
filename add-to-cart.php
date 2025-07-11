<?php
session_start();
require './database/koneksi.php'; // Pastikan path benar

header('Content-Type: application/json');

// Validasi session
if (!isset($_SESSION['login']) || !isset($_SESSION['dataUser']['user_id'])) {
    echo json_encode(['statusCode' => 201, 'message' => 'Sesi tidak valid. Silakan login kembali.']);
    exit;
}

// Validasi input
$required = ['product_id', 'qty'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['statusCode' => 201, 'message' => 'Parameter tidak lengkap.']);
        exit;
    }
}

// Bersihkan dan validasi input
$product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
$qty = filter_var($_POST['qty'], FILTER_VALIDATE_INT);
$user_id = $_SESSION['dataUser']['user_id'];

if ($product_id === false || $qty === false || $qty <= 0) {
    echo json_encode(['statusCode' => 201, 'message' => 'Input tidak valid.']);
    exit;
}

// Cek stok produk
$sql = "SELECT product_stok FROM tb_product WHERE product_id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt === false) {
    echo json_encode(['statusCode' => 500, 'message' => 'Gagal menyiapkan statement.']);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    echo json_encode(['statusCode' => 500, 'message' => 'Gagal mengeksekusi query stok.']);
    exit;
}

$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$product) {
    echo json_encode(['statusCode' => 201, 'message' => 'Produk tidak ditemukan.']);
    exit;
}

if ($product['product_stok'] < $qty) {
    echo json_encode(['statusCode' => 201, 'message' => 'Stok tidak mencukupi.']);
    exit;
}

// Cek apakah produk sudah ada di keranjang
$sql = "SELECT keranjang_id, qty FROM tb_keranjang WHERE user_id = ? AND product_id = ? AND is_payed = '2'";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    echo json_encode(['statusCode' => 500, 'message' => 'Gagal menyiapkan statement cek keranjang.']);
    exit;
}
mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$existingItem = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($existingItem) {
    // Jika sudah ada, update quantity
    $new_qty = $existingItem['qty'] + $qty;

    // Pastikan stok masih mencukupi dengan new_qty (opsional: tambahkan pengecekan lagi)
    if ($new_qty > $product['product_stok']) {
        echo json_encode(['statusCode' => 201, 'message' => 'Stok tidak mencukupi untuk penambahan quantity.']);
        exit;
    }

    $sql = "UPDATE tb_keranjang SET qty = ? WHERE keranjang_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        echo json_encode(['statusCode' => 500, 'message' => 'Gagal menyiapkan statement update keranjang.']);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "ii", $new_qty, $existingItem['keranjang_id']);
    $update_result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$update_result) {
        echo json_encode(['statusCode' => 500, 'message' => 'Gagal memperbarui keranjang. ' . mysqli_error($conn)]);
        exit;
    }
} else {
    // Jika belum ada, insert ke keranjang
    $sql = "INSERT INTO tb_keranjang (user_id, product_id, qty) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        echo json_encode(['statusCode' => 500, 'message' => 'Gagal menyiapkan statement insert.']);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $product_id, $qty);
    $insert_result = mysqli_stmt_execute($stmt);
    if ($insert_result === false) {
        echo json_encode(['statusCode' => 500, 'message' => 'Gagal menambahkan ke keranjang. ' . mysqli_error($conn)]);
        mysqli_stmt_close($stmt);
        exit;
    }
    mysqli_stmt_close($stmt);
}


echo json_encode([
    'statusCode' => 200,
    'message' => 'Produk berhasil ditambahkan ke keranjang.'
]);

mysqli_close($conn);
?>