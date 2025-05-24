<?php
// admin/dashboard.php
// Mulai session dan cek login
session_start(); 
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include '../config/database.php';
// Include header admin atau header biasa + cek login
// Di sini kita pakai header biasa dan tambahkan auth_check.php
// atau buat admin_header.php
// include '../includes/auth_check.php'; // Pastikan isinya sesuai
include '../includes/header.php'; // Ganti dengan admin_header jika ada

echo "<h3>Selamat Datang, " . htmlspecialchars($_SESSION['admin_username']) . "! | <a href='logout.php'>Logout</a></h3>";
echo "<h2>Dashboard Laporan Masyarakat</h2>";

// Ambil data laporan dari DB
$sql = "SELECT l.id_laporan, l.kode_unik_laporan, l.judul_laporan, k.nama_kategori, l.tanggal_dilaporkan, l.status_laporan 
        FROM laporan_masyarakat l 
        LEFT JOIN kategori_laporan k ON l.id_kategori = k.id_kategori 
        ORDER BY l.tanggal_dilaporkan DESC";
$result = $conn->query($sql);

?>

<table>
    <thead>
        <tr>
            <th>Kode Laporan</th>
            <th>Judul</th>
            <th>Kategori</th>
            <th>Tanggal Lapor</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['kode_unik_laporan']) . "</td>";
                echo "<td>" . htmlspecialchars($row['judul_laporan']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nama_kategori']) . "</td>";
                echo "<td>" . htmlspecialchars($row['tanggal_dilaporkan']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status_laporan']) . "</td>";
                echo "<td>
                        <a href='laporan_detail.php?id=" . $row['id_laporan'] . "'>Detail</a> 
                        </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>Tidak ada laporan saat ini.</td></tr>";
        }
        ?>
    </tbody>
</table>

<?php
$conn->close();
include '../includes/footer.php';
?>