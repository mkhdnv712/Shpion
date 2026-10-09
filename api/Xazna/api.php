<?php
if(isset($_GET['summa']) && isset($_GET['soat'])) {
    $summa = $_GET['summa'];
    $soat = $_GET['soat'];

    header('Content-Type: image/jpeg');

    // Rasm va shriftni chaqiramiz
    $img = imagecreatefromjpeg('img/rasm.jpg');
    $font = "font/font.OTF"; // SF Pro Display bo'lishi kerak
    $black = imagecolorallocate($img, 39, 39, 39); // Qoraga yaqin rang

    // Pul matnini tayyorlash
    $text = number_format($summa, 2, '.', ' ') . " UZS";
    $font_size_summa = 34;
$p = trim($_GET['p']);
function drawRotated($image, $size, $angle, $x, $y, $color, $font, $text) {
    imagettftext($image, $size, $angle, $x, $y, $color, $font, $text);
}

if (!empty($p)) {
    $text_p = $p;
    $p_x = 105; // chapdan masofa
    $p_y = 122; // tepadan boshlanish nuqtasi
    $p_angle = -65; // 90 daraja – pastga qarab yozish
    $p_size = 95;

    drawRotated($img, $p_size, $p_angle, $p_x, $p_y, $black, $font, $text_p);
}
    // Soat matni
    $font_size_time = 19;

    // Soat koordinatasi (yuqoridagi status barda soat bo‘lishi uchun)
    $x_time = 42;
    $y_time = 64;

    // Summa koordinatasi (to‘g‘ri joyga chiqishi uchun siz bergan rasmga qarab tekshirilgan)
    $x_summa = 145;
    $y_summa = 665;

    // Soat va summani yozamiz
    imagettftext($img, $font_size_time, 0, $x_time, $y_time, $black, $font, $soat);
    imagettftext($img, $font_size_summa, 0, $x_summa, $y_summa, $black, $font, $text);

    // Rasmni chiqarish
    imagejpeg($img);
    imagedestroy($img);
} else {
    echo "GET orqali ?summa=75000&soat=12:28 kabi qiymatlar yuboring.";
}