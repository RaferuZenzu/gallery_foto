<?php
session_start();
include 'database.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php"); exit;
}

$follower_id = $_SESSION['UserID'];
$following_id = $_GET['target'];

// Jangan biarkan follow diri sendiri
if ($follower_id == $following_id) {
    header("Location: ../user_space.php?UserID=$following_id"); exit;
}

// Cek apakah sudah follow
$check = $conn->prepare("SELECT * FROM follow WHERE FollowerID = ? AND FollowingID = ?");
$check->bind_param("ii", $follower_id, $following_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // Unfollow
    $stmt = $conn->prepare("DELETE FROM follow WHERE FollowerID = ? AND FollowingID = ?");
} else {
    // Follow
    $stmt = $conn->prepare("INSERT INTO follow (FollowerID, FollowingID, TanggalFollow) VALUES (?, ?, NOW())");
}

$stmt->bind_param("ii", $follower_id, $following_id);
$stmt->execute();

header("Location: ../user_space.php?UserID=$following_id");
exit;