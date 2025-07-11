<?php
session_start();

if (!isset($_SESSION['login'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Hanya perlu include satu kali controller dengan path yang benar
require_once __DIR__ . '/../../controller/transaksiController.php';

// Set default date range (current month)
$tgl_awal = date('Y-m-01');
$tgl_akhir = date('Y-m-d');

// Proses filter form
if (isset($_POST['tampilkan'])) {
    $tgl_awal = $_POST['tgl_awal'];
    $tgl_akhir = $_POST['tgl_akhir'] ?? date('Y-m-d');
    $transactions = getTransaksiFilter($tgl_awal, $tgl_akhir);
} else {
    // Default: tampilkan transaksi bulan berjalan
    $transactions = getTransaksiFilter($tgl_awal, $tgl_akhir);
}

// Response messages
$response = $_GET['response'] ?? null;
$messages = [
    "success" => "Operation successful",
    "error"   => "An error occurred"
];
$alert = isset($messages[$response]) ? $messages[$response] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Transaksi - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
      .sidebar {
          min-height: 100vh;
          background: #f8f9fa;
      }
      .table-responsive {
          overflow-x: auto;
      }
      .badge-paid {
          background-color: #28a745;
      }
      .badge-pending {
          background-color: #ffc107;
      }
  </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-file-alt"></i> Laporan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="h3 mb-4">Laporan Transaksi</h2>

                <?php if ($alert): ?>
                    <div class="alert alert-<?= $response === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                        <?= $alert ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-4">
                                <label for="tgl_awal" class="form-label">Tanggal Awal</label>
                                <input type="date" class="form-control" id="tgl_awal" name="tgl_awal" 
                                       value="<?= htmlspecialchars($tgl_awal) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="tgl_akhir" class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="tgl_akhir" name="tgl_akhir" 
                                       value="<?= htmlspecialchars($tgl_akhir) ?>" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="tampilkan" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Transaction Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($transactions)): ?>
                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <a href="cetak.php?tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>" 
                                       class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </a>
                                </div>
                                <div>
                                    <span class="badge bg-primary">
                                        Total Transaksi: <?= count($transactions) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Tanggal</th>
                                            <th>Pelanggan</th>
                                            <th>Produk</th>
                                            <th>Qty</th>
                                            <th>Harga</th>
                                            <th>Subtotal</th>
                                            <th>Status</th>
                                            <th>Pembayaran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $grandTotal = 0; ?>
                                        <?php foreach ($transactions as $trx): ?>
                                            <?php
                                            $subtotal = $trx['product_price'] * $trx['qty'];
                                            $grandTotal += $subtotal;
                                            ?>
                                            <tr>
                                                <td><?= $trx['transaksi_id'] ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($trx['tanggal_transaksi'])) ?></td>
                                                <td><?= htmlspecialchars($trx['fullname'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($trx['product_name']) ?></td>
                                                <td><?= $trx['qty'] ?></td>
                                                <td>Rp <?= number_format($trx['product_price'], 0, ',', '.') ?></td>
                                                <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                                <td>
                                                    <span class="badge <?= $trx['status_pembayaran'] == 1 ? 'badge-paid' : 'badge-pending' ?>">
                                                        <?= $trx['status_pembayaran'] == 1 ? 'Lunas' : 'Pending' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $trx['nama_bank'] ? htmlspecialchars($trx['nama_bank']) . ' (' . htmlspecialchars($trx['no_bank']) . ')' : '-' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <th colspan="6">Total Keseluruhan</th>
                                            <th colspan="3">Rp <?= number_format($grandTotal, 0, ',', '.') ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Tidak ada data transaksi untuk periode yang dipilih.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto close alert after 5 detik
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    new bootstrap.Alert(alert).close();
                });
            }, 5000);
        });
    </script>
</body>
</html>