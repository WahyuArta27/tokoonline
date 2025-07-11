<?php
session_start();

if (!isset($_SESSION['login'])) {
    header('Location: ../auth/login.php');
    exit;
}

require __DIR__ . '/../../controller/transaksiController.php';

$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$transactions = getTransaksiFilter($tgl_awal, $tgl_akhir);
$totalPendapatan = getTotalPendapatan($tgl_awal, $tgl_akhir);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Transaksi_".date('Ymd_His').".xls");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .title { font-size: 18px; font-weight: bold; }
        .header { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .border { border: 1px solid #ddd; }
    </style>
</head>
<body>
    <table border="1">
        <tr>
            <td colspan="9" class="title">LAPORAN TRANSAKSI</td>
        </tr>
        <tr>
            <td colspan="9">
                Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?>
            </td>
        </tr>
        <tr class="header">
            <th width="10">No</th>
            <th width="15">ID Transaksi</th>
            <th width="20">Tanggal</th>
            <th width="25">Pelanggan</th>
            <th width="30">Produk</th>
            <th width="10">Qty</th>
            <th width="15">Harga</th>
            <th width="15">Subtotal</th>
            <th width="15">Status</th>
        </tr>
        <?php if (!empty($transactions)): ?>
            <?php $no = 1; ?>
            <?php foreach ($transactions as $trx): ?>
                <?php $subtotal = $trx['product_price'] * $trx['qty']; ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $trx['transaksi_id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($trx['tanggal_transaksi'])) ?></td>
                    <td><?= htmlspecialchars($trx['fullname'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($trx['product_name']) ?></td>
                    <td class="text-right"><?= $trx['qty'] ?></td>
                    <td class="text-right"><?= number_format($trx['product_price'], 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($subtotal, 0, ',', '.') ?></td>
                    <td><?= $trx['status_pembayaran'] == 1 ? 'Lunas' : 'Pending' ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="header">
                <td colspan="7">TOTAL PENDAPATAN</td>
                <td colspan="2" class="text-right">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></td>
            </tr>
        <?php else: ?>
            <tr>
                <td colspan="9" align="center">Tidak ada data transaksi</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>