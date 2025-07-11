<?php
session_start();
require_once '../database/koneksi.php'; // Adjust path as needed
require_once '../controller/cartController.php'; // Tambahkan ini untuk menggunakan fungsi dari cartController

header('Content-Type: application/json');

if (!isset($_SESSION['login']) || !isset($_SESSION['dataUser']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing cart ID']);
    exit;
}

$cart_id = filter_var($_POST['cart_id'], FILTER_VALIDATE_INT);
$user_id = $_SESSION['dataUser']['user_id'];

if ($cart_id === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
    exit;
}

// Verify the cart belongs to the user
$sql = "SELECT keranjang_id FROM tb_keranjang WHERE keranjang_id = ? AND user_id = ? AND is_payed = '2'";
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

try {
    // Gunakan fungsi deleteCart dari cartController jika tersedia
    if (function_exists('deleteCart')) {
        $deleteResult = deleteCart($cart_id);
    } else {
        // Fallback ke kode asli jika fungsi tidak tersedia
        $sql = "DELETE FROM tb_keranjang WHERE keranjang_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cart_id);
        $deleteResult = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    if (!$deleteResult) {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart']);
        exit;
    }

    // Gunakan getCartTotal dari cartController jika tersedia
    if (function_exists('getCartTotal')) {
        $total = getCartTotal($user_id);
    } else {
        // Fallback ke kode asli jika fungsi tidak tersedia
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
        
        $total = $totalData['total'] ? $totalData['total'] : 0;
    }

    // Check if cart is empty
    if (function_exists('getMyCart')) {
        $cartItems = getMyCart($user_id);
        $cartEmpty = empty($cartItems);
    } else {
        // Fallback ke kode asli jika fungsi tidak tersedia
        $sql = "SELECT COUNT(*) AS count FROM tb_keranjang WHERE user_id = ? AND is_payed = '2'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $countResult = mysqli_stmt_get_result($stmt);
        $countData = mysqli_fetch_assoc($countResult);
        mysqli_stmt_close($stmt);
        
        $cartEmpty = ((int)$countData['count'] === 0);
    }

    // Log hasil operasi untuk debugging
    error_log("Remove cart item success - Cart ID: $cart_id, User ID: $user_id, Total: $total, Empty: " . ($cartEmpty ? 'Yes' : 'No'));

    echo json_encode([
        'success' => true,
        'total' => $total,
        'totalFormatted' => number_format($total, 0, ',', '.'),
        'cartEmpty' => $cartEmpty,
        'message' => 'Item removed successfully'
    ]);

} catch (Exception $e) {
    error_log("Error removing cart item: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

if (isset($conn)) {
    mysqli_close($conn);
}
?>