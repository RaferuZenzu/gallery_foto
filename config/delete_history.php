<?php
include 'database.php';
session_start();

$id = $_GET['id'] ?? 0;
$u_id = $_SESSION['UserID'] ?? 0;

if ($id && $u_id) {
    $query = mysqli_query($conn, "DELETE FROM search_history WHERE SearchID = '$id' AND UserID = '$u_id'");
    echo json_encode(['success' => $query]);
} else {
    echo json_encode(['success' => false]);
}