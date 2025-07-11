<?php
session_start();

require './database/koneksi.php';
require './controller/cartController.php';
require './controller/bankController.php';
require './controller/transaksiController.php';

// Ensure user is logged in
if (!isset($_SESSION['login'])) {
    header("Location: ./auth/login");
    exit();
}

$user_id = $_SESSION['dataUser']['user_id'];
$fullname = $_SESSION['dataUser']['fullname'];

$myCart = getMyCart($user_id);
$bank = getAllBank();

// Validate cart
if (empty($myCart)) {
    header("Location: ./my-cart?r=emptycart");
    exit();
}

// URL parameters
$response = (isset($_GET['r'])) ? $_GET['r'] : null;

// Response handling
$messages = [
    "trxsuccess" => "Transaction successful",
    "trxfailed" => "Transaction failed",
    "bankfalse" => "Please select payment method",
    "emptycart" => "Shopping cart is empty"
];
$response = isset($messages[$response]) ? $messages[$response] : null;

// Process transaction
if (isset($_POST['tambah-transaksi'])) {
    // Debug log
    error_log("=== CHECKOUT DEBUG ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("User ID: $user_id");
    error_log("Bank choices: " . print_r($bank, true));
    
    // Validate payment method
    if ($_POST['bank_id'] == "0") {
        error_log("Bank ID is 0 - redirecting to bankfalse");
        header("Location: ./checkout?r=bankfalse");
        exit();
    }

    // Simplified transaction data
    $transaction_data = [
        'user_id' => (int)$user_id,
        'bank_id' => (int)$_POST['bank_id']
    ];
    error_log("Transaction data: " . print_r($transaction_data, true));

    // Process transaction
    $transaksi_id = addTransaksi($transaction_data);
    error_log("Transaction result: " . ($transaksi_id ? "Success ID: $transaksi_id" : "Failed"));
    
    if ($transaksi_id) {
        error_log("Redirecting to success page");
        header("Location: ./detail-transaksi?id=" . $transaksi_id . "&r=trxsuccess");
        exit();
    } else {
        error_log("Redirecting to failed page");
        header("Location: ./checkout?r=trxfailed");
        exit();
    }
}
?>
<!DOCTYPE html>
<!-- HTML content remains the same -->
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Checkout - Toko Media</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.25/datatables.min.css" />
</head>

<body id="home">
    <!-- navbar -->
    <?php include './partials/navbar.php'; ?>

    <!-- checkout form -->
    <div class="container mt-5">
        <?php if ($response) : ?>
            <div class="alert alert-<?= strpos($response, 'failed') !== false ? 'danger' : 'success' ?> alert-dismissible fade show">
                <?= $response ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Customer Information</h4>
                    </div>
                    <div class="card-body">
                        <form id="checkoutForm" method="post">
                            <div class="form-group">
                                <label for="nama_pembeli">Full Name</label>
                                <input type="text" class="form-control" id="nama_pembeli" name="nama_pembeli" 
                                       value="<?= htmlspecialchars($fullname) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="alamat_pembeli">Complete Address</label>
                                <textarea class="form-control" id="alamat_pembeli" name="alamat_pembeli" rows="3" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="bank_id">Payment Method</label>
                                <select class="form-control" id="bank_id" name="bank_id" required>
                                    <option value="0">Select Payment</option>
                                    <?php foreach ($bank as $bankItem) : ?>
                                        <option value="<?= $bankItem['bank_id'] ?>">
                                            <?= htmlspecialchars($bankItem['nama_bank']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="tambah-transaksi" class="btn btn-success btn-block mt-4">
                                <i class="fas fa-check-circle"></i> Confirm Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Order Summary</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $total = 0; ?>
                                    <?php foreach ($myCart as $item) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td>Rp <?= number_format($item['product_price'], 0, ',', '.') ?></td>
                                            <td><?= $item['qty'] ?></td>
                                            <?php
                                            $subtotal = $item['product_price'] * $item['qty'];
                                            $total += $subtotal;
                                            ?>
                                            <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Total</th>
                                        <th>Rp <?= number_format($total, 0, ',', '.') ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- footer -->
    <?php include './partials/footer.php'; ?>

    <!-- javascript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="assets/js/script.js"></script>

    <script>
        $(document).ready(function() {
            // Form validation
            $('#checkoutForm').submit(function(e) {
                console.log('Form submitted');
                
                const bankId = $('#bank_id').val();
                if (bankId === "0") {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Warning',
                        text: 'Please select payment method'
                    });
                    return false;
                }
                
                const alamat = $('#alamat_pembeli').val().trim();
                if (alamat === "") {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Warning',
                        text: 'Please fill complete address'
                    });
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>