<?php

require_once __DIR__ . '/gallery.php';

$filename = isset($_GET['file']) ? basename((string) $_GET['file']) : '';
if ($filename === '' || !gallery_allowed_extension($filename)) {
    http_response_code(400);
    exit('Ugyldigt filnavn');
}

$source = gallery_uploads_dir() . DIRECTORY_SEPARATOR . $filename;
if (!is_file($source)) {
    http_response_code(404);
    exit('Billedet findes ikke');
}

$fallback = 'uploads/' . rawurlencode($filename);
$imageInfo = @getimagesize($source);
if (!$imageInfo || !function_exists('imagecreatetruecolor')) {
    header('Location: ' . $fallback, true, 302);
    exit;
}

$loaders = [
    IMAGETYPE_JPEG => 'imagecreatefromjpeg',
    IMAGETYPE_PNG => 'imagecreatefrompng',
    IMAGETYPE_GIF => 'imagecreatefromgif',
    IMAGETYPE_WEBP => 'imagecreatefromwebp',
];
$type = $imageInfo[2];
if (!isset($loaders[$type]) || !function_exists($loaders[$type]) || !function_exists('imagejpeg')) {
    header('Location: ' . $fallback, true, 302);
    exit;
}

$cacheDirectory = gallery_uploads_dir() . DIRECTORY_SEPARATOR . '.thumbs';
if (!is_dir($cacheDirectory) && !@mkdir($cacheDirectory, 0755, true) && !is_dir($cacheDirectory)) {
    header('Location: ' . $fallback, true, 302);
    exit;
}

$cacheKey = sha1($filename . '|' . filemtime($source) . '|480x480-v1');
$thumbnail = $cacheDirectory . DIRECTORY_SEPARATOR . $cacheKey . '.jpg';

if (!is_file($thumbnail)) {
    $sourceImage = @$loaders[$type]($source);
    if (!$sourceImage) {
        header('Location: ' . $fallback, true, 302);
        exit;
    }

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    $scale = min(1, 480 / max($sourceWidth, $sourceHeight));
    $width = max(1, (int) round($sourceWidth * $scale));
    $height = max(1, (int) round($sourceHeight * $scale));
    $resized = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($resized, 255, 255, 255);
    imagefill($resized, 0, 0, $background);
    imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

    $temporary = $thumbnail . '.' . uniqid('', true) . '.tmp';
    if (@imagejpeg($resized, $temporary, 78)) {
        @rename($temporary, $thumbnail);
    }
    @unlink($temporary);
    imagedestroy($resized);
    imagedestroy($sourceImage);
}

if (!is_file($thumbnail)) {
    header('Location: ' . $fallback, true, 302);
    exit;
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($thumbnail));
header('Cache-Control: public, max-age=31536000, immutable');
readfile($thumbnail);

