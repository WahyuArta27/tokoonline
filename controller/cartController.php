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
              WHERE k.user_id = ? AND k.is_payed = '2'"; // Perbaikan: Menghilangkan LIMIT 1, karena ingin menampilkan semua item di cart
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
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $affected > 0;
    } catch (Exception $e) {
        error_log("Error clearing user cart: " . $e->getMessage());
        return false;
    }
}

// New function to get cart total price
function getCartTotal($user_id)
{
    global $conn;

    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    if (!$user_id) {
        return 0;
    }

    $sql = "SELECT SUM(k.qty * p.product_price) as total
              FROM tb_keranjang k
              INNER JOIN tb_product p ON k.product_id = p.product_id
              WHERE k.user_id = ? AND k.is_payed = '2'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total = mysqli_fetch_assoc($result)['total'];
    mysqli_stmt_close($stmt);

    return $total ? $total : 0;
}

?>