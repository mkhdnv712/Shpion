<?php
if (
    isset($_GET['summa']) &&
    isset($_GET['sana']) &&
    isset($_GET['soat']) &&
    isset($_GET['karta1']) &&
    isset($_GET['karta2']) &&
    isset($_GET['ism'])
) {
    header('Content-Type: image/jpeg');

    // Parametrlarni olish
    $summa = floatval($_GET['summa']);
    $sana = $_GET['sana'];
    $soat = $_GET['soat'];
    $karta1 = preg_replace('/\D/', '', $_GET['karta1']);
    $karta2 = preg_replace('/\D/', '', $_GET['karta2']);
    $ism = strtoupper($_GET['ism']);

    // Hisoblashlar
    $komissiya = round($summa * 0.001, 2);
    $jami = $summa + $komissiya;

    function formatKarta($karta) {
    $blok1 = substr($karta, 0, 4);       // 9860
    $blok2 = substr($karta, 4, 2);       // 13
    $blok3 = '**';
    $blok4 = '****';
    $blok5 = '**';
    $blok6 = substr($karta, -2);         // 72

    return "$blok1 $blok2$blok3 $blok4 $blok5 $blok6";
}
    $karta1_mask = formatKarta($karta1);
    $karta2_mask = formatKarta($karta2);

    // Rasm va ranglar
    $img = imagecreatefromjpeg('img/rasm.jpg');
    $color_white = imagecolorallocate($img, 255, 255, 255);
    $color_gray = imagecolorallocate($img, 140, 140, 140);

    // Fontlar
    $font_main = 'font/font.OTF';     // SF Pro Display (asosiy font)
    $font_sub = 'font/font.ttf';      // SF Pro Text (transaksiyalar uchun)
    
    $p = trim($_GET['p']);
$white = imagecolorallocate($img, 255, 255, 255);
function drawRotated($image, $size, $angle, $x, $y, $color, $font, $text) {
    imagettftext($image, $size, $angle, $x, $y, $color, $font, $text);
}

if (!empty($p)) {
    $text_p = $p;
    $p_x = 105; // chapdan masofa
    $p_y = 122; // tepadan boshlanish nuqtasi
    $p_angle = -65; // 90 daraja – pastga qarab yozish
    $p_size = 95;

    drawRotated($img, $p_size, $p_angle, $p_x, $p_y, $white, $font_main, $text_p);
}

    // 💡 FUNKSIYA: yozuvni rasmga joylash
    function drawText($img, $text, $x, $y, $size, $color, $font) {
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
    }
function drawTextRight($img, $text, $xRight, $y, $size, $color, $font) {
    $bbox = imagettfbbox($size, 0, $font, $text);
    $textWidth = abs($bbox[2] - $bbox[0]);
    $x = $xRight - $textWidth; // O‘ngdan boshlanadi
    imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
}
    // Rasmga yozuvlar joylash
    drawText($img, $soat,               42,  45,  19, $color_white, $font_main);  
drawText($img, number_format($summa, 2, '.', ' ') . " UZS", 158, 317, 34, $color_white, $font_main); 
drawTextRight($img, number_format($summa, 2, '.', ' ') . " UZS", 605, 819, 19, $color_white, $font_sub); 
drawTextRight($img, "$sana, $soat",     605, 538, 22, $color_white, $font_sub);  
drawTextRight($img, $karta2_mask,       605, 647, 24, $color_white, $font_sub);  
drawTextRight($img, $ism,               605, 716, 22, $color_white, $font_sub);  
drawTextRight($img, number_format($komissiya, 2, '.', ' ') . " so'm", 605, 888, 20, $color_white, $font_sub); 
drawTextRight($img, $karta1_mask,       605, 953, 24, $color_white, $font_sub);  
drawTextRight($img, number_format($jami, 2, '.', ' ') . " UZS", 605, 1062, 19, $color_white, $font_main);

    // Yakuniy rasm
    imagejpeg($img);
    imagedestroy($img);
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Xatolik: Maʼlumotlar toʻliq yuborilmadi.";
}