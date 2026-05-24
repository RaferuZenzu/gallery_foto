<?php
include 'database.php';
session_start();

$q = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$current_user = isset($_SESSION['UserID']) ? $_SESSION['UserID'] : 0;

$response = [
    'keywords' => [],
    'users' => []
];

if ($q !== '') {
    // Ambil data visual secara global berdasarkan kecocokan karakter, gacha by Like
    $f_query = "SELECT foto.JudulFoto, COUNT(likefoto.LikeID) as TotalLike 
                FROM foto 
                LEFT JOIN likefoto ON foto.FotoID = likefoto.FotoID 
                WHERE foto.JudulFoto LIKE '%$q%' 
                GROUP BY foto.FotoID 
                ORDER BY TotalLike DESC, foto.JudulFoto ASC";
                
    $f_res = mysqli_query($conn, $f_query);
    while($row = mysqli_fetch_assoc($f_res)) {
        $response['keywords'][] = $row['JudulFoto'];
    }

    // Ambil data user/kreator
    $u_query = "SELECT UserID, Username, FotoProfil, Role FROM user 
                WHERE Username LIKE '%$q%' AND UserID != '$current_user' 
                ORDER BY Username ASC LIMIT 3";
                
    $u_res = mysqli_query($conn, $u_query);
    while($row = mysqli_fetch_assoc($u_res)) {
        $response['users'][] = [
            'id' => $row['UserID'],
            'name' => $row['Username'],
            'img' => $row['FotoProfil'] ? $row['FotoProfil'] : 'default.png',
            'role' => $row['Role']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);