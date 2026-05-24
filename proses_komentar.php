<?php
ob_start();
session_start();
include 'config/database.php';
error_reporting(0);
header('Content-Type: application/json');

if (isset($_POST['isi_komentar']) && isset($_SESSION['UserID'])) {
    $fotoID = $_POST['id'];
    $userID = $_SESSION['UserID'];
    $isi    = $_POST['isi_komentar']; // Tidak perlu real_escape_string lagi
    $tgl    = date('Y-m-d');
    
    // 1. PROSES INSERT AMAN
    $stmt = $conn->prepare("INSERT INTO komentarfoto (FotoID, UserID, IsiKomentar, TanggalKomentar) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $fotoID, $userID, $isi, $tgl);
    
    if ($stmt->execute()) {
        $newID = $stmt->insert_id;
        $stmt->close();

        // 2. AMBIL DATA USER (SELECT AMAN)
        $stmt_u = $conn->prepare("SELECT Username, FotoProfil FROM user WHERE UserID = ?");
        $stmt_u->bind_param("i", $userID);
        $stmt_u->execute();
        $res_u = $stmt_u->get_result();
        $u = $res_u->fetch_assoc();
        $stmt_u->close();

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'username' => $u['Username'],
            'isi' => htmlspecialchars($isi), // TETAP PAKAI INI UNTUK KEAMANAN TAMPILAN (XSS)
            'foto' => $u['FotoProfil'], 
            'userID_baru' => $userID,
            'komentarID_baru' => $newID 
        ]);
        exit;
    }
}

ob_clean();
echo json_encode(['status' => 'error', 'message' => 'Gagal simpan ke database']);