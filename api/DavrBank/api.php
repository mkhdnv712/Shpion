<?php
if (
    isset($_GET['summa']) &&
    isset($_GET['soat']) &&
    isset($_GET['sana']) &&
    isset($_GET['ism']) &&
    isset($_GET['karta1']) &&
    isset($_GET['karta2'])
) {
    header('Content-Type: image/jpeg');

    $summa = floatval($_GET['summa']);
    $soat = $_GET['soat'];
    $sana = $_GET['sana'];
    $ism = strtoupper($_GET['ism']);
    $karta1_raw = preg_replace('/\D/', '', $_GET['karta1']);
    $karta2_raw = preg_replace('/\D/', '', $_GET['karta2']);
    $vaqt_full = $sana . " " . $soat;
    $summa_fmt = number_format($summa, 0, '.', ' ') . " so'm";

    function maskCard($card) {
        $first4 = substr($card, 0, 4);
        $next2 = substr($card, 4, 2);
        $last4 = substr($card, -4);
        return "$first4 $next2** **** $last4";
    }

    $karta1 = maskCard($karta1_raw);
    $karta2 = maskCard($karta2_raw);

    function generateMerchant() {
        return str_pad(mt_rand(100000000000000, 999999999999999), 13, '0', STR_PAD_LEFT) . chr(mt_rand(65,90)) . chr(mt_rand(65,90));
    }

    function generateTerminal() {
        return str_pad(mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT) . chr(mt_rand(65,90));
    }

    $merchant = generateMerchant();
    $terminal = generateTerminal();

    $img = imagecreatefromjpeg('img/rasm.jpg');
    $font = 'font/font.OTF';
    $font2 = 'font/font4.ttf'; 
    $font3 = 'font/font3.ttf'; 
    $white = imagecolorallocate($img, 255, 255, 255);

    function drawRight($img, $text, $x_right, $y, $size, $color, $font) {
        $box = imagettfbbox($size, 0, $font, $text);
        $text_width = $box[2] - $box[0];
        imagettftext($img, $size, 0, $x_right - $text_width, $y, $color, $font, $text);
    }
    drawRight($img, $soat, 106, 40, 21, $white, $font);                  // Soat (status joyi)
    drawRight($img, $ism, 650, 375, 20, $white, $font2);                  // F.I.Sh
    drawRight($img, $karta1, 650, 423, 19, $white, $font2);               // Karta1
    drawRight($img, $karta2, 650, 476, 19, $white, $font2);               // Karta2
    drawRight($img, $summa_fmt, 650, 524, 19, $white, $font2);            // Summa
    drawRight($img, $vaqt_full, 650, 574, 17, $white, $font2);            // Sana + Soat
    drawRight($img, $merchant, 650, 622, 17, $white, $font2);             // Merchant
    drawRight($img, $terminal, 650, 672, 19, $white, $font2);             // Terminal

    imagejpeg($img);
    imagedestroy($img);
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Xatolik: Maʼlumotlar toʻliq yuborilmadi.";
}