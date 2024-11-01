<?php
$zipFilename = 'uploads.zip'; // Navnet på ZIP-filen, som vil blive genereret
$uploadsDir = 'uploads/';
$zipFilePath = $uploadsDir . $zipFilename; // Fuld sti til ZIP-filen

// Tjek om ZIP-filen allerede eksisterer, og om den skal regenereres
$zipUpdated = false;

// Tjek om zip-filen allerede eksisterer, og om nogen filer er blevet ændret siden sidste zip
if (!file_exists($zipFilePath)) {
    $zipUpdated = true; // Hvis zip-filen ikke findes, skal den oprettes
} else {
    // Hvis nogen filer i uploads-mappen er blevet ændret efter zip-filens oprettelse, regenerer zip-filen
    $lastModifiedTime = filemtime($zipFilePath);
    foreach (glob($uploadsDir . '*') as $file) {
        if (filemtime($file) > $lastModifiedTime) {
            $zipUpdated = true;
            break;
        }
    }
}

// Hvis zip-filen skal opdateres, opret den igen
if ($zipUpdated) {
    $zip = new ZipArchive();

    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = glob($uploadsDir . '*'); // Alle filer i uploads-mappen
        foreach ($files as $file) {
            if (is_file($file)) {
                $zip->addFile($file, basename($file)); // Tilføj filen til zip-arkivet
            }
        }
        $zip->close();
    } else {
        die("Kan ikke oprette ZIP-filen.");
    }
}

// Når ZIP-filen er klar, send den til brugeren for download
if (file_exists($zipFilePath)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($zipFilePath) . '"');
    header('Content-Length: ' . filesize($zipFilePath));
    readfile($zipFilePath);
    exit();
} else {
    die("ZIP-filen blev ikke genereret.");
}
?>
