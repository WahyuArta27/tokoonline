<?php

require './database/koneksi.php';

function getMyCart($user_id)
{
    global $conn;

    // Validate user_id
    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    if (!$user_id) {
        return [];
    }

    // Use prepared statement
    $sql = "SELECT k.*, p.* 
              FROM tb_keranjang k
              INNER JOIN tb_product p ON k.product_id = p.product_id 
              WHERE k.user_id = ? AND k.is_payed = '2'"; 
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

function getCartItemCount($user_id)
{
    global $conn;

    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    if (!$user_id) {
        return 0;
    }

    $sql = "SELECT COUNT(*) as count 
              FROM tb_keranjang 
              WHERE user_id = ? AND is_payed = '2'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = mysqli_fetch_assoc($result)['count'];
    mysqli_stmt_close($stmt);

    return $count;
}

function updateCart($data)
{
    global $conn;

    // Validate inputs
    $cart_id = filter_var($data['cart_id'], FILTER_VALIDATE_INT);
    $qty = filter_var($data['qty'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);

    if (!$cart_id || !$qty) {
        return false;
    }

    // Check if item exists and get product info
    $sql = "SELECT k.*, p.product_stok 
              FROM tb_keranjang k
              INNER JOIN tb_product p ON k.product_id = p.product_id
              WHERE k.keranjang_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $cart_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$item) {
        return false;
    }

    // Check stock availability
    if ($qty > $item['product_stok']) {
        return false;
    }

    // Update quantity
    $sql = "UPDATE tb_keranjang SET qty = ? WHERE keranjang_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $qty, $cart_id);
    $success = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    return $affected > 0;
}

function deleteCart($id)
{
    global $conn;

    $id = filter_var($id, FILTER_VALIDATE_INT);
    if (!$id) {
        return false;
    }

    // Use transaction to ensure data integrity
    mysqli_begin_transaction($conn);

    try {
        // First get the cart item to verify it exists
        $sql = "SELECT * FROM tb_keranjang WHERE keranjang_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $item = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$item) {
            mysqli_rollback($conn);
            return false;
        }

        // Delete the item
        $sql = "DELETE FROM tb_keranjang WHERE keranjang_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        return $affected > 0;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error deleting cart item: " . $e->getMessage());
        return false;
    }
}

function clearUserCart($user_id)
{
    global $conn;

    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    if (!$user_id) {
        return false;
    }

    try {
        $sql = "DELETE FROM tb_keranjang WHERE user_id = ? AND is_payed = '2'";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("Prepare failed: " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        error_log("Clear user cart: $affected rows affected");
        return $affected > 0;

    } catch (Exception $e) {
        error_log("Exception in clearUserCart: " . $e->getMessage());
        return false;
    }
}

// New function to get cart total price
function getCartTotal($user_id)
{
    global $conn;

    error_log("=== DEBUG: getCartTotal ===");
    error_log("User ID: $user_id");

    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    if (!$user_id) {
        error_log("Invalid user_id: $user_id");
        return 0;
    }

    $sql = "SELECT SUM(k.qty * p.product_price) as total
              FROM tb_keranjang k
              INNER JOIN tb_product p ON k.product_id = p.product_id
              WHERE k.user_id = ? AND k.is_payed = '2'";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total = mysqli_fetch_assoc($result)['total'];
    mysqli_stmt_close($stmt);

    error_log("Cart total: $total");
    return $total ? $total : 0;
}

// === Fungsi baru untuk mengambil data transaksi dari tb_transaksi ===
function getUserTransactions($user_id)
{
    global $conn;

    // Validate user_id
    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    if (!$user_id) {
        return [];
    }

    $sql = "SELECT * FROM tb_transaksi WHERE user_id = ? ORDER BY transaksi_id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed (getUserTransactions): " . mysqli_error($conn));
        return [];
    }
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $transactions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $transactions;
}

// Tambahkan fungsi ini di cartController.php
function addToCart($user_id, $product_id, $qty) {
    global $conn;

    // Validasi input
    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    $product_id = filter_var($product_id, FILTER_VALIDATE_INT);
    $qty = filter_var($qty, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if (!$user_id || !$product_id || !$qty) {
        return ['success' => false, 'message' => 'Invalid input'];
    }

    // Cek stok produk
    $product = getProductById($product_id);
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }

    if ($qty > $product['product_stok']) {
        return ['success' => false, 'message' => 'Quantity exceeds available stock'];
    }

    // Cek apakah produk sudah ada di keranjang
    $existingItem = getCartItemByProduct($user_id, $product_id);
    
    if ($existingItem) {
        // Update quantity jika item sudah ada
        $newQty = $existingItem['qty'] + $qty;
        if ($newQty > $product['product_stok']) {
            return ['success' => false, 'message' => 'Total quantity exceeds available stock'];
        }
        
        $updateResult = updateCartItemQuantity($existingItem['keranjang_id'], $newQty);
        return $updateResult;
    } else {
        // Tambahkan item baru ke keranjang
        $insertResult = insertCartItem($user_id, $product_id, $qty);
        return $insertResult;
    }
}

// Fungsi pendukung baru
function getProductById($product_id) {
    global $conn;
    $sql = "SELECT * FROM tb_product WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getCartItemByProduct($user_id, $product_id) {
    global $conn;
    $sql = "SELECT * FROM tb_keranjang WHERE user_id = ? AND product_id = ? AND is_payed = '2'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function updateCartItemQuantity($cart_id, $newQty) {
    global $conn;
    $sql = "UPDATE tb_keranjang SET qty = ? WHERE keranjang_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $newQty, $cart_id);
    $success = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    return [
        'success' => $success && $affected > 0,
        'message' => $success ? 'Cart updated' : 'Failed to update cart'
    ];
}

function insertCartItem($user_id, $product_id, $qty) {
    global $conn;
    $sql = "INSERT INTO tb_keranjang (user_id, product_id, qty, is_payed) VALUES (?, ?, ?, '2')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $product_id, $qty);
    $success = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    return [
        'success' => $success && $affected > 0,
        'message' => $success ? 'Item added to cart' : 'Failed to add to cart'
    ];
}

?>