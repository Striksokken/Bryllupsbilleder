<?php

$requested = isset($_GET['file']) ? str_replace('\\', '/', (string) $_GET['file']) : '';
$allowedExtensions = ['ttf', 'otf', 'png', 'jpg', 'jpeg', 'ico'];
$extension = strtolower(pathinfo($requested, PATHINFO_EXTENSION));

if (!preg_match('#^(fonts|icons)/[^/]+$#', $requested) || !in_array($extension, $allowedExtensions, true)) {
    http_response_code(400);
    exit('Ugyldig asset');
}

$projectRoot = realpath(__DIR__);
$file = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $requested));
if ($file === false || !is_file($file) || strpos($file, $projectRoot . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(404);
    exit('Asset findes ikke');
}

$contentTypes = [
    'ttf' => 'font/ttf',
    'otf' => 'font/otf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'ico' => 'image/vnd.microsoft.icon',
];

header('Content-Type: ' . $contentTypes[$extension]);
header('Content-Length: ' . filesize($file));
header('Cache-Control: public, max-age=31536000, immutable');
readfile($file);

