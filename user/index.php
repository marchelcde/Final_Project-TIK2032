<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laporan Aduan Masyarakat</title>
    <link rel="stylesheet" href="shared/css/style.css" />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
      rel="stylesheet"
    />
  </head>
  <body>
    <nav class="navbar">
      <div class="nav-container">
        <div class="nav-logo">
          <h2>Aduan Masyarakat</h2>
        </div>
        <ul class="nav-menu">
          <li class="nav-item">
            <a href="index.html" class="nav-link active">Home</a>
          </li>
          <li class="nav-item">
            <a href="about.php" class="nav-link">About</a>
          </li>
          <li class="nav-item">
            <a href="login.html" class="nav-link">Login</a>
          </li>
          <li class="nav-item">
            <a href="register.html" class="nav-link">Register</a>
          </li>
        </ul>
        <div class="hamburger">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </div>
      </div>
    </nav>

    <main>
      <section class="hero">
        <div class="container">
          <h1>Sistem Laporan Aduan Masyarakat</h1>
          <p>
            Laporkan keluhan dan saran Anda untuk pembangunan yang lebih baik
          </p>
          <a href="user/laporan.php" class="btn btn-primary">Buat Laporan</a>
        </div>
      </section>

      <section id="laporan" class="form-section">
        <div class="container">
          <h2>Form Laporan Aduan</h2>
          <form id="reportForm" class="report-form">
            <div class="form-group">
              <label for="nama">Nama Lengkap</label>
              <input type="text" id="nama" name="nama" required />
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" required />
            </div>

            <div class="form-group">
              <label for="telepon">No. Telepon</label>
              <input type="tel" id="telepon" name="telepon" required />
            </div>

            <div class="form-group">
              <label for="kategori">Kategori Aduan</label>
              <select id="kategori" name="kategori" required>
                <option value="">Pilih Kategori</option>
                <option value="infrastruktur">Infrastruktur</option>
                <option value="lingkungan">Lingkungan</option>
                <option value="sosial">Sosial</option>
                <option value="ekonomi">Ekonomi</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>

            <div class="form-group">
              <label for="judul">Judul Aduan</label>
              <input type="text" id="judul" name="judul" required />
            </div>

            <div class="form-group">
              <label for="deskripsi">Deskripsi Aduan</label>
              <textarea
                id="deskripsi"
                name="deskripsi"
                rows="5"
                required
              ></textarea>
            </div>

            <div class="form-group">
              <label for="lokasi">Lokasi Kejadian</label>
              <input type="text" id="lokasi" name="lokasi" required />
            </div>

            <button type="submit" class="btn btn-primary">Kirim Laporan</button>
          </form>
        </div>
      </section>

      <section class="features">
        <div class="container">
          <h2>Mengapa Menggunakan Sistem Ini?</h2>
          <div class="features-grid">
            <div class="feature-card">
              <i class="fas fa-fast-forward"></i>
              <h3>Cepat & Mudah</h3>
              <p>Proses pelaporan yang simpel dan tidak ribet</p>
            </div>
            <div class="feature-card">
              <i class="fas fa-shield-alt"></i>
              <h3>Aman & Terpercaya</h3>
              <p>Data Anda akan dijaga kerahasiaannya</p>
            </div>
            <div class="feature-card">
              <i class="fas fa-clock"></i>
              <h3>Respon Cepat</h3>
              <p>Tim kami akan merespon laporan Anda dengan segera</p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer>
      <div class="container">
        <p>&copy; 2025 Sistem Laporan Aduan Masyarakat. All rights reserved.</p>
      </div>
    </footer>

    <script src="shared/js/script.js"></script>
  </body>
</html>
