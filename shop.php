<?php
session_start();
require './controller/produkController.php';

$products = getAllProduk(); // Changed variable name to plural to avoid confusion

if (isset($_SESSION['login'])) {
  $user_id = $_SESSION['dataUser']['user_id'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Toko Komputer</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.min.css">
  <style>
    .card-custom {
      height: 100%;
      display: flex;
      flex-direction: column;
      margin-bottom: 20px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      overflow: hidden;
      transition: transform 0.3s ease;
    }

    .card-custom:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .card-custom-header {
      height: 200px;
      overflow: hidden;
    }

    .img-custom {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .card-custom:hover .img-custom {
      transform: scale(1.05);
    }

    .card-custom-body {
      flex-grow: 1;
      padding: 15px;
      display: flex;
      flex-direction: column;
    }

    .card-custom-text {
      margin-bottom: 15px;
    }

    .form-group {
      margin-bottom: 10px;
    }

    .form-check {
      margin-bottom: 15px;
    }

    .button-purple {
      background-color: #4a1667;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .button-purple:hover {
      background-color: #5d1d82;
    }

    #multiCartBtn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
  </style>
</head>

<body id="home">
  <!-- navbar -->
  <nav class="navbar-container">
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

  <!-- all product -->
  <section class="product" id="shop">
    <div class="product-content">
      <div class="container">
        <h2 class="text-center mb-5">Our Products</h2>

        <form id="multiCartForm">
          <div class="row">
            <?php foreach ($products as $product) : ?>
              <div class="col-md-4 mb-4" data-aos="zoom-in">
                <div class="card-custom">
                  <div class="card-custom-header">
                    <img src="img/<?= htmlspecialchars($product['product_thumb']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="img-custom">
                  </div>
                  <div class="card-custom-body">
                    <div class="card-custom-text">
                      <h4 class="m-0"><?= htmlspecialchars($product['product_name']) ?></h4>
                      <span class="d-block font-weight-bold mb-2">Rp.<?= number_format($product['product_price'], 0, ',', '.') ?></span>
                      <small class="text-muted">Stok: <?= htmlspecialchars($product['product_stok']) ?></small>
                    </div>

                    <?php if (isset($_SESSION['login'])) : ?>
                      <div class="mt-auto">
                        <div class="form-group">
                          <label for="qty_<?= $product['product_id'] ?>">Jumlah:</label>
                          <input type="number" id="qty_<?= $product['product_id'] ?>" name="qty[<?= $product['product_id'] ?>]" class="form-control" min="1" max="<?= htmlspecialchars($product['product_stok']) ?>" value="1">
                        </div>
                        <button type="button" onclick="addToCart(<?= htmlspecialchars($product['product_id']) ?>,
           document.getElementById('qty_<?= htmlspecialchars($product['product_id']) ?>').value,
           <?= htmlspecialchars($user_id) ?>)" class="btn btn-block button-purple">
                          <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                      </div>
                    <?php else : ?>
                      <a href="./auth/login" class="btn btn-block button-purple mt-3">
                        <i class="fas fa-lock"></i> Login to Order
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </form>

        <?php if (isset($_SESSION['login'])) : ?>
          <button id="multiCartBtn" class="btn btn-primary btn-lg rounded-circle" onclick="addMultipleToCart(<?= htmlspecialchars($user_id) ?>)" title="Add Selected Items to Cart">
            <i class="fas fa-cart-plus"></i>
            <span class="badge badge-light ml-1" id="selectedCount">0</span>
          </button>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- footer -->
  <section class="footer bg-dark" id="contact">
    <div class="footer-content">
      <div class="container">
        <div class="row">
          <div class="col-md-5 my-3 mx-auto" data-aos="fade-in">
            <h4 class="text-light text-poppins font-weight-bold">Useful Links</h4>
            <div class="d-flex flex-column">
              <a href="#home" class="text-light font-weight-light">Home</a>
              <a href="#shop" class="text-light font-weight-light">Shop</a>
            </div>
          </div>
          <div class="col-md-5 my-3 mx-auto" data-aos="fade-in">
            <h4 class="text-light text-poppins font-weight-bold">Toko Komputer</h4>
            <p class="d-block font-weight-light text-light">
              Toko Komputer adalah Website berbelanja online dengan fasilitas memadai
            </p>
          </div>
        </div>
        <div class="row">
          <div class="col">
            <div class="d-flex justify-content-center align-items-center text-center flex-column mx-auto">
              <span class="d-block text-light">© Copyright <strong><?= date('Y') ?></strong>. All Right Reserved</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- javascript -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="assets/js/script.js"></script>

  <script>
    $(document).ready(function() {
      AOS.init();

      // Update selected products count
      $('input[name="products[]"]').change(function() {
        const selectedCount = $('input[name="products[]"]:checked').length;
        $('#selectedCount').text(selectedCount);

        if (selectedCount > 0) {
          $('#multiCartBtn').show();
        } else {
          $('#multiCartBtn').hide();
        }
      });

      // Initialize - hide if no products selected
      $('#multiCartBtn').hide();
    });

    function addToCart(productId, qty, user_id) {
    // Validasi jumlah
    if (qty <= 0) {
        Swal.fire('Error', 'Jumlah tidak valid', 'error');
        return;
    }

    // Tampilkan loading
    Swal.fire({
        title: 'Processing...',
        text: 'Menambahkan produk ke keranjang',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Kirim request AJAX
    $.ajax({
        url: 'add-to-cart.php',
        method: 'POST',
        data: {
            product_id: productId,
            user_id: user_id,
            qty: qty
        },
        dataType: 'json', // Expect JSON response
        success: function (response) {
            console.log("Response from add-to-cart.php:", response);  // Debugging

            try {
                const result = JSON.parse(response);
                console.log("Parsed JSON response:", result); // Debugging

                if (result.statusCode === 200) {
                    Swal.fire({
                        title: 'Berhasil',
                        text: result.message || 'Produk berhasil ditambahkan ke keranjang',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Lihat Keranjang',
                        cancelButtonText: 'Lanjut Belanja'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            console.log("Redirecting to my-cart.php"); // Debugging
                            window.location.href = './my-cart';
                        } else {
                            console.log("Staying on shop page"); // Debugging
                        }
                    });
                } else {
                    Swal.fire('Error', result.message || 'Gagal menambahkan produk', 'error');
                }
            } catch (e) {
                console.error("Error parsing JSON response:", e); // Debugging
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            } finally {
                Swal.close(); // Ensure SweetAlert loading is closed
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", error);  // Debugging
            Swal.fire('Network Error', 'Periksa koneksi Anda dan coba lagi', 'error');
        }
    });
}

    function addMultipleToCart(user_id) {
      const selectedProducts = $('input[name="products[]"]:checked');

      if (selectedProducts.length === 0) {
        Swal.fire('Info', 'Silakan pilih produk terlebih dahulu', 'info');
        return;
      }

      // Validate quantities
      let valid = true;
      selectedProducts.each(function() {
        const productId = $(this).val();
        const qty = $(`#qty_${productId}`).val();
        const maxStock = $(`#qty_${productId}`).attr('max');

        if (qty <= 0 || qty > maxStock) {
          valid = false;
          $(`#qty_${productId}`).addClass('is-invalid');
        } else {
          $(`#qty_${productId}`).removeClass('is-invalid');
        }
      });

      if (!valid) {
        Swal.fire('Error', 'Beberapa jumlah produk tidak valid', 'error');
        return;
      }

      Swal.fire({
        title: 'Tambah ke Keranjang?',
        text: `Anda akan menambahkan ${selectedProducts.length} produk ke keranjang`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Tambahkan',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading
          Swal.fire({
            title: 'Processing...',
            text: 'Menambahkan produk ke keranjang',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          $.ajax({
            url: 'add-to-cart.php',
            method: 'POST',
            data: $('#multiCartForm').serialize() + '&user_id=' + user_id + '&multi=1',
            success: function(response) {
              console.log("Response:", response);
              
              // Assume success
              Swal.fire({
                title: 'Berhasil',
                text: 'Produk berhasil ditambahkan ke keranjang',
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Lihat Keranjang',
                cancelButtonText: 'Lanjut Belanja'
              }).then((result) => {
                if (result.isConfirmed) {
                  window.location.href = './my-cart';
                }
              });
            },
            error: function() {
              Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            }
          });
        }
      });
    }
  </script>
</body>

</html>