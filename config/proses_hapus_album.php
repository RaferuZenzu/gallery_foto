<?php
session_start();
include 'database.php';

if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'admin') {
    die("Akses ditolak!");
}

// Ambil data dari POST atau GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? '';
$foto_id = $_GET['id'] ?? '';

// 1. HAPUS FOTO SATUAN
if ($action == 'single' && !empty($foto_id)) {
    hapusDataLengkap($conn, $foto_id, 'foto');
} 
// 2. HAPUS ALBUM TERPILIH
elseif ($action == 'selected' && isset($_POST['album_ids'])) {
    foreach ($_POST['album_ids'] as $albumID) {
        $res = $conn->query("SELECT FotoID FROM foto WHERE AlbumID = $albumID");
        while($f = $res->fetch_assoc()) {
            hapusDataLengkap($conn, $f['FotoID'], 'foto');
        }
        $conn->query("DELETE FROM album WHERE AlbumID = $albumID");
    }
} 
// 3. HAPUS SEMUA KONTEN USER
elseif ($action == 'all' && !empty($user_id)) {
    $res = $conn->query("SELECT FotoID FROM foto WHERE UserID = $user_id");
    while($f = $res->fetch_assoc()) {
        hapusDataLengkap($conn, $f['FotoID'], 'foto');
    }
    $conn->query("DELETE FROM album WHERE UserID = $user_id");
}

function hapusDataLengkap($conn, $id, $type) {
    if ($type === 'foto') {
        $stmt = $conn->prepare("SELECT LokasiFile FROM foto WHERE FotoID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $f = $stmt->get_result()->fetch_assoc();

        if ($f) {
            $conn->query("DELETE FROM likefoto WHERE FotoID = $id");
            $conn->query("DELETE FROM komentarfoto WHERE FotoID = $id");
            
            $target_file = "../assets/uploads/" . $f['LokasiFile'];
            if (file_exists($target_file)) {
                unlink($target_file);
            }
            $conn->query("DELETE FROM foto WHERE FotoID = $id");
        }
    }
}

// Redirect kembali ke User Space target semula
header("Location: ../user_space.php?UserID=" . $user_id);
exit;
?>