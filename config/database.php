<?php
$host = "";
$user = "";
$pass = "";
$db   = "";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

function getVerificationStatus($user) {
    // 1. Jika Role adalah admin, WAJIB centang merah (Exclusive)
    if ($user['Role'] === 'admin') {
        return 'admin'; // verif2.png
    }
    
    // 2. Jika user punya kolom IsVerified (pemberian admin manual) 
    // atau memenuhi syarat 10jt follower & 1jt like
    if (
        (isset($user['IsVerified']) && $user['IsVerified'] == 1) || 
        ($user['jml_follower'] >= 10000000 && $user['jml_like'] >= 1000000)
    ) {
        return 'verified'; // verif1.png (Tetap Ungu sesuai request)
    }
    
    return 'none';
}
?>
