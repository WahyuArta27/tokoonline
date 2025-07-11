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
    "false" => "Sorry, failed to send payment proof",
    "invalid" => "Invalid file format. Please upload JPG, JPEG, or PNG image"
];
$response = isset($messages[$response]) ? $messages[$response] : null;

// Process payment confirmation
if (isset($_POST['konfirmasi-pembayaran'])) {
    // Validate file upload
    $valid_extensions = ['jpg', 'jpeg', 'png'];
    $max_file_size = 2 * 1024 * 1024; // 2MB
    
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['payment_proof']['tmp_name'];
        $file_size = $_FILES['payment_proof']['size'];
        $file_name = $_FILES['payment_proof']['name'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file extension
        if (!in_array($file_ext, $valid_extensions)) {
            header("Location: ./detail-transaksi?r=invalid");
            exit();
        }
        
        // Validate file size
        if ($file_size > $max_file_size) {
            header("Location: ./detail-transaksi?r=invalid");
            exit();
        }
        
        // Generate unique filename
        $new_file_name = 'payment_' . time() . '_' . $user_id . '.' . $file_ext;
        $upload_path = './uploads/payments/' . $new_file_name;
        
        // Create directory if not exists
        if (!file_exists('./uploads/payments/')) {
            mkdir('./uploads/payments/', 0777, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            // Add payment proof to database
            $_POST['payment_proof'] = $new_file_name;
            
            if (konfirmPayment($_POST)) {
                header("Location: ./detail-transaksi?r=success");
                exit();
            } else {
                // Delete uploaded file if database update fails
                if (file_exists($upload_path)) {
                    unlink($upload_path);
                }
                header("Location: ./detail-transaksi?r=false");
                exit();
            }
        } else {
            header("Location: ./detail-transaksi?r=false");
            exit();
        }
    } else {
        header("Location: ./detail-transaksi?r=invalid");
        exit();
    }
}

// Get user transactions
$myTransaksi = getTransaksiByUserId($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta and Styles -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details - Toko Komputer</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css">
    <style>
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include './partials/navbar.php'; ?>

    <!-- Transaction Details -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">My Transactions</h2>
                
                <?php if ($response) : ?>
                    <div class="alert <?= $response === "success" ? "alert-success" : "alert-danger" ?> alert-dismissible fade show">
                        <?= $response ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
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
                        <?php if (empty($myTransaksi)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No transactions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($myTransaksi as $transaksi): ?>
                                <?php if (isset($transaksi['product_name'])): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($transaksi['product_name']) ?></td>
                                        <td>Rp <?= number_format($transaksi['product_price'] ?? 0, 0, ',', '.') ?></td>
                                        <td><?= $transaksi['qty'] ?? 0 ?></td>
                                        <td>Rp <?= number_format(($transaksi['product_price'] ?? 0) * ($transaksi['qty'] ?? 0), 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $transaksi['status_pembayaran'] == 1 ? 'success' : 'warning' ?>">
                                                <?= $transaksi['status_pembayaran'] == 1 ? 'Paid' : 'Pending' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($transaksi['status_pembayaran'] == 0): ?>
                                                <button 
                                                    data-id="<?= $transaksi['transaksi_id'] ?>" 
                                                    class="btn btn-primary btn-sm pay-button"
                                                    data-toggle="modal" 
                                                    data-target="#paymentModal">
                                                    Upload Payment Proof
                                                </button>
                                            <?php else: ?>
                                                <span class="badge badge-success">Payment Verified</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Upload Payment Proof</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="transaksi_id" id="transaksi_id" value="">
                        
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle"></i> 
                                Please upload a clear image of your payment receipt or screenshot of the payment confirmation.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_proof">Payment Proof (JPG, JPEG, PNG max 2MB)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png" required>
                                <label class="custom-file-label" for="payment_proof">Choose file</label>
                            </div>
                            <img id="preview" class="preview-image mt-3" src="#" alt="Payment Proof Preview">
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_notes">Notes (Optional)</label>
                            <textarea class="form-control" id="payment_notes" name="payment_notes" rows="2" placeholder="Any additional information about your payment"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="konfirmasi-pembayaran" class="btn btn-success">Submit Payment Proof</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include './partials/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Set transaction ID when Pay button is clicked
        $('.pay-button').click(function() {
            var transaksiId = $(this).data('id');
            $('#transaksi_id').val(transaksiId);
            console.log('Transaction ID set to: ' + transaksiId);
        });
        
        // Auto close alerts after 5 seconds
        window.setTimeout(function() {
            $(".alert-dismissible").fadeTo(500, 0).slideUp(500, function() {
                $(this).remove();
            });
        }, 5000);
        
        // File input change event - show file name and preview image
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
            
            // Image preview
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#preview').attr('src', e.target.result);
                    $('#preview').css('display', 'block');
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    </script>
</body>
</html>