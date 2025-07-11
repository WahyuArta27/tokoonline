<?php
session_start();
require './database/koneksi.php';

header('Content-Type: application/json');

// Validasi session
if (!isset($_SESSION['login']) || !isset($_SESSION['dataUser']['user_id'])) {
    echo json_encode([
        'statusCode' => 401,
        'success' => false,
        'message' => 'Unauthorized: Please login first'
    ]);
    exit;
}

// Validasi input
if (!isset($_POST['product_id']) || !isset($_POST['qty'])) {
    echo json_encode([
        'statusCode' => 400,
        'success' => false,
        'message' => 'Bad Request: Missing required parameters'
    ]);
    exit;
}

// Bersihkan dan validasi input
$product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
$qty = filter_var($_POST['qty'], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);
$user_id = $_SESSION['dataUser']['user_id'];

if ($product_id === false || $qty === false) {
    echo json_encode([
        'statusCode' => 400,
        'success' => false,
        'message' => 'Invalid input: Product ID or quantity is invalid'
    ]);
    exit;
}

// Mulai transaction untuk atomic operation
mysqli_begin_transaction($conn);

try {
    // 1. Cek stok produk dengan row locking
    $sql = "SELECT product_id, product_name, product_stok FROM tb_product WHERE product_id = ? FOR UPDATE";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare product check statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$product) {
        throw new Exception('Product not found');
    }

    if ($product['product_stok'] < $qty) {
        throw new Exception('Insufficient stock for product: ' . $product['product_name']);
    }

    // 2. Cek apakah produk sudah ada di keranjang
    $sql = "SELECT keranjang_id, qty FROM tb_keranjang 
            WHERE user_id = ? AND product_id = ? AND is_payed = '2' FOR UPDATE";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare cart check statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existingItem = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($existingItem) {
        // Update quantity jika item sudah ada
        $new_qty = $existingItem['qty'] + $qty;
        
        if ($new_qty > $product['product_stok']) {
            throw new Exception('Total quantity exceeds available stock for product: ' . $product['product_name']);
        }

        $sql = "UPDATE tb_keranjang SET qty = ? WHERE keranjang_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $new_qty, $existingItem['keranjang_id']);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update cart item: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
    } else {
        // Insert item baru ke keranjang
        $sql = "INSERT INTO tb_keranjang (user_id, product_id, qty, is_payed) VALUES (?, ?, ?, '2')";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $product_id, $qty);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to add item to cart: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
    }

    // 3. Update stok produk (optional, tergantung business logic)
    // $sql = "UPDATE tb_product SET product_stok = product_stok - ? WHERE product_id = ?";
    // $stmt = mysqli_prepare($conn, $sql);
    // mysqli_stmt_bind_param($stmt, "ii", $qty, $product_id);
    // mysqli_stmt_execute($stmt);
    // mysqli_stmt_close($stmt);

    // Commit transaction jika semua berhasil
    mysqli_commit($conn);

    // 4. Hitung total item di keranjang untuk response
    $sql = "SELECT COUNT(*) as cart_count FROM tb_keranjang WHERE user_id = ? AND is_payed = '2'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cartData = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    echo json_encode([
        'statusCode' => 200,
        'success' => true,
        'message' => 'Product successfully added to cart',
        'cartCount' => $cartData['cart_count'],
        'productName' => $product['product_name']
    ]);

} catch (Exception $e) {
    // Rollback transaction jika ada error
    mysqli_rollback($conn);
    
    error_log("Cart Error: " . $e->getMessage());
    
    echo json_encode([
        'statusCode' => 500,
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    mysqli_close($conn);
}
?>