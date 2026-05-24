<?php
session_start();
include 'config/database.php';

if (isset($_GET['id']) && isset($_GET['foto'])) {
    $komentarID = $_GET['id'];
    $fotoID     = $_GET['foto'];
    $userID     = $_SESSION['UserID'];
    $role       = $_SESSION['Role'];

    // 1. CEK PEMILIK KOMENTAR (SELECT AMAN)
    $stmt_cek = $conn->prepare("SELECT UserID FROM komentarfoto WHERE KomentarID = ?");
    $stmt_cek->bind_param("i", $komentarID);
    $stmt_cek->execute();
    $result = $stmt_cek->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        // 2. LOGIKA IZIN HAPUS
        // ADMIN bisa hapus semua, USER cuma bisa hapus miliknya sendiri
        if ($role === 'admin' || $data['UserID'] == $userID) {
            
            // 3. PROSES HAPUS (DELETE AMAN)
            $stmt_delete = $conn->prepare("DELETE FROM komentarfoto WHERE KomentarID = ?");
            $stmt_delete->bind_param("i", $komentarID);
            
            if ($stmt_delete->execute()) {
                // Berhasil dihapus
            }
            $stmt_delete->close();
        }
    }
    
    // Pastikan statement cek ditutup juga
    $stmt_cek->close();

    // Balik ke halaman detail foto agar tidak blank!
    header("Location: detail.php?id=" . $fotoID);
    exit;
}
header("Location: index.php");
exit;