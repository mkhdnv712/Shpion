<?php
header("Content-Type: image/jpeg");

// ====== PARAMETRLAR ======
$name   = $_GET['name']   ?? 'Abror Todjiyev';
$card   = $_GET['card']   ?? '98600101238272637263';
$amount = $_GET['amount'] ?? '45 000';
$time   = $_GET['time']   ?? '00:53';
$date   = $_GET['date']   ?? '12-fevral';

// ====== FAYLLAR ======
$template = __DIR__ . '/template.jpg'; 
$font_bold = __DIR__ . '/fonts/Roboto-Bold.ttf';
$font_reg  = __DIR__ . '/fonts/Roboto-Regular.ttf';

if (!file_exists($template)) exit('Template topilmadi');

$image = imagecreatefromjpeg($template);

// ====== RANGLAR ======
$white = imagecolorallocate($image, 255, 255, 255);
$green = imagecolorallocate($image, 50, 215, 75); // Muvaffaqiyatli yashili
$gray  = imagecolorallocate($image, 150, 150, 150); // Karta va vaqt uchun kulrang

// ====== MATNLARNI JOYLASHTIRISH (KOORDINATALAR) ======

// 1. Sana (Tepada o'rtada: "12-fevral")
imagettftext($image, 24, 0, 450, 155, $gray, $font_reg, $date);

// 2. Ism Familiya (Katta va qalin)
imagettftext($image, 42, 0, 210, 80, $white, $font_bold, $name);

// 3. Karta raqami (Ismning tagida)
imagettftext($image, 28, 0, 210, 130, $gray, $font_reg, $card);

// 4. "Muvaffaqiyatli" yozuvi (Yashil)
imagettftext($image, 34, 0, 340, 205, $green, $font_bold, "Muvaffaqiyatli");

// 5. Vaqt (Muvaffaqiyatli yozuvining yonida)
imagettftext($image, 26, 0, 610, 205, $gray, $font_reg, $time);

// 6. Asosiy Summa (Markazda katta)
// Summani formatlash (bo'sh joy bilan)
$formatted_amount = str_replace(' ', '', $amount);
$formatted_amount = number_format((float)$formatted_amount, 0, '', ' ');

imagettftext($image, 65, 0, 310, 285, $white, $font_bold, $formatted_amount);
imagettftext($image, 40, 0, 310 + (strlen($formatted_amount) * 35), 285, $white, $font_reg, "so'm");

// ====== CHIQARISH ======
imagejpeg($image, null, 100);
imagedestroy($image);
