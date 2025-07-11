<?php
require_once './database/koneksi.php';

function addTransaksi($data) {
    global $conn;
    
    // Log semua data yang diterima
    error_log("=== START TRANSACTION DEBUG ===");
    error_log("Input data: " . print_r($data, true));
    
    // Cek koneksi database
    if ($conn->connect_error) {
        error_log("DB Connection Error: " . $conn->connect_error);
        return false;
    }
    
    try {
        // Cek struktur tabel
        $result = $conn->query("SHOW COLUMNS FROM tb_transaksi");
        if (!$result) {
            error_log("Error querying table structure: " . $conn->error);
            return false;
        }
        
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = [
                'name' => $row['Field'],
                'type' => $row['Type'],
                'null' => $row['Null'],
                'key' => $row['Key'],
                'default' => $row['Default']
            ];
        }
        error_log("Table structure: " . print_r($columns, true));
        
        // Cek apakah user_id valid
        $user_check = $conn->query("SELECT user_id FROM tb_user WHERE user_id = " . $data['user_id']);
        if (!$user_check || $user_check->num_rows === 0) {
            error_log("Invalid user_id: " . $data['user_id']);
            return false;
        }
        
        // Cek apakah bank_id valid
        $bank_check = $conn->query("SELECT bank_id FROM tb_bank WHERE bank_id = " . $data['bank_id']);
        if (!$bank_check || $bank_check->num_rows === 0) {
            error_log("Invalid bank_id: " . $data['bank_id']);
            return false;
        }
        
        // Cek apakah user memiliki item di keranjang
        $cart_check = $conn->query("SELECT COUNT(*) as count FROM tb_keranjang WHERE user_id = " . $data['user_id'] . " AND is_payed = '2'");
        if (!$cart_check) {
            error_log("Error checking cart: " . $conn->error);
            return false;
        }
        
        $cart_count = $cart_check->fetch_assoc()['count'];
        if ($cart_count == 0) {
            error_log("User has no items in cart");
            return false;
        }
        error_log("User has $cart_count items in cart");
        
        // Mulai transaksi
        $conn->begin_transaction();
        error_log("Transaction started");
        
        // Buat query INSERT minimal (hanya kolom yang pasti ada)
        $sql = "INSERT INTO tb_transaksi (user_id, bank_id, status_pembayaran) VALUES (?, ?, 0)";
        error_log("SQL Query: $sql");
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $user_id = (int)$data['user_id'];
        $bank_id = (int)$data['bank_id'];
        error_log("Binding parameters: user_id=$user_id, bank_id=$bank_id");
        
        $stmt->bind_param("ii", $user_id, $bank_id);
        
        // Execute query
        $result = $stmt->execute();
        if (!$result) {
            error_log("Execute failed: " . $stmt->error);
            $conn->rollback();
            error_log("Transaction rolled back");
            return false;
        }
        
        $transaksi_id = $stmt->insert_id;
        error_log("Transaction inserted with ID: $transaksi_id");
        
        $stmt->close();
        
        // Update keranjang items to mark them as part of this transaction
        $update_cart = $conn->prepare("UPDATE tb_keranjang SET is_payed = '1', transaksi_id = ? WHERE user_id = ? AND is_payed = '2'");
        if (!$update_cart) {
            error_log("Prepare update cart failed: " . $conn->error);
            $conn->rollback();
            return false;
        }
        
        $update_cart->bind_param("ii", $transaksi_id, $user_id);
        $cart_result = $update_cart->execute();
        
        if (!$cart_result) {
            error_log("Update cart failed: " . $update_cart->error);
            $conn->rollback();
            return false;
        }
        
        $affected_rows = $update_cart->affected_rows;
        error_log("Updated $affected_rows cart items to transaction ID: $transaksi_id");
        $update_cart->close();
        
        // Commit transaksi
        $conn->commit();
        error_log("Transaction committed");
        error_log("=== END TRANSACTION DEBUG ===");
        
        return $transaksi_id;
        
    } catch (Exception $e) {
        // Rollback pada error
        if ($conn->connect_error === null) {
            $conn->rollback();
            error_log("Transaction rolled back due to exception");
        }
        error_log("Exception: " . $e->getMessage());
        error_log("=== END TRANSACTION DEBUG ===");
        return false;
    }
}

function getTransaksiByUserId($id) {
    global $conn;
    
    try {
        // Improved query to get transaction details with product information
        $sql = "SELECT t.*, k.qty, p.product_name, p.product_price 
                FROM tb_transaksi t
                JOIN tb_keranjang k ON t.transaksi_id = k.transaksi_id
                JOIN tb_product p ON k.product_id = p.product_id
                WHERE t.user_id = ?
                ORDER BY t.transaksi_id DESC";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        $stmt->close();
        return $transactions;
        
    } catch (Exception $e) {
        error_log("Error fetching transactions: " . $e->getMessage());
        return [];
    }
}

function konfirmPayment($data) {
    global $conn;
    
    // Basic validation
    if (empty($data['transaksi_id'])) {
        error_log("Empty transaction ID in konfirmPayment");
        return false;
    }
    
    try {
        // Log untuk debugging
        error_log("Confirming payment for transaction ID: " . $data['transaksi_id']);
        
        // Prepare payment data
        $transaksi_id = (int)$data['transaksi_id'];
        $payment_proof = $data['payment_proof'] ?? null;
        $payment_notes = $data['payment_notes'] ?? null;
        
        // Update transaction with payment proof
        $sql = "UPDATE tb_transaksi SET 
                status_pembayaran = 1, 
                bukti_pembayaran = ?, 
                catatan_pembayaran = ?, 
                tanggal_pembayaran = NOW() 
                WHERE transaksi_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $payment_proof, $payment_notes, $transaksi_id);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        error_log("Payment confirmation result: " . ($success ? "Success" : "Failed") . ", Affected rows: $affected");
        
        return $success && $affected > 0;
        
    } catch (Exception $e) {
        error_log("Error confirming payment: " . $e->getMessage());
        return false;
    }
}