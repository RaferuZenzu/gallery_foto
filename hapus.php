<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$type = $_GET['type'] ?? 'foto'; 
$userID = $_SESSION['UserID'];
$role = $_SESSION['Role'];

if ($type === 'album') {
    // --- LOGIKA HAPUS ALBUM ---
    // 1. Cek kepemilikan album
    $stmt_cek = $conn->prepare("SELECT * FROM album WHERE AlbumID = ?");
    $stmt_cek->bind_param("i", $id);
    $stmt_cek->execute();
    $result_album = $stmt_cek->get_result();
    $data_album = $result_album->fetch_assoc();

    if ($data_album && ($data_album['UserID'] == $userID || $role === 'admin')) {
        // 2. Amankan foto (Set AlbumID ke NULL)
        $stmt_upd = $conn->prepare("UPDATE foto SET AlbumID = NULL WHERE AlbumID = ?");
        $stmt_upd->bind_param("i", $id);
        $stmt_upd->execute();

        // 3. Hapus Album
        $stmt_del = $conn->prepare("DELETE FROM album WHERE AlbumID = ?");
        $stmt_del->bind_param("i", $id);
        
        if ($stmt_del->execute()) {
            echo "<script>alert('Album berhasil dihapus!'); window.location='album.php';</script>";
        }
    } else {
        echo "<script>alert('Akses Ditolak atau Album tidak ditemukan!'); window.location='album.php';</script>";
    }

} else {
    // --- LOGIKA HAPUS FOTO ---
    // 1. Ambil data foto untuk mendapatkan nama filenya
    $stmt_foto = $conn->prepare("SELECT * FROM foto WHERE FotoID = ?");
    $stmt_foto->bind_param("i", $id);
    $stmt_foto->execute();
    $dataFoto = $stmt_foto->get_result()->fetch_assoc();

    if ($dataFoto) {
        if ($role === 'admin' || $dataFoto['UserID'] == $userID) {
            // 2. Hapus File Fisik (Aset)
            $path = "assets/uploads/" . $dataFoto['LokasiFile'];
            if (file_exists($path)) { unlink($path); }

            // 3. Hapus Data dari Database
            $stmt_del_foto = $conn->prepare("DELETE FROM foto WHERE FotoID = ?");
            $stmt_del_foto->bind_param("i", $id);
            
            if ($stmt_del_foto->execute()) {
                echo "<script>alert('Foto dihapus!'); window.location='index.php';</script>";
            }
        } else {
            echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
        }
    } else {
        echo "<script>alert('Foto tidak ditemukan!'); window.location='index.php';</script>";
    }
}
?>