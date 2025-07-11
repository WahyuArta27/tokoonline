// Existing menu toggle functionality
let menuButton = document.querySelector(".button-menu");
let container = document.querySelector(".container");
let pageContent = document.querySelector(".page-content");
let responsiveBreakpoint = 991;

if (menuButton && container) {
  if (window.innerWidth <= responsiveBreakpoint) {
    container.classList.add("nav-closed");
  }

  menuButton.addEventListener("click", function () {
    container.classList.toggle("nav-closed");
  });

  if (pageContent) {
    pageContent.addEventListener("click", function () {
      if (window.innerWidth <= responsiveBreakpoint) {
        container.classList.add("nav-closed");
      }
    });
  }

  window.addEventListener("resize", function () {
    if (window.innerWidth > responsiveBreakpoint) {
      container.classList.remove("nav-closed");
    }
  });
}

// Existing time display function
function showTime() {
  const timeElement = document.getElementById("time");
  if (!timeElement) return;

  let today = new Date();
  let curr_hour = today.getHours();
  let curr_minute = today.getMinutes();
  let curr_second = today.getSeconds();
  if (curr_hour == 0) {
    curr_hour = 12;
  }
  if (curr_hour > 24) {
    curr_hour = curr_hour - 12;
  }
  curr_hour = checkTime(curr_hour);
  curr_minute = checkTime(curr_minute);
  curr_second = checkTime(curr_second);
  timeElement.innerHTML = `<strong>Jam hari ini : ${curr_hour}:${curr_minute}:${curr_second} </strong>`;
}

function checkTime(i) {
  if (i < 10) {
    i = "0" + i;
  }
  return i;
}
setInterval(showTime, 500);

// New Cart Functionality
$(document).ready(function () {
  // Initialize DataTables if it exists on the page
  if ($.fn.DataTable && $("#tabel-data").length) {
    $("#tabel-data").DataTable({
      "responsive": true,
      "language": {
        "search": "Cari:",
        "lengthMenu": "Tampilkan _MENU_ data per halaman",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        "infoEmpty": "Tidak ada data yang ditampilkan",
        "infoFiltered": "(difilter dari _MAX_ total data)",
        "zeroRecords": "Tidak ada data yang cocok",
        "paginate": {
          "first": "Pertama",
          "last": "Terakhir",
          "next": "Selanjutnya",
          "previous": "Sebelumnya"
        }
      }
    });
  }

  // Initialize cart counter if user is logged in
  if (typeof userId !== 'undefined') {
    updateCartCounter();
    // Auto-update cart counter every 30 seconds
    setInterval(updateCartCounter, 30000);
  }

  // Handle checkout form submission
  $("#checkoutForm").on('submit', function(e) {
    // Form validation
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
    
    // Show loading state
    Swal.fire({
      title: 'Processing...',
      text: 'Please wait while we process your order',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    return true;
  });

  // Cart functions
  function updateCartCounter() {
    if (typeof userId !== 'undefined') {
      $.ajax({
        url: 'controller/getCartCount.php',
        method: 'GET',
        data: { user_id: userId },
        dataType: 'json', // Expect JSON response
        success: function(data) {
          if (data && data.count !== undefined) {
            $('.cart-count').text(data.count);
          } else {
            console.error('Invalid cart count data:', data);
          }
        },
        error: function(xhr, status, error) {
          console.error('Failed to update cart counter:', status, error, xhr.responseText);
        }
      });
    } else {
      console.warn('userId is not defined.');
    }
  }

  // Add to cart function (single item)
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

  // Add multiple items to cart
  window.addMultipleToCart = function(user_id) {
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
        $.ajax({
          url: 'add-to-cart.php', // Direct path to the file
          method: 'POST',
          data: $('#multiCartForm').serialize() + '&user_id=' + user_id + '&multi=1',
          dataType: 'json', // Expect JSON response
          success: function(res) {
            if (res && res.statusCode === 200) {
              Swal.fire({
                title: 'Success',
                text: res.message,
                icon: 'success'
              }).then(() => {
                updateCartCounter();
                if (res.redirect) {
                  window.location.href = res.redirect;
                }
              });
            } else {
              let errorMsg = res.message || 'Gagal menambahkan produk';
              if (res.errors) {
                errorMsg += '\n' + res.errors.join('\n');
              }
              Swal.fire('Error', errorMsg, 'error');
            }
          },
          error: function(xhr, status, error) {
            console.error('Error adding multiple to cart:', status, error, xhr.responseText);
            Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
          }
        });
      }
    });
  };

  // Update cart item quantity
  window.updateQty = function(cartId, newQty) {
    $.ajax({
      url: 'controller/updateCart.php',
      method: 'POST',
      data: {
        cart_id: cartId,
        qty: newQty
      },
      dataType: 'json', // Expect JSON response
      success: function(res) {
        if (res && res.success) {
          updateCartCounter();
          // Optional: Update subtotal without page reload
          $(`#subtotal-${cartId}`).text('Rp ' + res.subtotal);
          $('#cart-total').text('Rp ' + res.total);
        } else {
          Swal.fire('Error', res.message || 'Gagal memperbarui jumlah', 'error');
        }
      },
      error: function(xhr, status, error) {
        console.error('Error updating quantity:', status, error, xhr.responseText);
        Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
      }
    });
  };

  // Remove item from cart
  window.removeItem = function(cartId) {
    Swal.fire({
      title: 'Hapus Item?',
      text: "Anda yakin ingin menghapus item ini dari keranjang?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: 'controller/removeCart.php',
          method: 'POST',
          data: { id: cartId },
          dataType: 'json', // Expect JSON response
          success: function(res) {
            if (res && res.success) {
              $(`#cart-item-${cartId}`).remove();
              updateCartCounter();
              $('#cart-total').text('Rp ' + res.total);
              Swal.fire('Deleted!', 'Item telah dihapus.', 'success');
              
              // If cart is now empty, reload the page
              if (res.empty) {
                window.location.reload();
              }
            }
          },
          error: function(xhr, status, error) {
            console.error('Error removing item:', status, error, xhr.responseText);
            Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
          }
        });
      }
    });
  };
});

// Floating cart button functionality
document.addEventListener('DOMContentLoaded', function() {
  // Only create floating cart if user is logged in
  if (typeof userId !== 'undefined') {
    const floatingCartBtn = document.createElement('div');
    floatingCartBtn.className = 'floating-cart-btn';
    floatingCartBtn.innerHTML = `
      <a href="my-cart">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count">0</span>
      </a>
    `;
    document.body.appendChild(floatingCartBtn);

    // Show/hide based on scroll
    window.addEventListener('scroll', function() {
      if (window.scrollY > 300) {
        floatingCartBtn.classList.add('show');
      } else {
        floatingCartBtn.classList.remove('show');
      }
    });
  }
});

// Add to cart function (updated version)
function addToCart(productId, qty = 1) {
    // Validate if user is logged in
    if (typeof userId === 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Login Required',
            text: 'Please login to add items to cart',
            footer: '<a href="auth/login">Click here to login</a>'
        });
        return;
    }

    // Show loading state
    const loadingSwal = Swal.fire({
        title: 'Adding to Cart',
        html: 'Please wait...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // AJAX request
    $.ajax({
        url: 'controller/add-to-cart.php',
        method: 'POST',
        data: {
            product_id: productId,
            qty: qty
        },
        dataType: 'json',
        success: function(response) {
            loadingSwal.close();
            
            if (response.success) {
                // Update cart counter
                if (response.cartCount) {
                    $('.cart-count, .floating-cart-btn .cart-count').text(response.cartCount).show();
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    html: `<strong>${response.productName}</strong> added to cart`,
                    showConfirmButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'View Cart',
                    cancelButtonText: 'Continue Shopping',
                    timer: 3000,
                    timerProgressBar: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'my-cart';
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: response.message || 'Failed to add to cart',
                    showConfirmButton: true
                });
            }
        },
        error: function(xhr, status, error) {
            loadingSwal.close();
            
            let errorMessage = 'Network error occurred';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 401) {
                errorMessage = 'Session expired. Please login again';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage,
                footer: xhr.status === 401 ? '<a href="auth/login">Click here to login</a>' : ''
            });
        }
    });
}