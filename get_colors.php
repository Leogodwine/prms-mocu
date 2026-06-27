<?php
$img = imagecreatefrompng('public/images/mocu_logo.png');
$width = imagesx($img);
$height = imagesy($img);

$colors = [];
for($x = 0; $x < $width; $x++) {
    for($y = 0; $y < $height; $y++) {
        $rgb = imagecolorat($img, $x, $y);
        $colorsList = imagecolorsforindex($img, $rgb);
        // Ignore transparent pixels
        if ($colorsList['alpha'] > 100) continue;
        // Ignore white and black
        if ($colorsList['red'] > 240 && $colorsList['green'] > 240 && $colorsList['blue'] > 240) continue;
        if ($colorsList['red'] < 15 && $colorsList['green'] < 15 && $colorsList['blue'] < 15) continue;
        
        $hex = sprintf("#%02x%02x%02x", $colorsList['red'], $colorsList['green'], $colorsList['blue']);
        if (!isset($colors[$hex])) $colors[$hex] = 0;
        $colors[$hex]++;
    }
}

arsort($colors);
$top = array_slice($colors, 0, 10);
print_r($top);
?>
