<?php
session_start();

// Menghapus semua data session yang ada di memori server
$_SESSION = array();

// Jika ingin benar-benar menghapus cookie session di browser user (opsional tapi pro)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Menghancurkan session
session_destroy();

// Balik ke index
header("Location: index.php");
exit;