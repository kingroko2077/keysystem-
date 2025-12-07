<?php

error_reporting(E_ALL);

ini_set('display_errors', 1);

// ================== CONFIG DATABASE ==================

$host = "sql305.infinityfree.com";

$username = "if0_40606621";

$password = "0564551032";

$database = "if0_40606621_keysystem";

$conn = mysqli_connect($host, $username, $password, $database);

mysqli_set_charset($conn, "utf8");

if (!$conn) {

    http_response_code(500);

    echo "Lỗi kết nối database.";

    exit;

}

// ================== HÀM TẠO KEY ==================

function taoKey() {

    $part1 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 4);

    $part2 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 3);

    $part3 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 3);

    return "CossThha-$part1-$part2-$part3";

}

// ================== HÀM GỌI API YEUMONEY (cURL rồi fallback) ==================

function yeumoney_shorten($targetUrl, $api_token) {

    $api_url = "https://yeumoney.com/QL_api.php?token=" . urlencode($api_token) . "&url=" . urlencode($targetUrl) . "&format=json";

    // try cURL

    if (function_exists('curl_init')) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $api_url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($ch);

        $err = curl_error($ch);

        curl_close($ch);

        if ($resp !== false && $resp !== '') {

            $j = json_decode($resp, true);

            return $j ?: ['error' => 'invalid_json', 'raw' => $resp];

        } else {

            return ['error' => 'curl_error', 'msg' => $err ?: 'empty response'];

        }

    }

    // fallback file_get_contents (if allow_url_fopen)

    if (ini_get('allow_url_fopen')) {

        $resp = @file_get_contents($api_url);

        if ($resp !== false) {

            $j = json_decode($resp, true);

            return $j ?: ['error' => 'invalid_json', 'raw' => $resp];

        } else {

            return ['error' => 'fopen_error'];

        }

    }

    return ['error' => 'no_method', 'msg' => 'cURL not available and allow_url_fopen disabled'];

}

// ================== XỬ LÝ GET KEY ==================

if (isset($_GET['getkey'])) {

    // tạo key + lưu DB

    $thekey = taoKey();

    $expires = time() + 24 * 60 * 60;

    $stmt = mysqli_prepare($conn, "INSERT INTO `keys` (`thekey`, `expires`) VALUES (?, ?)");

    if ($stmt) {

        mysqli_stmt_bind_param($stmt, "si", $thekey, $expires);

        mysqli_stmt_execute($stmt);

        $id = mysqli_insert_id($conn);

        mysqli_stmt_close($stmt);

    } else {

        http_response_code(500);

        echo "Lỗi DB khi lưu key.";

        exit;

    }

    // link thực để show key

    $realLink = "https://" . $_SERVER['HTTP_HOST'] . "/showkey.php?id=" . urlencode($id);

    // gọi YeuMoney

    $api_token = "89d4229ffa46cccd767e2bffb7c03153144fe01f8eb31137eea555e4e7b434e1";

    $result = yeumoney_shorten($realLink, $api_token);

    if (isset($result['status']) && $result['status'] === 'success' && !empty($result['shortenedUrl'])) {

        $shortURL = $result['shortenedUrl'];

        header("Location: $shortURL");

        exit;

    } else {

        // show error trang (để debug): hiển thị message thân thiện

        http_response_code(502);

        echo "<h2>Lỗi rút gọn link YeuMoney</h2><pre>";

        echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo "</pre><p>Link thực (bạn có thể truy cập trực tiếp): <a href=\"" . htmlspecialchars($realLink) . "\">" . htmlspecialchars($realLink) . "</a></p>";

        exit;

    }

}

?>

<!doctype html>

<html lang="vi">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>CossThha Key System</title>

<style>

    :root{

        --menu-w: 46%;

        --menu-bg: rgba(20,20,20,0.95);

    }

    html,body{height:100%;margin:0;font-family:Arial,Helvetica,sans-serif;background:#000}

    body{

        background-image: url('background.jpg');

        background-size:cover;

        background-position:center;

        background-attachment:fixed;

    }

    /* full overlay blur & center box */

    .overlay{

        backdrop-filter: blur(6px);

        width:100%;

        min-height:100vh;

        display:flex;

        align-items:center;

        justify-content:center;

        padding:40px 20px;

        box-sizing:border-box;

    }

    .box{

        width: min(860px, 92%);

        background: rgba(0,0,0,0.6);

        border-radius:18px;

        padding:30px;

        color:#fff;

        box-shadow: 0 10px 40px rgba(0,0,0,0.6);

        position:relative;

    }

    .box h1{margin:0 0 18px;font-size:32px;letter-spacing:1px;text-align:center}

    .btn{

        display:block;

        width:100%;

        padding:16px;

        background:#00c3ff;

        color:#000;

        border-radius:12px;

        border:0;

        font-weight:700;

        font-size:18px;

        cursor:pointer;

        text-align:center;

    }

    .btn:active{transform:translateY(1px)}

    /* music control */

    .music-panel{margin-top:18px;background:rgba(0,0,0,0.45);padding:14px;border-radius:12px}

    .music-row{display:flex;align-items:center;gap:12px}

    .music-row button{padding:8px 12px;border-radius:8px;border:none;cursor:pointer}

    .vol{flex:1;display:flex;align-items:center;gap:8px;color:#ddd}

    .vol input[type=range]{width:100%}

    .status{font-weight:700;margin-left:8px}

    /* floating menu (big) */

    .menu-toggle{

        position:fixed;left:18px;top:18px;z-index:9999;

        background:rgba(0,0,0,0.6);color:#fff;padding:12px;border-radius:12px;cursor:pointer;

        box-shadow:0 6px 18px rgba(0,0,0,0.4);font-size:28px

    }

    .sidebar{

        position:fixed;left:0;top:0;bottom:0;width:var(--menu-w);

        max-width:420px;background:var(--menu-bg);color:#fff;padding:40px 28px;box-sizing:border-box;

        transform:translateX(-110%);transition:transform .28s ease;z-index:9998;

    }

    .sidebar.open{transform:translateX(0)}

    .sidebar .title{font-size:32px;font-weight:800;margin-bottom:24px}

    .sidebar a{display:block;color:#fff;text-decoration:none;padding:12px 8px;border-radius:8px;margin-bottom:10px;font-size:20px;background:rgba(255,255,255,0.02)}

    .sidebar a:hover{background:rgba(255,255,255,0.04);}

    /* overlay when menu open */

    .menu-overlay{position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,0.35);opacity:0;transition:opacity .2s;z-index:9997;pointer-events:none}

    .menu-overlay.show{opacity:1;pointer-events:auto}

    /* responsive */

    @media(min-width:900px){

        :root{--menu-w: 380px}

        .box{width:760px}

    }

    @media(max-width:420px){

        :root{--menu-w: 70%}

        .box{padding:18px}

        .box h1{font-size:24px}

    }

</style>

</head>

<body>

<!-- MENU TO -->

<div class="menu-toggle" id="menuToggle" onclick="toggleMenu()">☰</div>

<div class="sidebar" id="sidebar">

    <div class="title">MENU</div>

    <li>

    <a href="/">

        <img src="/assets/icons/home.png?v=2" width="26"

        style="margin-right:8px;vertical-align:middle;">

        Trang chủ

    </a>

</li>

<li>

    <a href="login.php">

        <img src="/login.png?v=4" width="26"

        style="margin-right:8px;vertical-align:middle;">

        Đăng nhập

    </a>

</li>

<li>

    <a href="?getkey=1">

        <img src="/assets/icons/key.png?v=2" width="26"

        style="margin-right:8px;vertical-align:middle;">

        Get key

    </a>

</li>

<li>

    <a href="https://youtube.com/@kirbynamsicv" target="_blank">

        <img src="/assets/icons/youtube.png" width="26"

        style="margin-right:8px;vertical-align:middle;">

        YouTube

    </a>

</li>

<li>

    <a href="https://www.facebook.com/share/1EjTsuACnw/" target="_blank">

        <img src="/assets/icons/facebook.png" width="26"

        style="margin-right:8px;vertical-align:middle;">

        Facebook

    </a>

</li>

</div>

<div class="menu-overlay" id="menuOverlay" onclick="toggleMenu()"></div>

<!-- MAIN BOX -->

<div class="overlay">

    <div class="box" role="main">

        <h1>🔑 LẤY KEY 24H</h1>

        <p style="text-align:center;color:#cfefff;margin:10px 0 18px">

            Tạo xong rồi vui lòng get , đừng spam link

        </p>

        <div style="max-width:520px;margin:0 auto">

            <a href="?getkey=1" style="text-decoration:none;display:block">

                <button class="btn">LẤY KEY NGAY</button>

            </a>

            <!-- Music controls -->

            <div class="music-panel">

                <div class="music-row">

                    <button id="musicBtn">🔇</button>

                    <div class="vol">

                        <label for="volRange" style="min-width:78px">Âm lượng:</label>

                        <input id="volRange" type="range" min="0" max="1" step="0.01" value="0.6">

                        <span class="status" id="musicStatus">OFF</span>

                    </div>

                </div>

                <small style="color:#999;display:block;margin-top:10px">Nhạc nền: Cạnh Thì Không Thương Nhau </small>

            </div>

        </div>

    </div>

</div>

<!-- audio element (muted until user toggles) -->

<audio id="bgm" loop preload="auto">

    <source src="nhacnen.mp3" type="audio/mpeg">

</audio>

<script>

/* MENU */

function toggleMenu(){

    document.getElementById('sidebar').classList.toggle('open');

    document.getElementById('menuOverlay').classList.toggle('show');

}

/* MUSIC */

const audio = document.getElementById('bgm');

const btn = document.getElementById('musicBtn');

const vol = document.getElementById('volRange');

const status = document.getElementById('musicStatus');

// load saved state

let saved = localStorage.getItem('music_state') || 'off';

let savedVol = parseFloat(localStorage.getItem('music_vol') || '0.6');

vol.value = savedVol;

audio.volume = savedVol;

function updateUI(){

    if(saved === 'on'){

        btn.textContent = '🎵';

        status.textContent = 'ON';

    } else {

        btn.textContent = '🔇';

        status.textContent = 'OFF';

    }

}

// initial UI

updateUI();

if(saved === 'on'){

    audio.play().catch(()=>{});

}

btn.addEventListener('click', ()=>{

    if(saved === 'on'){

        saved = 'off';

        audio.pause();

    } else {

        saved = 'on';

        audio.play().catch(()=>{});

    }

    localStorage.setItem('music_state', saved);

    updateUI();

});

vol.addEventListener('input', ()=>{

    audio.volume = vol.value;

    localStorage.setItem('music_vol', vol.value);

});

</script>

</body>

</html>