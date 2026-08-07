<?php

require_once __DIR__ . '/gallery.php';
header('Content-Type: application/json; charset=utf-8');

function upload_error($message, $status = 400)
{
    http_response_code($status);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function upload_safe_component($value, $maximumBytes)
{
    $component = preg_replace('/[^\pL\pN_-]+/u', '-', (string) $value);
    $component = trim((string) $component, '-_');
    if (strlen($component) <= $maximumBytes) {
        return $component;
    }
    if (function_exists('mb_strcut')) {
        return rtrim(mb_strcut($component, 0, $maximumBytes, 'UTF-8'), '-_');
    }
    while (strlen($component) > $maximumBytes) {
        $component = preg_replace('/.$/u', '', $component);
    }
    return rtrim($component, '-_');
}

$name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
if ($name === '') {
    upload_error('Navn er påkrævet.');
}
if (!isset($_FILES['fileToUpload']['name']) || !is_array($_FILES['fileToUpload']['name'])) {
    upload_error('Der blev ikke valgt nogen billeder.');
}

$safeName = upload_safe_component($name, 60);
if ($safeName === '') $safeName = 'gaest';

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];
$directory = gallery_uploads_dir();
if (!is_dir($directory) && !@mkdir($directory, 0755, true)) {
    upload_error('Uploadmappen kunne ikke oprettes.', 500);
}

$uploaded = [];
$errors = [];
$fileInfo = new finfo(FILEINFO_MIME_TYPE);

foreach ($_FILES['fileToUpload']['name'] as $key => $originalName) {
    $error = $_FILES['fileToUpload']['error'][$key];
    $temporary = $_FILES['fileToUpload']['tmp_name'][$key];
    $size = (int) $_FILES['fileToUpload']['size'][$key];

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = $originalName . ': uploadfejl.';
        continue;
    }
    if ($size <= 0 || $size > 10000000) {
        $errors[] = $originalName . ': billedet må højst fylde 10 MB.';
        continue;
    }

    $mime = $fileInfo->file($temporary);
    if (!isset($allowedTypes[$mime]) || @getimagesize($temporary) === false) {
        $errors[] = $originalName . ': filtypen understøttes ikke.';
        continue;
    }

    try {
        $unique = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
    } catch (Exception $exception) {
        $unique = date('Ymd-His') . '-' . uniqid();
    }
    $originalStem = upload_safe_component(pathinfo($originalName, PATHINFO_FILENAME), 100);
    if ($originalStem === '') $originalStem = 'billede';
    $filename = $safeName . '--' . $unique . '--' . $originalStem . '.' . $allowedTypes[$mime];
    if (move_uploaded_file($temporary, $directory . DIRECTORY_SEPARATOR . $filename)) {
        $uploaded[] = gallery_public_item(['name' => $filename, 'mtime' => time()]);
    } else {
        $errors[] = $originalName . ': filen kunne ikke gemmes.';
    }
}

if (!$uploaded) {
    upload_error($errors ? implode(' ', $errors) : 'Ingen billeder blev uploadet.');
}

gallery_invalidate_cache();
$message = count($uploaded) === 1 ? 'Billedet er nu uploadet.' : count($uploaded) . ' billeder er nu uploadet.';
if ($errors) $message .= ' Nogle filer blev sprunget over: ' . implode(' ', $errors);

echo json_encode([
    'status' => 'success',
    'message' => $message,
    'images' => $uploaded,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
