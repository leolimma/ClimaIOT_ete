<?php
$pngFile = 'public/assets/img/agradece.png';
$jpgFile = 'public/assets/img/agradece.jpg';

$img = imagecreatefrompng($pngFile);
$bg = imagecreatetruecolor(imagesx($img), imagesy($img));
$white = imagecolorallocate($bg, 255, 255, 255);
imagefill($bg, 0, 0, $white);
imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
imagejpeg($bg, $jpgFile, 90);
imagedestroy($img);
imagedestroy($bg);

echo "Imagem convertida: $jpgFile\n";
