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
  return i < 10 ? "0" + i : i;
}
setInterval(showTime, 500);

// New Cart Functionality
$(document).ready(function () {
  // DataTables initialization jika elemen #tabel-data ada
  if ($.fn.DataTable && $("#tabel-data").length) {
    $("#tabel-data").DataTable({
      responsive: true,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data per halaman",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data yang ditampilkan",
        infoFiltered: "(difilter dari _MAX_ total data)",
        zeroRecords: "Tidak ada data yang cocok",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Selanjutnya",
          previous: "Sebelumnya"
        }
      }
    });
  }

  // Update cart counter jika user sudah login
  if (typeof userId !== 'undefined') {
    updateCartCounter();
    setInterval(updateCartCounter, 30000);
  }

  $("#checkoutForm").on('submit', function(e) {
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

  // Function updateCartCounter
  function updateCartCounter() {
    if (typeof userId !== 'undefined') {
      $.ajax({
        url: 'controller/getCartCount.php',
        method: 'GET',
        data: { user_id: userId },
        dataType: 'json',
        success: function(data) {
          if (data && typeof data.count !== 'undefined') {
            $('.cart-count, .floating-cart-btn .cart-count').text(data.count);
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

  // Add to cart (single item)
  window.addToCart = function(productId, qty = 1) {
    if (typeof userId === 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'Login Required',
        text: 'Please login to add items to cart',
        footer: '<a href="auth/login">Click here to login</a>'
      });
      return;
    }

    const loadingSwal = Swal.fire({
      title: 'Adding to Cart',
      html: 'Please wait...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

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
  };

  // Add multiple items to cart
  window.addMultipleToCart = function(user_id) {
    const selectedProducts = $('input[name="products[]"]:checked');

    if (selectedProducts.length === 0) {
      Swal.fire('Info', 'Silakan pilih produk terlebih dahulu', 'info');
      return;
    }

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
          url: 'add-to-cart.php',
          method: 'POST',
          data: $('#multiCartForm').serialize() + '&user_id=' + user_id + '&multi=1',
          dataType: 'json',
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
      data: { cart_id: cartId, qty: newQty },
      dataType: 'json',
      success: function(res) {
        if (res && res.success) {
          updateCartCounter();
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
          dataType: 'json',
          success: function(res) {
            if (res && res.success) {
              $(`#cart-item-${cartId}`).remove();
              updateCartCounter();
              $('#cart-total').text('Rp ' + res.total);
              Swal.fire('Deleted!', 'Item telah dihapus.', 'success');
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

    window.addEventListener('scroll', function() {
      if (window.scrollY > 300) {
        floatingCartBtn.classList.add('show');
      } else {
        floatingCartBtn.classList.remove('show');
      }
    });
  }
});