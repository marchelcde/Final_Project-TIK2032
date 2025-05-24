<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu untuk mengakses halaman laporan.";

    header('Location: login.php');
    exit(); 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLM - Buat Laporan Baru</title>
</head>
<body>
    <header>
        </header>

    <main class="container">
        <h2>Buat Laporan Baru</h2>
        <p>Silakan isi formulir di bawah ini dengan lengkap dan benar.</p>
        
        <form action="proses_laporan.php" method="POST"> 
            <div class="form-group">
                <label for="nama_pelapor">Nama Anda (Opsional):</label>
                <input type="text" id="nama_pelapor" name="nama_pelapor">
            </div>

            <div class="form-group">
                <label for="email_pelapor">Email Anda (Wajib untuk notifikasi):</label>
                <input type="email" id="email_pelapor" name="email_pelapor" required>
            </div>

            <div class="form-group">
                <label for="judul_laporan">Judul Laporan:</label>
                <input type="text" id="judul_laporan" name="judul_laporan" required>
            </div>

            <div class="form-group">
                <label for="id_kategori">Kategori Laporan:</label>
                <select id="id_kategori" name="id_kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="1">Kerusakan Jalan</option>
                    <option value="2">Sampah</option>
                    <option value="3">Penerangan Jalan</option>
                    <option value="4">Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label for="lokasi_kejadian">Lokasi Kejadian/Masalah:</label>
                <textarea id="lokasi_kejadian" name="lokasi_kejadian" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label for="deskripsi_laporan">Deskripsi Lengkap:</label>
                <textarea id="deskripsi_laporan" name="deskripsi_laporan" rows="6" required></textarea>
            </div>

            <div class="form-group">
                <label for="foto_bukti">Upload Foto Bukti (Opsional):</label>
                <input type="file" id="foto_bukti" name="foto_bukti" accept="image/*">
            </div>

            <button type="submit">Kirim Laporan</button>
        </form>
        <button type = "button" onclick="window.location.href='../index.php'">Kembali ke Halaman Utama</button></p>
    </main>

    <footer>
        </footer>
</body>
</html>