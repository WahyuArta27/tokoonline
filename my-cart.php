<?php
session_start();

require './database/koneksi.php';
require './controller/cartController.php';

// Pastikan pengguna sudah login
if (!isset($_SESSION['login'])) {
    header("Location: ./auth/login");
    exit();
}

$user_id = $_SESSION['dataUser']['user_id'];
$myCart = getMyCart($user_id);

// Debugging - tampilkan data cart
// var_dump($myCart);

if (isset($_POST['update'])) {
    $cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
    $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($cart_id === false || $qty === false) {
        echo "<script>
            window.location.href = './my-cart?r=updatefailed&error=invalidinput'
        </script>";
        exit();
    }

    $updateData = ['cart_id' => $cart_id, 'qty' => $qty];

    if (updateCart($updateData) > 0) {
        echo "<script>
            window.location.href = './my-cart?r=updatesuccess'
        </script>";
    } else {
        echo "<script>
            window.location.href = './my-cart?r=updatefailed'
        </script>";
    }
}

if (isset($_POST['delete'])) {
    $id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);

    if ($id === false) {
        echo "<script>
            window.location.href = './my-cart?r=deletefailed&error=invalidinput'
        </script>";
        exit();
    }

    if (deleteCart($id)) {
        echo "<script>
            window.location.href = './my-cart?r=deletesuccess'
        </script>";
    } else {
        echo "<script>
            window.location.href = './my-cart?r=deletefailed'
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- meta -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- title -->
    <title>My Cart - Toko Komputer</title>

    <!-- css -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.25/datatables.min.css" />
</head>

<body id="home">

    <!-- navbar -->
    <nav class="navbar-container sticky-top">
        <div class="navbar-logo">
            <h3><a href="./">Toko Komputer</a></h3>
        </div>
        <div class="navbar-box">
            <ul class="navbar-list">
                <li><a href="./"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="./shop"><i class="fas fa-shopping-cart"></i> Shop</a></li>
                <?php if (!isset($_SESSION['login'])) { ?>
                    <li><a href="./auth/login"><i class="fas fa-lock"></i> Signin</a></li>
                <?php } else { ?>
                    <li><a href="./my-cart"><i class="fas fa-shopping-cart"></i> My Cart</a></li>
                    <li><a href="./auth/logout"><i class="fas fa-lock"></i> Logout</a></li>
                <?php } ?>
            </ul>
        </div>
        <div class="navbar-toggle">
            <span></span>
        </div>
    </nav>
    <!-- akhir navbar -->

    <!-- mycart -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-2">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Menu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row"><a href="./detail-transaksi">Pesanan Saya</a></th>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-10">
                <?php if (empty($myCart)) : ?>
                    <div class="alert alert-info">
                        Keranjang belanja Anda kosong. <a href="./shop">Mulai belanja</a>
                    </div>
                <?php else : ?>
                    <div class="card">
                        <div class="card-body">
                            <table id="tabel-data" class="table table-striped table-bordered text-center" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stok</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Sub total</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $total = 0; ?>
                                    <?php foreach ($myCart as $item) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td><?= htmlspecialchars($item['product_stok']) ?></td>
                                            <td>Rp.<?= number_format($item['product_price'], 0, ',', '.') ?></td>
                                            <form action="" method="post">
                                                <td>
                                                    <input type="hidden" name="cart_id" value="<?= htmlspecialchars($item['keranjang_id']) ?>">
                                                    <input type="number" name="qty" class="form-control" value="<?= htmlspecialchars($item['qty']) ?>" min="1" max="<?= htmlspecialchars($item['product_stok']) ?>">
                                                </td>
                                                <?php
                                                $sub_total = intval($item['product_price']) * intval($item['qty']);
                                                $sub_total2 = number_format($sub_total, 0, ',', '.');
                                                ?>
                                                <td>Rp.<?= $sub_total2 ?></td>
                                                <td>
                                                    <button type="submit" class="btn btn-warning" name="update">
                                                        <i class="fas fa-sync-alt"></i> Update
                                                    </button>
                                                    <button type="submit" class="btn btn-danger" name="delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </form>
                                        </tr>
                                        <?php $total += $sub_total ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <h3>Total: Rp. <?= number_format($total, 0, ',', '.') ?></h3>
                                <a href="./checkout" class="btn btn-success btn-lg">
                                    <i class="fas fa-credit-card"></i> Checkout
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- mycart end -->

    <!-- javascript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="assets/js/script.js"></script>

    <script>
        $(document).ready(function() {
            $('#tabel-data').DataTable();
            
            // SweetAlert untuk konfirmasi delete
            $('button[name="delete"]').click(function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Produk akan dihapus dari keranjang!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(this).closest('form').submit();
                    }
                });
            });
        });
    </script>
</body>

</html>