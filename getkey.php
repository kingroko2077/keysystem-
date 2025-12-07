<?php

error_reporting(E_ALL);

ini_set('display_errors', 1);

$host = "sql305.infinityfree.com";

$username = "if0_40606621";

$password = "0564551032";

$database = "if0_40606621_keysystem";

$conn = mysqli_connect($host, $username, $password, $database);

mysqli_set_charset($conn, "utf8");

function taoKey() {

    $part1 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 4);

    $part2 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 3);

    $part3 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 3);

    return "CossThha-$part1-$part2-$part3";

}

// Xử lý khi bấm GET KEY

if (isset($_GET['getkey'])) {

    $thekey = taoKey();

    $expires = time() + 86400;

    mysqli_query($conn, "INSERT INTO `keys`(`thekey`,`expires`) VALUES ('$thekey','$expires')");

    $id = mysqli_insert_id($conn);

    $realLink = "https://cossthha.gamer.gd/showkey.php?id=$id";

    $api_token = "89d4229ffa46cccd767e2bffb7c03153144fe01f8eb31137eea555e4e7b434e1";

    $api_url = "https://yeumoney.com/QL_api.php?token={$api_token}&url=" . urlencode($realLink) . "&format=json";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $api_url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    curl_close($ch);

    $result = json_decode($response, true);

    if (!$result || $result["status"] !== "success") {

        die("API LỖI: " . $response);

    }

    header("Location: " . $result["shortenedUrl"]);

    exit;

}

?>