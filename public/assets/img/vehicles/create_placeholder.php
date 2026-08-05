<?php
/**
 * CARDOXIS - Vehicle Image Placeholder Generator
 * 
 * Salvar como: C:\xampp\htdocs\cardoxis\public\assets\img\vehicles\placeholder.png
 * Execute este script uma vez para gerar a imagem placeholder
 */

// Configurar cabeçalho
header('Content-Type: image/png');

// Dimensões
$width = 400;
$height = 300;

// Criar imagem
$image = imagecreatetruecolor($width, $height);

// Cores
$primary = imagecolorallocate($image, 0, 82, 204); // #0052CC
$primaryLight = imagecolorallocate($image, 76, 154, 255); // #4C9AFF
$white = imagecolorallocate($image, 255, 255, 255);
$gray = imagecolorallocate($image, 107, 119, 140); // #6B778C

// Preencher fundo com gradiente
imagefilledrectangle($image, 0, 0, $width, $height, $primary);

// Adicionar padrão de veículo (ícone simples)
$iconSize = 80;
$iconX = ($width - $iconSize) / 2;
$iconY = ($height - $iconSize) / 2 - 20;

// Desenhar um ícone de carro simples
$carColor = $white;

// Corpo do carro
imagefilledrectangle($image, $iconX + 10, $iconY + 30, $iconX + $iconSize - 10, $iconY + $iconSize - 20, $carColor);
// Teto
imagefilledrectangle($image, $iconX + 20, $iconY + 15, $iconX + $iconSize - 20, $iconY + 35, $carColor);
// Rodas
imagefilledellipse($image, $iconX + 20, $iconY + $iconSize - 15, 20, 20, $gray);
imagefilledellipse($image, $iconX + $iconSize - 20, $iconY + $iconSize - 15, 20, 20, $gray);

// Adicionar texto
$textX = $width / 2;
$textY = $height - 40;
$fontSize = 5;
$text = "CARDOXIS";
$textWidth = imagefontwidth($fontSize) * strlen($text);
imagestring($image, $fontSize, ($width - $textWidth) / 2, $textY, $text, $white);

// Salvar a imagem
$outputPath = __DIR__ . '/placeholder.png';
imagepng($image, $outputPath);

// Destruir a imagem
imagedestroy($image);

echo "Placeholder criado com sucesso em: " . $outputPath;
echo "\n\nArquivo salvo como: placeholder.png";