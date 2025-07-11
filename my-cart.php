<?php
session_start();
require './controller/cartController.php'; // Adjust path as needed

// Check if user is logged in
if (!isset($_SESSION['login'])) {
    header("Location: ./auth/login"); // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['dataUser']['user_id'];
$cartItems = getMyCart($user_id); // Get cart items
$cartTotal = getCartTotal($user_id); // Get cart total
$transactions = getUserTransactions($user_id); // Get user transactions
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Toko Komputer</title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Adjust path as needed -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.min.css">
    <style>
      /* Add your styles here */
      .cart-item-image {
        max-width: 80px;
        height: auto;
      }
      .nav-tabs .nav-link {
        color: #495057;
      }
      .nav-tabs .nav-link.active {
        font-weight: bold;
        color: #0d6efd;
      }
      .transaction-item {
        border-bottom: 1px solid #dee2e6;
        padding: 15px 0;
      }
      .transaction-item:last-child {
        border-bottom: none;
      }
    </style>
</head>
<body>

    <?php include './partials/navbar.php'; ?> <!-- Adjust path as needed -->

    <div class="container mt-5">
        <h2>My Shopping</h2>
        
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="cart-tab" data-bs-toggle="tab" data-bs-target="#cart" type="button" role="tab" aria-controls="cart" aria-selected="true">
                    <i class="fas fa-shopping-cart"></i> My Cart
                    <?php if (!empty($cartItems)): ?>
                        <span class="badge bg-primary"><?= count($cartItems) ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                    <i class="fas fa-clipboard-list"></i> My Orders
                    <?php if (!empty($transactions)): ?>
                        <span class="badge bg-primary"><?= count($transactions) ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            <!-- Cart Tab -->
            <div class="tab-pane fade show active" id="cart" role="tabpanel" aria-labelledby="cart-tab">
                <?php if (empty($cartItems)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Your cart is empty.
                        <a href="./shop" class="alert-link">Start shopping now</a>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr id="cart-item-<?= htmlspecialchars($item['keranjang_id']) ?>">
                                    <td>
                                        <img src="img/<?= htmlspecialchars($item['product_thumb']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="cart-item-image">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </td>
                                    <td>Rp. <?= number_format($item['product_price'], 0, ',', '.') ?></td>
                                    <td>
                                        <input type="number"
                                               value="<?= htmlspecialchars($item['qty']) ?>"
                                               min="1"
                                               max="<?= htmlspecialchars($item['product_stok']) ?>"
                                               data-cart-id="<?= htmlspecialchars($item['keranjang_id']) ?>"
                                               class="form-control qty-input"
                                               id="qty-<?= htmlspecialchars($item['keranjang_id']) ?>">
                                    </td>
                                    <td id="subtotal-<?= htmlspecialchars($item['keranjang_id']) ?>">Rp. <?= number_format($item['product_price'] * $item['qty'], 0, ',', '.') ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm remove-item" data-cart-id="<?= htmlspecialchars($item['keranjang_id']) ?>">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Total:</strong></td>
                                <td id="cart-total">Rp. <?= number_format($cartTotal, 0, ',', '.') ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="text-end">
                        <a href="./checkout.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Orders Tab -->
            <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                <?php if (empty($transactions)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You don't have any orders yet.
                        <a href="./shop" class="alert-link">Start shopping now</a>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($transactions as $transaction): ?>
                            <div class="list-group-item transaction-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5>Order #<?= htmlspecialchars($transaction['transaksi_id']) ?></h5>
                                        <small class="text-muted">
                                            Date: <?= date('d M Y H:i', strtotime($transaction['tanggal_transaksi'])) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?= $transaction['status'] == 'completed' ? 'success' : ($transaction['status'] == 'pending' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($transaction['status']) ?>
                                        </span>
                                        <div class="mt-2">
                                            <strong>Total: Rp. <?= number_format($transaction['total_harga'], 0, ',', '.') ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <a href="detail-transaksi.php?id=<?= $transaction['transaksi_id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <?php if ($transaction['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-clock"></i> Track Order
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($transaction['status'] == 'completed'): ?>
                                        <button class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-redo"></i> Buy Again
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="container mt-5">
    <!-- Kode yang sudah ada... -->
    
    <?php if (empty($cartItems)): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <!-- Tabel cart yang sudah ada... -->
    <?php endif; ?>
    
    <!-- Tambahkan tombol ini -->
    <div class="mt-4">
        <a href="./detail-transaksi" class="btn btn-secondary">
            <i class="fas fa-clipboard-list"></i> View My Orders
        </a>
    </div>
</div>

    <?php include './partials/footer.php'; ?> <!-- Adjust path as needed -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize Bootstrap tabs
        var tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEls.forEach(function(tabEl) {
            tabEl.addEventListener('click', function (event) {
                event.preventDefault();
                var tab = new bootstrap.Tab(tabEl);
                tab.show();
            });
        });

        // Update quantity
        $('.qty-input').on('change', function() {
            const cartId = $(this).data('cart-id');
            const newQty = $(this).val();

            // Validate quantity
            const maxQty = $(this).attr('max');
            if (newQty > maxQty) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Quantity',
                    text: 'Quantity exceeds available stock',
                });
                $(this).val(maxQty);
                return;
            }

            updateQty(cartId, newQty);
        });

// Fungsi remove item
$('.remove-item').on('click', function() {
    const cartId = $(this).data('cart-id');
    
    Swal.fire({
        title: 'Remove Item?',
        text: "Are you sure you want to remove this item from your cart?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, remove it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'controller/removeCart.php', // Pastikan path ini benar
                method: 'POST',
                data: {
                    cart_id: cartId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove the table row with animation
                        $('#cart-item-' + cartId).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Update cart total
                            $('#cart-total').text('Rp. ' + response.totalFormatted);
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Removed!',
                                text: response.message || 'Item removed from cart',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            
                            // Reload page if cart is empty
                            if (response.cartEmpty) {
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to remove item'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error removing item:', status, error);
                    if (xhr.responseText) {
                        console.error('Server response:', xhr.responseText);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error occurred'
                    });
                }
            });
        }
    });
});

        // Function to update quantity
        function updateQty(cartId, newQty) {
            $.ajax({
                url: 'controller/updateCart.php',
                method: 'POST',
                data: {
                    cart_id: cartId,
                    qty: newQty
                },
                dataType: 'json',
                beforeSend: function() {
                    // Show loading
                    Swal.fire({
                        title: 'Updating...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        // Update subtotal in the table
                        $('#subtotal-' + cartId).text('Rp. ' + response.subtotalFormatted);
                        $('#cart-total').text('Rp. ' + response.totalFormatted);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Quantity updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to update quantity'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('Error updating quantity:', status, error, xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error occurred'
                    });
                }
            });
        }

        // Function to remove item
        function removeItem(cartId) {
            $.ajax({
                url: 'controller/removeCart.php',
                method: 'POST',
                data: {
                    cart_id: cartId
                },
                dataType: 'json',
                beforeSend: function() {
                    Swal.fire({
                        title: 'Removing...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        // Remove the table row with animation
                        $('#cart-item-' + cartId).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Update cart total
                            $('#cart-total').text('Rp. ' + response.totalFormatted);
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Removed!',
                                text: 'Item removed from cart',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            
                            // Reload page if cart is empty
                            if (response.cartEmpty) {
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to remove item'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('Error removing item:', status, error, xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error occurred'
                    });
                }
            });
        }
    });
    </script>
</body>
</html>