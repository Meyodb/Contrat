<?php
$img = imagecreatetruecolor(300, 100);
$background = imagecolorallocate($img, 255, 255, 255);
$textcolor = imagecolorallocate($img, 0, 0, 0);
imagefilledrectangle($img, 0, 0, 300, 100, $background);
imagestring($img, 3, 50, 30, "Signature BRIAND Gregory", $textcolor);
imageline($img, 50, 60, 250, 60, $textcolor);
imageline($img, 50, 60, 100, 80, $textcolor);
imageline($img, 100, 80, 150, 40, $textcolor);
imageline($img, 150, 40, 200, 70, $textcolor);
imageline($img, 200, 70, 250, 60, $textcolor);
$dir = __DIR__ . '/storage/app/public/signatures/admin';
if (!file_exists($dir)) {
    mkdir($dir, 0755, true);
}
$filepath = $dir . '/admin_signature.png';
imagepng($img, $filepath);
imagedestroy($img);
chmod($filepath, 0644);
echo "Signature admin créée: {$filepath}"; 