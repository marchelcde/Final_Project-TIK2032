<?php
include 'includes/header.php';
include 'includes/footer.php';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLM - Sistem Laporan Masyarakat</title>
    <link rel="stylesheet" href="../css/style.css"> 
</head>
<body data-logged-in = "<?php echo $isLoggedIn ? 'true' : 'false'; ?>">
    <main class="container">
        <section class="hero">
            <h2>Selamat Datang di Sistem Laporan Masyarakat</h2>
            <p>Laporkan masalah atau berikan aspirasi Anda untuk komunitas yang lebih baik. Kami siap mendengarkan dan menindaklanjuti.</p>
            <a href="laporan.php"  class="button require-login">Buat Laporan Sekarang!</a>
        </section>

        <section class="features">
            <h3>Mengapa Menggunakan SLM?</h3>
            <article>
                <h4>Mudah & Cepat</h4>
                <p>Sampaikan laporan Anda hanya dalam beberapa langkah sederhana.</p>
            </article>
            <article>
                <h4>Transparan</h4>
                <p>Lacak status laporan Anda secara online kapan saja.</p>
            </article>
            <article>
                <h4>Responsif</h4>
                <p>Laporan Anda akan diterima dan ditangani oleh pihak berwenang.</p>
            </article>
        </section>

        <section class="stats">
            <h3>Statistik Laporan</h3>
            <p>Total Laporan Masuk: <strong>150</strong></p>
            <p>Laporan Diproses: <strong>75</strong></p>
            <p>Laporan Selesai: <strong>60</strong></p>
        </section>
    </main>
    <div id="popup-overlay"></div> 
    <div id="popup-container">
        </div>
    <script src="../js/script.js"></script> 

</body>
</html>