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
    </style>
</head>
<body>

    <?php include './partials/navbar.php'; ?> <!-- Adjust path as needed -->

    <div class="container mt-5">
        <h2>My Shopping Cart</h2>

        <?php if (empty($cartItems)): ?>
            <p>Your cart is empty.</p>
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
                                <button class="btn btn-danger btn-sm remove-item" data-cart-id="<?= htmlspecialchars($item['keranjang_id']) ?>">Remove</button>
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
            <a href="./checkout.php" class="btn btn-primary">Checkout</a>
        <?php endif; ?>
    </div>

    <?php include './partials/footer.php'; ?> <!-- Adjust path as needed -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Update quantity
        $('.qty-input').on('change', function() {
            const cartId = $(this).data('cart-id');
            const newQty = $(this).val();

            // You can add validation here (e.g., check if newQty is within stock)
            updateQty(cartId, newQty);
        });

        // Remove item
        $('.remove-item').on('click', function() {
            const cartId = $(this).data('cart-id');
            removeItem(cartId);
        });

        // Function to update quantity (AJAX call to your updateCart.php)
        function updateQty(cartId, newQty) {
            $.ajax({
                url: 'controller/updateCart.php', // Adjust path as needed
                method: 'POST',
                data: {
                    cart_id: cartId,
                    qty: newQty
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update subtotal in the table
                        $('#subtotal-' + cartId).text('Rp. ' + response.subtotalFormatted);
                        $('#cart-total').text('Rp. ' + response.totalFormatted);
                         Swal.fire('Success', 'Quantity updated successfully', 'success');
                    } else {
                        Swal.fire('Error', response.message || 'Failed to update quantity', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating quantity:', status, error, xhr.responseText);
                    Swal.fire('Error', 'Network error', 'error');
                }
            });
        }

        // Function to remove item (AJAX call to your removeCart.php)
        function removeItem(cartId) {
            $.ajax({
                url: 'controller/removeCart.php', // Adjust path as needed
                method: 'POST',
                data: {
                    cart_id: cartId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove the table row
                        $('#cart-item-' + cartId).remove();
                        $('#cart-total').text('Rp. ' + response.totalFormatted);

                         Swal.fire('Success', 'Item removed successfully', 'success');

                        // Optional: Reload page if cart is empty
                        if (response.cartEmpty) {
                            location.reload();
                        }
                    } else {
                         Swal.fire('Error', response.message || 'Failed to remove item', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error removing item:', status, error, xhr.responseText);
                    Swal.fire('Error', 'Network error', 'error');
                }
            });
        }
    });
    </script>
</body>
</html>