SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `e-lapor` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `e-lapor`;

CREATE TABLE `pengguna_admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pengguna_admin` (`id_admin`, `username`, `password`, `email`, `full_name`, `created_at`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@elapor.com', 'Administrator', '2025-05-26 22:00:47', 'active'),
(2, 'super_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin@elapor.com', 'Super Administrator', '2025-05-26 22:45:45', 'active');

CREATE TABLE `users` (
  `id` varchar(20) NOT NULL,
  `fullName` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `registeredDate` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `fullName`, `username`, `email`, `password`, `phone`, `address`, `nik`, `registeredDate`, `created_at`, `role`, `status`) VALUES
('USR001', 'Keefa Lasut', 'Keefa', 'kenola700@gmail.com', '$2y$10$saNRvP4xRfBHvHaXJpV9muSkWnsnc9E5Ek5dDyjyj6SMgtuinjzDu', '081234567890', 'Jl. Contoh No. 1', '1234567890123456', NOW(), '2025-05-24 19:17:29', 'user', 'active'),
('USR002', 'Marchel Manullang', 'Lerch', 'mrchl@gmail.com', '$2y$10$Ut2NdgLFXs9xv2rmBcUQ6uDdzhoXE6oKYtHM7wJFdlcPo.pWuXEc6', '082345678901', 'Jl. Contoh No. 2', '1234567890123457', NOW(), '2025-05-24 19:26:41', 'user', 'active'),
('USR003', 'Valen Tino', 'Tino', 'insomniac@gmail.com', '$2y$10$F.UVaHFCGH1ym5iv35cWOORuGfYGrtCtT1b2PuP6LQ2qPK/uTyGpu', '083456789012', 'Jl. Contoh No. 3', '1234567890123458', NOW(), '2025-05-24 19:40:38', 'user', 'active'),
('USR004', 'Joka', 'Joka', 'joka@gmail.com', '$2y$10$wd18RU.5FrTvn5.RcFNPAOW3zruGHRRlbfTPZnQWDgWiWAKZcYJuS', '084567890123', 'Jl. Contoh No. 4', '1234567890123459', NOW(), '2025-05-24 20:08:52', 'user', 'active'),
('USR005', 'Claisty Cazzy', 'Cazzy', 'cazzy@gmail.com', '$2y$10$4d.jYyOrl0lsqrOof/wKnOirQWN8Te8Q8bjx7LxckHJVS4vtXZYwe', '085678901234', 'Jl. Contoh No. 5', '1234567890123460', NOW(), '2025-05-26 01:25:47', 'user', 'active'),
('ADM001', 'Admin User', 'admin_user', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081111111111', 'Kantor Admin', '9999999999999999', NOW(), '2025-05-26 01:25:47', 'admin', 'active');


CREATE TABLE `reports` (
  `id` varchar(20) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telepon` varchar(20) NOT NULL,
  `kategori` enum('infrastruktur','lingkungan','sosial','ekonomi','lainnya') NOT NULL,
  `judul` varchar(200) NOT NULL,
  `deskripsi` text NOT NULL,
  `lokasi` varchar(200) NOT NULL,
  `status` enum('pending','in_progress','completed','rejected') DEFAULT 'pending',
  `feedback_admin` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `foto_bukti` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reports` (`id`, `user_id`, `nama`, `email`, `telepon`, `kategori`, `judul`, `deskripsi`, `lokasi`, `status`, `feedback_admin`, `created_at`, `updated_at`, `foto_bukti`) VALUES
('RPT001', 'USR001', 'Ahmad Wijaya', 'ahmad@email.com', '081234567890', 'infrastruktur', 'Jalan Rusak di Jl. Merdeka', 'Jalan berlubang besar yang membahayakan pengendara. Lubang dengan diameter sekitar 2 meter dan kedalaman 30cm.', 'Jl. Merdeka No. 45, Jakarta Pusat', 'pending', NULL, '2025-05-30 06:38:19', '2025-05-30 06:38:19', NULL),
('RPT002', 'USR002', 'Siti Nurhaliza', 'siti@email.com', '082345678901', 'lingkungan', 'Sampah Menumpuk di TPS', 'TPS tidak dibersihkan selama seminggu, menimbulkan bau tidak sedap dan mengundang lalat.', 'TPS Kelurahan Menteng, Jakarta Pusat', 'in_progress', 'Laporan sedang ditindaklanjuti oleh Dinas Kebersihan.', '2025-05-30 06:38:19', '2025-05-30 06:38:19', NULL),
('RPT003', 'USR003', 'Budi Santoso', 'budi@email.com', '083456789012', 'sosial', 'Lampu Penerangan Jalan Mati', 'Lampu PJU mati total di sepanjang jalan sepanjang 500 meter, rawan kejahatan pada malam hari.', 'Jl. Sudirman Km 5, Jakarta Selatan', 'completed', 'Lampu PJU telah diperbaiki dan menyala normal.', '2025-05-31 08:16:29', '2025-05-31 08:16:29', NULL),
('RPT004', 'USR004', 'Dewi Lestari', 'dewi@email.com', '084567890123', 'lingkungan', 'Polusi Suara dari Pabrik', 'Pabrik beroperasi 24 jam dengan suara mesin yang sangat bising, mengganggu istirahat warga.', 'Jl. Industri No. 12, Bekasi', 'pending', NULL, '2025-05-30 06:38:19', '2025-05-30 06:38:19', NULL),
('RPT005', 'USR005', 'Rio Firmansyah', 'rio@email.com', '085678901234', 'infrastruktur', 'Drainase Tersumbat', 'Saluran air tersumbat sampah, menyebabkan banjir saat hujan deras.', 'Perumahan Griya Indah Blok C, Depok', 'rejected', 'Laporan tidak sesuai dengan prosedur pelaporan. Mohon melampirkan foto bukti yang jelas.', '2025-05-30 06:38:19', '2025-06-01 06:15:10', NULL),
('RPT006', 'USR001', 'Keefa Lasut', 'kenola700@gmail.com', '081234567890', 'infrastruktur', 'Kerusakan Parah Jalan di Malalayang', 'Saya ingin melaporkan kondisi jalan di area Malalayang yang sudah mengalami kerusakan parah dan sangat membahayakan keselamatan para pengguna jalan, baik pengendara roda dua maupun roda empat.', 'Malalayang, Manado', 'completed', 'Laporan telah ditindaklanjuti dan perbaikan jalan telah selesai dilakukan.', '2025-06-01 06:05:41', '2025-06-01 06:06:46', NULL);

CREATE TABLE `report_comments` (
  `id` int(11) NOT NULL,
  `report_id` varchar(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `report_comments` (`id`, `report_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 'RPT001', 'USR001', 'Mohon segera ditindaklanjuti karena kondisi semakin parah.', '2025-05-31 10:30:00'),
(2, 'RPT002', 'ADM001', 'Laporan telah diteruskan ke Dinas Kebersihan untuk ditindaklanjuti.', '2025-05-31 11:00:00'),
(3, 'RPT003', 'USR003', 'Terima kasih atas tindak lanjutnya. Lampu sudah menyala kembali.', '2025-06-01 08:00:00');

CREATE VIEW `report_stats` AS
SELECT 
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_reports,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reports,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reports
FROM reports;

CREATE VIEW `category_stats` AS
SELECT 
    kategori,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as completion_rate
FROM reports 
GROUP BY kategori;

CREATE VIEW `monthly_report_stats` AS
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reports,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_reports,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reports
FROM reports 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month DESC;

ALTER TABLE `pengguna_admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_status` (`status`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_email` (`email`);

ALTER TABLE `report_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_id` (`report_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

ALTER TABLE `pengguna_admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `report_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `report_comments`
  ADD CONSTRAINT `fk_comments_report_id` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


DELIMITER //

CREATE FUNCTION `GenerateReportId`() 
RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE new_id VARCHAR(20);
    DECLARE id_exists INT DEFAULT 1;
    
    WHILE id_exists > 0 DO
        SET new_id = CONCAT('RPT', UNIX_TIMESTAMP(), FLOOR(RAND() * 1000));
        SELECT COUNT(*) INTO id_exists FROM reports WHERE id = new_id;
    END WHILE;
    
    RETURN new_id;
END//

CREATE FUNCTION `GenerateUserId`() 
RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE new_id VARCHAR(20);
    DECLARE id_exists INT DEFAULT 1;
    
    WHILE id_exists > 0 DO
        SET new_id = CONCAT('USR', UNIX_TIMESTAMP(), FLOOR(RAND() * 1000));
        SELECT COUNT(*) INTO id_exists FROM users WHERE id = new_id;
    END WHILE;
    
    RETURN new_id;
END//

CREATE PROCEDURE `GetMonthlyStats`(IN months_back INT)
BEGIN
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_reports,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reports,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_reports,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reports
    FROM reports 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL months_back MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC;
END//

CREATE PROCEDURE `GetCategoryStatistics`()
BEGIN
    SELECT 
        kategori,
        COUNT(*) as total_reports,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as completion_rate
    FROM reports 
    GROUP BY kategori
    ORDER BY total_reports DESC;
END//

CREATE PROCEDURE `GetUserReportSummary`(IN user_id VARCHAR(20))
BEGIN
    SELECT 
        COUNT(*) as total_reports,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reports 
    WHERE user_id = user_id;
END//

CREATE PROCEDURE `CleanupOldReports`(IN days_old INT)
BEGIN
    DELETE FROM report_comments 
    WHERE report_id IN (
        SELECT id FROM reports 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY) 
        AND status IN ('completed', 'rejected')
    );
    
    DELETE FROM reports 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY) 
    AND status IN ('completed', 'rejected');
END//

DELIMITER ;


DELIMITER //

CREATE TRIGGER `before_insert_reports`
BEFORE INSERT ON `reports`
FOR EACH ROW
BEGIN
    IF NEW.id IS NULL OR NEW.id = '' THEN
        SET NEW.id = GenerateReportId();
    END IF;
END//

CREATE TRIGGER `before_insert_users`
BEFORE INSERT ON `users`
FOR EACH ROW
BEGIN
    IF NEW.id IS NULL OR NEW.id = '' THEN
        SET NEW.id = GenerateUserId();
    END IF;
END//

CREATE TRIGGER `after_update_report_status`
AFTER UPDATE ON `reports`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO report_comments (report_id, user_id, comment, created_at)
        VALUES (NEW.id, 'SYSTEM', CONCAT('Status changed from ', OLD.status, ' to ', NEW.status), NOW());
    END IF;
END//

DELIMITER ;

COMMIT;

