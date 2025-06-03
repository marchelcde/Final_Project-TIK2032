
CREATE DATABASE IF NOT EXISTS aduan_masyarakat;
USE aduan_masyarakat;


CREATE TABLE IF NOT EXISTS reports (
    id VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telepon VARCHAR(20) NOT NULL,
    kategori ENUM('infrastruktur', 'lingkungan', 'sosial', 'ekonomi', 'lainnya') NOT NULL,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT NOT NULL,
    lokasi VARCHAR(200) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_kategori (kategori),
    INDEX idx_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(20) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    fullName VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    nik VARCHAR(16) UNIQUE,
    registeredDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_nik (nik),
    INDEX idx_role (role)
);

CREATE TABLE IF NOT EXISTS report_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id VARCHAR(20) NOT NULL,
    user_id VARCHAR(20) NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id),
    INDEX idx_created_at (created_at)
);


INSERT INTO users (id, username, password, email, fullName, role) VALUES 
('ADM001', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@gmail.com', 'Administrator', 'admin'),
('USR001', 'user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@gmail.com', 'User', 'user');


INSERT INTO reports (id, nama, email, telepon, kategori, judul, deskripsi, lokasi, status) VALUES 
('RPT001', 'Ahmad Wijaya', 'ahmad@email.com', '081234567890', 'infrastruktur', 'Jalan Rusak di Jl. Merdeka', 'Jalan berlubang besar yang membahayakan pengendara. Lubang dengan diameter sekitar 2 meter dan kedalaman 30cm.', 'Jl. Merdeka No. 45, Jakarta Pusat', 'pending'),

('RPT002', 'Siti Nurhaliza', 'siti@email.com', '082345678901', 'lingkungan', 'Sampah Menumpuk di TPS', 'TPS tidak dibersihkan selama seminggu, menimbulkan bau tidak sedap dan mengundang lalat.', 'TPS Kelurahan Menteng, Jakarta Pusat', 'in_progress'),

('RPT003', 'Budi Santoso', 'budi@email.com', '083456789012', 'sosial', 'Lampu Penerangan Jalan Mati', 'Lampu PJU mati total di sepanjang jalan sepanjang 500 meter, rawan kejahatan pada malam hari.', 'Jl. Sudirman Km 5, Jakarta Selatan', 'completed'),

('RPT004', 'Dewi Lestari', 'dewi@email.com', '084567890123', 'lingkungan', 'Polusi Suara dari Pabrik', 'Pabrik beroperasi 24 jam dengan suara mesin yang sangat bising, mengganggu istirahat warga.', 'Jl. Industri No. 12, Bekasi', 'pending'),

('RPT005', 'Rio Firmansyah', 'rio@email.com', '085678901234', 'infrastruktur', 'Drainase Tersumbat', 'Saluran air tersumbat sampah, menyebabkan banjir saat hujan deras.', 'Perumahan Griya Indah Blok C, Depok', 'rejected');


CREATE VIEW report_stats AS
SELECT 
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_reports,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reports,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reports
FROM reports;

CREATE VIEW category_stats AS
SELECT 
    kategori,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as completion_rate
FROM reports 
GROUP BY kategori;


DELIMITER //
CREATE PROCEDURE GetMonthlyStats(IN months_back INT)
BEGIN
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_reports,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reports,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports
    FROM reports 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL months_back MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC;
END//
DELIMITER ;


DELIMITER //
CREATE FUNCTION GenerateReportId() 
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
DELIMITER ;

DELIMITER //
CREATE TRIGGER before_insert_reports
BEFORE INSERT ON reports
FOR EACH ROW
BEGIN
    IF NEW.id IS NULL OR NEW.id = '' THEN
        SET NEW.id = GenerateReportId();
    END IF;
END//
DELIMITER ;


BEGIN
    -- Hapus komentar laporan yang terkait dengan laporan lama
    DELETE FROM report_comments
    WHERE report_id IN (
        SELECT id FROM reports
        WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY)
        AND status IN ('completed', 'rejected')
    );

    -- Hapus laporan lama dari tabel reports
    DELETE FROM reports
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY)
    AND status IN ('completed', 'rejected');
END

BEGIN
    DECLARE new_id VARCHAR(20);
    DECLARE id_exists INT DEFAULT 1;
    
    WHILE id_exists > 0 DO
        SET new_id = CONCAT('RPT', UNIX_TIMESTAMP(), FLOOR(RAND() * 1000));
        SELECT COUNT(*) INTO id_exists FROM reports WHERE id = new_id;
    END WHILE;
    
    RETURN new_id;
END


BEGIN
    DECLARE new_id VARCHAR(20);
    DECLARE id_exists INT DEFAULT 1;
    
    WHILE id_exists > 0 DO
        SET new_id = CONCAT('USR', UNIX_TIMESTAMP(), FLOOR(RAND() * 1000));
        SELECT COUNT(*) INTO id_exists FROM users WHERE id = new_id;
    END WHILE;
    
    RETURN new_id;
END

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
END

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
END

BEGIN
    SELECT 
        COUNT(*) as total_reports,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reports 
    WHERE user_id = user_id;
END