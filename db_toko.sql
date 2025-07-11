-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 11, 2025 at 08:05 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_toko`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_bank`
--

CREATE TABLE `tb_bank` (
  `bank_id` int(11) NOT NULL,
  `nama_bank` varchar(255) DEFAULT NULL,
  `no_bank` varchar(255) DEFAULT NULL,
  `atas_nama_bank` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_bank`
--

INSERT INTO `tb_bank` (`bank_id`, `nama_bank`, `no_bank`, `atas_nama_bank`) VALUES
(1, 'mandiri', '12300372', 'Jhon Doe'),
(2, 'BCA', '120037642', 'Jhonn');

-- --------------------------------------------------------

--
-- Table structure for table `tb_keranjang`
--

CREATE TABLE `tb_keranjang` (
  `keranjang_id` int(11) NOT NULL,
  `keranjang_grup` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `qty` int(100) DEFAULT NULL,
  `is_payed` int(11) NOT NULL DEFAULT 2,
  `transaksi_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_keranjang`
--

INSERT INTO `tb_keranjang` (`keranjang_id`, `keranjang_grup`, `product_id`, `user_id`, `qty`, `is_payed`, `transaksi_id`) VALUES
(53, 0, 6, 6, 2, 1, 43),
(54, 0, 6, 6, 3, 1, 44),
(55, 0, 6, 6, 11, 1, 45),
(61, 0, 6, 6, 1, 1, 46),
(62, 0, 6, 6, 1, 1, 47),
(63, 0, 6, 6, 3, 1, 48),
(64, 0, 6, 6, 1, 1, 70),
(65, 0, 6, 6, 1, 1, 71),
(66, 0, 6, 6, 1, 1, 72),
(67, 0, 6, 6, 2, 1, 73),
(68, 0, 6, 6, 1, 1, 74),
(69, 0, 6, 6, 1, 1, 75),
(71, 0, 9, 6, 1, 1, 76),
(72, 0, 11, 7, 2, 1, 77),
(73, 0, 11, 7, 1, 1, 78);

-- --------------------------------------------------------

--
-- Table structure for table `tb_product`
--

CREATE TABLE `tb_product` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `product_desc` varchar(255) DEFAULT NULL,
  `product_thumb` varchar(255) DEFAULT NULL,
  `product_stok` int(100) DEFAULT NULL,
  `product_price` int(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_product`
--

INSERT INTO `tb_product` (`product_id`, `product_name`, `product_desc`, `product_thumb`, `product_stok`, `product_price`) VALUES
(6, 'GEFORCE RTX 2060 GAMING X', 'Model Name : GeForce RTX™  Interface : PCI Express® Gen 4.0 x8 Cores : 2560 Units Core Clocks : Boost: 1777MHz Memory Speed : 14 Gbps Memory : 8GB GDDR6 Memory Bus : 128-bit Output : DisplayPort x 3 (v1.4a) HDMI x 1 (Supports 4K@120Hz as specified in HDMI', '68710b856befd.png', 191, 4000000),
(7, 'GEFORCE RTX 5070 12GB SHADOW 3X OC', 'G507T-16GTCP NVIDIA GeForce RTX 5070 Ti PCI Express Gen 5 Extreme Performance: 2580 MHz (MSI Center) Boost: 2572 MHz (GAMING & SILENT Mode) 8960 Units 28 Gbps 16GB GDDR7 256-bit DisplayPort x 3 (v2.1b) HDMI x 1 (As specified in HDMI 2.1b: up to 4K 480Hz o', '687141b40ca32.png', 100, 13000000),
(8, 'GEFORCE RTX 5070 Ti 16GB SHADOW 3X', 'Model Name : GeForce RTX™  Interface : PCI Express® Gen 4.0 x8 Cores : 2560 Units Core Clocks : Boost: 1777MHz Memory Speed : 14 Gbps Memory : 8GB GDDR6 Memory Bus : 128-bit Output : DisplayPort x 3 (v1.4a) HDMI x 1 (Supports 4K@120Hz as specified in HDMI', '687141fa2b031.png', 50, 17000000),
(9, 'GEFORCE RTX 5090 MASTER 32GB', 'Specifications :  Graphics Processing - GeForce RTX 5090  Core Clock - 2655 MHz (Reference card : 2407MHz)  CUDA Cores - 21760  Memory Clock - 28 Gbps  Memory Size - 32 GB  Memory Type - GDDR7  Memory Bus - 512 bit  Card Bus - PCI-E 5.0  Digital max resol', '6871420bc964e.png', 20, 55000000),
(11, 'GEFORCE RTX 3050 8GB VENTUS OC 2X', 'Model Name : GeForce RTX™ 3050 VENTUS 2X 8G Graphics Processing Unit : NVIDIA® GeForce RTX™ 3050 Interface : PCI Express® Gen 4.0 x8 Cores : 2560 Units Core Clocks : Boost: 1777MHz Memory Speed : 14 Gbps Memory : 8GB GDDR6 Memory Bus : 128-bit Output : Di', '687145a04b303.png', 1000, 5000000);

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `transaksi_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bank_id` int(11) DEFAULT NULL,
  `keranjang_grup` int(11) NOT NULL,
  `transaksi_alamat` varchar(255) DEFAULT NULL,
  `tanggal_transaksi` varchar(255) DEFAULT NULL,
  `status_pembayaran` int(1) DEFAULT 2,
  `bukti_pembayaran` varchar(256) NOT NULL DEFAULT '2',
  `total_pembayaran` varchar(256) NOT NULL DEFAULT '2',
  `catatan_pembayaran` text DEFAULT NULL,
  `tanggal_pembayaran` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_transaksi`
--

INSERT INTO `tb_transaksi` (`transaksi_id`, `user_id`, `bank_id`, `keranjang_grup`, `transaksi_alamat`, `tanggal_transaksi`, `status_pembayaran`, `bukti_pembayaran`, `total_pembayaran`, `catatan_pembayaran`, `tanggal_pembayaran`) VALUES
(43, 6, 2, 0, NULL, NULL, 1, 'payment_1752242022_6.jpg', '2', 'cepat ya kak', '2025-07-11 21:53:42'),
(44, 6, 1, 0, NULL, NULL, 1, 'payment_1752242097_6.jpg', '2', 'sadadwa', '2025-07-11 21:54:57'),
(45, 6, 2, 0, NULL, NULL, 0, '2', '2', NULL, NULL),
(46, 6, 1, 0, NULL, NULL, 1, 'payment_1752247512_6.jpg', '2', '', '2025-07-11 23:25:12'),
(47, 6, 1, 0, NULL, NULL, 1, 'payment_1752246484_6.jpg', '2', 'sadaw', '2025-07-11 23:08:04'),
(48, 6, 2, 0, NULL, NULL, 1, 'payment_1752249139_6.jpg', '2', '', '2025-07-11 23:52:19'),
(70, 6, 2, 0, NULL, NULL, 1, 'payment_1752251186_6.jpg', '2', '', '2025-07-12 00:26:26'),
(71, 6, 2, 65, 'asdawdasd', NULL, 0, '2', '2', NULL, NULL),
(72, 6, 2, 66, 'asdadwd', NULL, 1, 'payment_1752251683_6.jpg', '2', '', '2025-07-12 00:34:43'),
(73, 6, 1, 67, 'asdawdasd', '2025-07-12 00:37:11', 1, 'payment_1752251853_6.jpg', '2', '', '2025-07-12 00:37:33'),
(74, 6, 2, 68, 'wweeww', '2025-07-12 00:48:02', 1, 'payment_1752252492_6.png', '2', '', '2025-07-12 00:48:12'),
(75, 6, 2, 69, 'popperrr', '2025-07-12 00:51:11', 1, 'payment_1752252725_6.jpg', '2', '', '2025-07-12 00:52:05'),
(78, 7, 1, 73, 'moonstone ', '2025-07-12 01:14:00', 1, 'payment_1752254078_7.jpg', '2', 'dikirim cepat kak', '2025-07-12 01:14:38');

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` int(1) DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`user_id`, `username`, `fullname`, `password`, `role`) VALUES
(3, 'user', 'muhamad nizar', '$2y$10$AD85JaIX8eK1qCkVe32n6epjiimfvi1f2t0Qb3itZ0ddskIiXyzFC', 2),
(4, 'nizar', 'nizarrr ganteng', '$2y$10$pcLiOwnbVDvY3a9jya.2e.QxJPkGZ1tSUSgLzxq3FI8rmjSD.GwjS', 2),
(5, 'admin', 'administrator', '$2a$12$pOh9oFu0oCEaX7q7EcLpUOfu3CySHdiGd97viQ6jVfApLGtBgiC4S', 1),
(6, 'himuro', 'himuro szeto', '$2y$10$rM64XLqBtkztnOO2sbI37u4w7dvDei8ZysXyR24LYgvjygMm5IQx2', 2),
(7, 'anjaymabar', 'bapak yanto', '$2y$10$uoyPIacWQlUA7hUSI43b.OkdAkCib/e2VtbpAifpi2nkSMDTJMY76', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_bank`
--
ALTER TABLE `tb_bank`
  ADD PRIMARY KEY (`bank_id`);

--
-- Indexes for table `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  ADD PRIMARY KEY (`keranjang_id`),
  ADD KEY `FK_58e81b88-c1a7-4aed-87d8-a3b3a7f26b58` (`product_id`),
  ADD KEY `FK_c5cfab37-f97f-418b-843a-36d9fd87a70f` (`user_id`);

--
-- Indexes for table `tb_product`
--
ALTER TABLE `tb_product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`transaksi_id`),
  ADD KEY `FK_3d28adec-a61d-47ef-8de6-6f6e908db79b` (`user_id`),
  ADD KEY `FK_c517df8a-2e58-491f-802b-a338cae9abb5` (`bank_id`),
  ADD KEY `tb_transaksi_ibfk_1` (`keranjang_grup`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_bank`
--
ALTER TABLE `tb_bank`
  MODIFY `bank_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  MODIFY `keranjang_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `tb_product`
--
ALTER TABLE `tb_product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `transaksi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  ADD CONSTRAINT `FK_58e81b88-c1a7-4aed-87d8-a3b3a7f26b58` FOREIGN KEY (`product_id`) REFERENCES `tb_product` (`product_id`),
  ADD CONSTRAINT `FK_c5cfab37-f97f-418b-843a-36d9fd87a70f` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`user_id`);

--
-- Constraints for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `FK_3d28adec-a61d-47ef-8de6-6f6e908db79b` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`user_id`),
  ADD CONSTRAINT `FK_c517df8a-2e58-491f-802b-a338cae9abb5` FOREIGN KEY (`bank_id`) REFERENCES `tb_bank` (`bank_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
