<?php
// Hanya gunakan koneksi dari file berikut (pastikan path-nya benar)
require_once __DIR__ . '/../database/koneksi.php';

function addTransaksi($data) {
    global $conn;
    
    // Log semua data yang diterima
    error_log("=== START TRANSACTION DEBUG ===");
    error_log("Input data: " . print_r($data, true));
    
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
                'name'    => $row['Field'],
                'type'    => $row['Type'],
                'null'    => $row['Null'],
                'key'     => $row['Key'],
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
        
        // Sesuaikan query INSERT dengan kolom yang ada di tabel
        $transaksi_alamat = isset($data['transaksi_alamat']) ? $data['transaksi_alamat'] : '';
        $keranjang_grup = isset($data['keranjang_grup']) ? $data['keranjang_grup'] : null;
        
        // Cek apakah kolom transaksi_alamat dan keranjang_grup ada di tabel
        $has_alamat = false;
        $has_keranjang_grup = false;
        
        foreach ($columns as $col) {
            if ($col['name'] === 'transaksi_alamat') $has_alamat = true;
            if ($col['name'] === 'keranjang_grup') $has_keranjang_grup = true;
        }
        
        // Buat query INSERT sesuai dengan struktur tabel dan tambahkan tanggal_transaksi
        if ($has_alamat && $has_keranjang_grup) {
            $sql = "INSERT INTO tb_transaksi (user_id, bank_id, transaksi_alamat, keranjang_grup, tanggal_transaksi, status_pembayaran) 
                    VALUES (?, ?, ?, ?, NOW(), 0)";
            error_log("SQL Query with alamat and grup: $sql");
        } elseif ($has_alamat) {
            $sql = "INSERT INTO tb_transaksi (user_id, bank_id, transaksi_alamat, tanggal_transaksi, status_pembayaran) 
                    VALUES (?, ?, ?, NOW(), 0)";
            error_log("SQL Query with alamat: $sql");
        } elseif ($has_keranjang_grup) {
            $sql = "INSERT INTO tb_transaksi (user_id, bank_id, keranjang_grup, tanggal_transaksi, status_pembayaran) 
                    VALUES (?, ?, ?, NOW(), 0)";
            error_log("SQL Query with grup: $sql");
        } else {
            $sql = "INSERT INTO tb_transaksi (user_id, bank_id, tanggal_transaksi, status_pembayaran) 
                    VALUES (?, ?, NOW(), 0)";
            error_log("SQL Query basic: $sql");
        }
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            $conn->rollback();
            return false;
        }
        
        $user_id = (int)$data['user_id'];
        $bank_id = (int)$data['bank_id'];
        error_log("Binding parameters: user_id=$user_id, bank_id=$bank_id");
        
        // Bind parameters sesuai dengan query
        if ($has_alamat && $has_keranjang_grup) {
            error_log("Binding 4 parameters");
            $stmt->bind_param("iisi", $user_id, $bank_id, $transaksi_alamat, $keranjang_grup);
        } elseif ($has_alamat) {
            error_log("Binding 3 parameters with alamat");
            $stmt->bind_param("iis", $user_id, $bank_id, $transaksi_alamat);
        } elseif ($has_keranjang_grup) {
            error_log("Binding 3 parameters with grup");
            $stmt->bind_param("iii", $user_id, $bank_id, $keranjang_grup);
        } else {
            error_log("Binding 2 parameters");
            $stmt->bind_param("ii", $user_id, $bank_id);
        }
        
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $conn->rollback();
            error_log("Transaction rolled back");
            return false;
        }
        
        $transaksi_id = $stmt->insert_id;
        error_log("Transaction inserted with ID: $transaksi_id");
        
        $stmt->close();
        
        // Update keranjang items
        $update_cart = $conn->prepare("UPDATE tb_keranjang SET is_payed = '1', transaksi_id = ? WHERE user_id = ? AND is_payed = '2'");
        if (!$update_cart) {
            error_log("Prepare update cart failed: " . $conn->error);
            $conn->rollback();
            return false;
        }
        
        $update_cart->bind_param("ii", $transaksi_id, $user_id);
        if (!$update_cart->execute()) {
            error_log("Update cart failed: " . $update_cart->error);
            $conn->rollback();
            return false;
        }
        
        error_log("Updated " . $update_cart->affected_rows . " cart items to transaction ID: $transaksi_id");
        $update_cart->close();
        
        // Commit transaksi
        $conn->commit();
        error_log("Transaction committed");
        error_log("=== END TRANSACTION DEBUG ===");
        
        return $transaksi_id;
        
    } catch (Exception $e) {
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

function getTransaksiFilter($tgl_awal, $tgl_akhir = null) {
    global $conn;

    try {
        if (!DateTime::createFromFormat('Y-m-d', $tgl_awal)) {
            throw new Exception("Format tanggal awal tidak valid: $tgl_awal");
        }
        if ($tgl_akhir && !DateTime::createFromFormat('Y-m-d', $tgl_akhir)) {
            throw new Exception("Format tanggal akhir tidak valid: $tgl_akhir");
        }

        $sql = "SELECT 
                    t.transaksi_id,
                    t.tanggal_transaksi,
                    t.status_pembayaran,
                    k.qty,
                    p.product_name,
                    p.product_price,
                    u.fullname,
                    b.nama_bank,
                    b.no_bank
                FROM tb_transaksi t
                INNER JOIN tb_keranjang k ON t.keranjang_grup = k.keranjang_id
                INNER JOIN tb_product p ON k.product_id = p.product_id
                INNER JOIN tb_user u ON t.user_id = u.user_id
                INNER JOIN tb_bank b ON t.bank_id = b.bank_id
                WHERE DATE(t.tanggal_transaksi) >= ?";

        $params = [$tgl_awal];
        if ($tgl_akhir) {
            $sql .= " AND DATE(t.tanggal_transaksi) <= ?";
            $params[] = $tgl_akhir;
        }
        $sql .= " ORDER BY t.tanggal_transaksi DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception("Execute query failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }

        $stmt->close();
        return $transactions;

    } catch (Exception $e) {
        error_log("Error in getTransaksiFilter: " . $e->getMessage());
        return [];
    }
}

function getTotalPendapatan($tgl_awal, $tgl_akhir = null) {
    global $conn;
    
    try {
        $sql = "SELECT SUM(p.product_price * k.qty) as total
                FROM tb_transaksi t
                JOIN tb_keranjang k ON t.transaksi_id = k.transaksi_id
                JOIN tb_product p ON k.product_id = p.product_id
                WHERE DATE(t.tanggal_transaksi) >= ?";
        
        $params = [$tgl_awal];
        
        if ($tgl_akhir) {
            $sql .= " AND DATE(t.tanggal_transaksi) <= ?";
            $params[] = $tgl_akhir;
        }
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan statement");
        }
        
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        mysqli_stmt_close($stmt);
        return $row['total'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Error in getTotalPendapatan: " . $e->getMessage());
        return 0;
    }
}

function konfirmPayment($data) {
    global $conn;
    
    if (empty($data['transaksi_id'])) {
        error_log("Empty transaction ID in konfirmPayment");
        return false;
    }
    
    try {
        error_log("Confirming payment for transaction ID: " . $data['transaksi_id']);
        
        $transaksi_id = (int)$data['transaksi_id'];
        $payment_proof = $data['payment_proof'] ?? null;
        $payment_notes = $data['payment_notes'] ?? null;
        
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