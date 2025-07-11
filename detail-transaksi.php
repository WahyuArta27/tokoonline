<?php
session_start();

require './database/koneksi.php';
require './controller/transaksiController.php';

// Ensure user is logged in
if (!isset($_SESSION['login'])) {
    header("Location: ./auth/login");
    exit();
}

$user_id = $_SESSION['dataUser']['user_id'];
$fullname = $_SESSION['dataUser']['fullname'];

// Response handling
$response = (isset($_GET['r'])) ? $_GET['r'] : null;
$messages = [
    "success" => "Payment proof successfully sent, thank you",
    "false" => "Sorry, failed to send payment proof"
];
$response = isset($messages[$response]) ? $messages[$response] : null;

// Get user transactions
$myTransaksi = getTransaksiByUserId($user_id);

// Process payment confirmation
if (isset($_POST['konfirmasi-pembayaran'])) {
    if (konfirmPayment($_POST)) {
        header("Location: ./detail-transaksi?r=success");
        exit();
    } else {
        header("Location: ./detail-transaksi?r=false");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta and Styles -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details - Toko Media</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include './partials/navbar.php'; ?>

    <!-- Transaction Details -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <?php if ($response) : ?>
                    <div class="alert <?= $response === "success" ? "alert-success" : "alert-danger" ?>">
                        <?= $response ?>
                    </div>
                <?php endif; ?>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myTransaksi as $transaksi): ?>
                            <tr>
                                <td><?= htmlspecialchars($transaksi['product_name']) ?></td>
                                <td>Rp <?= number_format($transaksi['product_price'], 0, ',', '.') ?></td>
                                <td><?= $transaksi['qty'] ?></td>
                                <td>Rp <?= number_format($transaksi['product_price'] * $transaksi['qty'], 0, ',', '.') ?></td>
                                <td><?= $transaksi['status_pembayaran'] == 1 ? 'Paid' : 'Pending' ?></td>
                                <td>
                                    <?php if ($transaksi['status_pembayaran'] == 0): ?>
                                        <button data-id="<?= $transaksi['transaksi_id'] ?>" class="btn btn-primary btn-sm">Pay</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include './partials/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
</body>
</html>