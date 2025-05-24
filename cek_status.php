<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLM - Cek Status Laporan</title>
</head>
<body>
    <header>
        </header>

    <main class="container">
        <h2>Cek Status Laporan Anda</h2>
        <p>Masukkan Kode Unik Laporan yang Anda terima saat membuat laporan.</p>
        
        <form action="hasil_cek_status.php" method="GET"> 
            <div class="form-group">
                <label for="kode_laporan">Kode Laporan:</label>
                <input type="text" id="kode_laporan" name="kode_laporan" placeholder="Contoh: SLM-123456789" required>
            </div>
            <button type="submit">Cek Status</button>
        </form>
    </main>

    <footer>
        </footer>
</body>
</html>