<?php
$conn = mysqli_connect("localhost", "root", "", "db_toko");

function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function uploadImage() {
    $targetDir = "../../img/"; // Changed to match the original path in produk_function.php
    
    // Buat folder jika belum ada
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["gambar"]["name"]);
    $targetFile = $targetDir . $fileName;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Cek apakah file benar-benar gambar
    $check = getimagesize($_FILES["gambar"]["tmp_name"]);
    if ($check === false) {
        return ["status" => false, "message" => "File yang diupload bukan gambar"];
    }

    // Cek ukuran file (maksimal 1MB to match original)
    if ($_FILES["gambar"]["size"] > 1000000) {
        return ["status" => false, "message" => "Ukuran gambar terlalu besar (maks 1MB)"];
    }

    // Format file yang diizinkan
    $allowedFormats = ["jpg", "jpeg", "png", "jfif"]; // Added jfif to match original
    if (!in_array($imageFileType, $allowedFormats)) {
        return ["status" => false, "message" => "Hanya format JPG, JPEG, PNG & JFIF yang diizinkan"];
    }

    // Generate nama file unik
    $newFileName = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $newFileName;

    // Coba upload file
    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFile)) {
        return ["status" => true, "filename" => $newFileName];
    } else {
        return ["status" => false, "message" => "Gagal mengupload gambar"];
    }
}

function tambah($data) {
    global $conn;
    
    $upload = uploadImage();
    if (!$upload['status']) {
        return ["status" => false, "message" => $upload['message']];
    }

    $product_name = mysqli_real_escape_string($conn, $data["nama_produk"]);
    $product_price = mysqli_real_escape_string($conn, $data["harga_produk"]);
    $product_desc = mysqli_real_escape_string($conn, $data["desc_produk"]);
    $product_stock = mysqli_real_escape_string($conn, $data["stok_produk"]);
    $product_thumb = $upload['filename']; // Changed to product_thumb

    $query = "INSERT INTO tb_product (product_name, product_desc, product_thumb, product_stok, product_price) 
              VALUES ('$product_name', '$product_desc', '$product_thumb', '$product_stock', '$product_price')";

    if (mysqli_query($conn, $query)) {
        return ["status" => true, "message" => "Produk berhasil ditambahkan"];
    } else {
        // Hapus gambar jika query gagal
        unlink("../../img/" . $product_thumb);
        return ["status" => false, "message" => "Gagal menambahkan produk: " . mysqli_error($conn)];
    }
}

function updateProduk($data) {
    global $conn;
    
    $id = mysqli_real_escape_string($conn, $data["id"]);
    $product_name = mysqli_real_escape_string($conn, $data["nama_produk"]);
    $product_price = mysqli_real_escape_string($conn, $data["harga_produk"]);
    $product_desc = mysqli_real_escape_string($conn, $data["desc_produk"]);
    $product_stock = mysqli_real_escape_string($conn, $data["stok_produk"]);
    $old_image = mysqli_real_escape_string($conn, $data["gambar_lama"]);

    // Handle gambar baru
    if ($_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImage();
        if (!$upload['status']) {
            return ["status" => false, "message" => $upload['message']];
        }
        $product_thumb = $upload['filename']; // Changed to product_thumb
        
        // Hapus gambar lama
        if (file_exists("../../img/" . $old_image)) {
            unlink("../../img/" . $old_image);
        }
    } else {
        $product_thumb = $old_image;
    }

    $query = "UPDATE tb_product SET 
              product_name = '$product_name',
              product_desc = '$product_desc',
              product_thumb = '$product_thumb', // Changed to product_thumb
              product_stok = '$product_stock',
              product_price = '$product_price'
              WHERE product_id = '$id'";

    if (mysqli_query($conn, $query)) {
        return ["status" => true, "message" => "Produk berhasil diupdate"];
    } else {
        // Hapus gambar baru jika query gagal
        if ($product_thumb !== $old_image) {
            unlink("../../img/" . $product_thumb);
        }
        return ["status" => false, "message" => "Gagal mengupdate produk: " . mysqli_error($conn)];
    }
}

function deleteProduk($id) {
    global $conn;
    
    // Mulai transaction
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Dapatkan nama file gambar
        $result = mysqli_query($conn, "SELECT product_thumb FROM tb_product WHERE product_id = '$id'"); // Changed to product_thumb
        $row = mysqli_fetch_assoc($result);
        $image = $row['product_thumb']; // Changed to product_thumb
        
        // 2. Hapus produk
        mysqli_query($conn, "DELETE FROM tb_product WHERE product_id = '$id'");
        
        // 3. Hapus gambar
        if ($image && file_exists("../../img/" . $image)) {
            unlink("../../img/" . $image);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        return ["status" => true, "message" => "Produk berhasil dihapus"];
    } catch (mysqli_sql_exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        return ["status" => false, "message" => "Gagal menghapus produk: " . $e->getMessage()];
    }
}
?>