<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$fotoID = $_GET['id'];
$userID = $_SESSION['UserID'];
$tgl    = date('Y-m-d');

// 1. CEK APAKAH USER SUDAH LIKE (SELECT AMAN)
$stmt_cek = $conn->prepare("SELECT * FROM likefoto WHERE FotoID = ? AND UserID = ?");
$stmt_cek->bind_param("ii", $fotoID, $userID);
$stmt_cek->execute();
$cek = $stmt_cek->get_result();

if ($cek->num_rows > 0) {
    // 2. JIKA SUDAH ADA, HAPUS (DELETE AMAN)
    $stmt_unlike = $conn->prepare("DELETE FROM likefoto WHERE FotoID = ? AND UserID = ?");
    $stmt_unlike->bind_param("ii", $fotoID, $userID);
    $stmt_unlike->execute();
    $stmt_unlike->close();
} else {
    // 3. JIKA BELUM, TAMBAH (INSERT AMAN)
    // Sebaiknya sebutkan nama kolomnya agar lebih aman jika struktur tabel berubah
    $stmt_like = $conn->prepare("INSERT INTO likefoto (FotoID, UserID, TanggalLike) VALUES (?, ?, ?)");
    $stmt_like->bind_param("iis", $fotoID, $userID, $tgl);
    $stmt_like->execute();
    $stmt_like->close();
}

$stmt_cek->close();

header("Location: detail.php?id=" . $fotoID);
exit;
?>