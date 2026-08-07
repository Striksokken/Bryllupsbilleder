<?php

const GALLERY_PAGE_SIZE = 24;

function gallery_uploads_dir()
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
}

function gallery_allowed_extension($filename)
{
    return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
}

function gallery_invalidate_cache()
{
    $cache = gallery_uploads_dir() . DIRECTORY_SEPARATOR . '.gallery-index.json';
    if (is_file($cache)) {
        @unlink($cache);
    }
}

/**
 * Return the image index, newest first. The directory is only scanned after an
 * upload (or another filesystem change), instead of once for every page request.
 */
function gallery_images()
{
    $directory = gallery_uploads_dir();
    if (!is_dir($directory)) {
        return [];
    }

    $cache = $directory . DIRECTORY_SEPARATOR . '.gallery-index.json';
    $directoryModified = @filemtime($directory) ?: 0;

    if (is_file($cache) && (@filemtime($cache) ?: 0) >= $directoryModified) {
        $decoded = json_decode((string) @file_get_contents($cache), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    $images = [];
    foreach (new DirectoryIterator($directory) as $file) {
        if (!$file->isFile() || !gallery_allowed_extension($file->getFilename())) {
            continue;
        }
        $images[] = [
            'name' => $file->getFilename(),
            'mtime' => $file->getMTime(),
        ];
    }

    usort($images, function ($a, $b) {
        if ($a['mtime'] === $b['mtime']) {
            return strcmp($b['name'], $a['name']);
        }
        return $b['mtime'] <=> $a['mtime'];
    });

    $temporary = $cache . '.' . uniqid('', true) . '.tmp';
    if (@file_put_contents($temporary, json_encode($images), LOCK_EX) !== false) {
        @rename($temporary, $cache);
        // File content updates do not change the directory timestamp. Touching
        // after rename makes the cache at least as new as the directory entry.
        @touch($cache);
    } else {
        @unlink($temporary);
    }

    return $images;
}

function gallery_public_item($image)
{
    $encodedName = rawurlencode($image['name']);
    return [
        'src' => 'uploads/' . $encodedName,
        'thumb' => 'thumb.php?file=' . $encodedName,
        'uploader' => gallery_uploader_from_filename($image['name']),
    ];
}

function gallery_uploader_from_filename($filename)
{
    $nameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);

    // Current format: Guest-name--20260807-123456-a1b2c3d4--original-name.jpg
    if (preg_match('/^(.+?)--\d{8}-\d{6}-[a-f0-9]+--/iu', $nameWithoutExtension, $matches)) {
        return str_replace('-', ' ', $matches[1]);
    }

    // First optimized format: 20260807-123456-a1b2c3d4-Guest-name.jpg
    if (preg_match('/^\d{8}-\d{6}-[a-f0-9]{8}-(.+)$/iu', $nameWithoutExtension, $matches)) {
        return str_replace('-', ' ', $matches[1]);
    }

    // Original format: 42, GuestName.jpg
    if (preg_match('/^\d+,\s*(.+)$/u', $nameWithoutExtension, $matches)) {
        return trim($matches[1]);
    }

    return '';
}

function gallery_page($page, $pageSize = GALLERY_PAGE_SIZE)
{
    $images = gallery_images();
    $offset = max(0, ($page - 1) * $pageSize);
    $slice = array_slice($images, $offset, $pageSize);

    return [
        'images' => array_map('gallery_public_item', $slice),
        'hasMore' => $offset + count($slice) < count($images),
        'nextPage' => $page + 1,
    ];
}
