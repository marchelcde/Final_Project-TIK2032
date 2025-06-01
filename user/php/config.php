<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'aduan_masyarakat');
define('DB_USER', 'root');
define('DB_PASS', 'kenola20');

define('APP_NAME', 'Sistem Laporan Aduan Masyarakat');
define('APP_URL', 'http://localhost/web');
define('UPLOAD_PATH', 'uploads/');

session_start();

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                  $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // CHANGE THIS LINE: Throw an exception instead of echoing
            throw new Exception("Database connection error: " . $exception->getMessage(), 0, $exception);
        }
        return $this->conn;
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateId($prefix = 'RPT') {
    return $prefix . time() . rand(100, 999);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function getStatusText($status) {
    $statusMap = [
        'pending' => 'Menunggu',
        'in_progress' => 'Diproses',
        'completed' => 'Selesai',
        'rejected' => 'Ditolak'
    ];
    return $statusMap[$status] ?? $status;
}

function getCategoryText($category) {
    $categoryMap = [
        'infrastruktur' => 'Infrastruktur',
        'lingkungan' => 'Lingkungan',
        'sosial' => 'Sosial',
        'ekonomi' => 'Ekonomi',
        'lainnya' => 'Lainnya'
    ];
    return $categoryMap[$category] ?? $category;
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.html');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.html');
        exit;
    }
}
?>