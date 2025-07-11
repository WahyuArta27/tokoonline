<?php
session_start();
require_once '../database/koneksi.php'; // Adjust path as needed

header('Content-Type: application/json');

if (!isset($_SESSION['login']) || !isset($_SESSION['dataUser']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_POST['cart_id']) || !isset($_POST['qty'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$cart_id = filter_var($_POST['cart_id'], FILTER_VALIDATE_INT);
$qty = filter_var($_POST['qty'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$user_id = $_SESSION['dataUser']['user_id'];

if ($cart_id === false || $qty === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Verify the cart belongs to the user
$sql = "SELECT k.qty, p.product_price, p.product_stok
        FROM tb_keranjang k
        INNER JOIN tb_product p ON k.product_id = p.product_id
        WHERE k.keranjang_id = ? AND k.user_id = ? AND k.is_payed = '2'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found or does not belong to user']);
    exit;
}

// Check stock
if ($qty > $item['product_stok']) {
    echo json_encode(['success' => false, 'message' => 'Quantity exceeds available stock']);
    exit;
}

// Update the cart
$sql = "UPDATE tb_keranjang SET qty = ? WHERE keranjang_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $qty, $cart_id);
$updateResult = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$updateResult) {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    exit;
}

// Recalculate subtotal and total
$subtotal = $item['product_price'] * $qty;
$sql = "SELECT SUM(k.qty * p.product_price) AS total
        FROM tb_keranjang k
        INNER JOIN tb_product p ON k.product_id = p.product_id
        WHERE k.user_id = ? AND k.is_payed = '2'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$totalResult = mysqli_stmt_get_result($stmt);
$totalData = mysqli_fetch_assoc($totalResult);
mysqli_stmt_close($stmt);

$total = $totalData['total'];

mysqli_close($conn);

echo json_encode([
    'success' => true,
    'subtotal' => $subtotal,
    'subtotalFormatted' => number_format($subtotal, 0, ',', '.'),
    'total' => $total,
    'totalFormatted' => number_format($total, 0, ',', '.')
]);
?>