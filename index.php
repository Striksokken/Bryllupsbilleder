<?php
require_once __DIR__ . '/gallery.php';

if (isset($_GET['page'])) {
    $page = max(1, (int) $_GET['page']);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(gallery_page($page), JSON_UNESCAPED_SLASHES);
    exit;
}

$initialPage = gallery_page(1);
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Simone og Jakob Brodersen Gugel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="asset.php?file=icons%2FCouple.ico" type="image/x-icon">
    <link rel="icon" href="asset.php?file=icons%2FCouple%2032x32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="asset.php?file=icons%2FCouple%2096x96.png" sizes="96x96" type="image/png">
    <link rel="apple-touch-icon" href="asset.php?file=icons%2FCouple%20180x180.png" sizes="180x180">
</head>
<body>
<main>
    <h1>Velkommen til vores bryllupsbillede galleri!</h1>
    <p class="subtext"><i>Vi vil meget gerne se de øjeblikke I har fanget fra vores bryllup den 28. september 2024. Del dem med os her. Du kan også se de billeder, de andre gæster har taget og delt med os.<br>De kærligste hilsener</i></p>
    <p class="sign">Simone og Jakob</p>
    <div class="flex-container">
        <section class="upload">
            <h2>Del dine billeder med os</h2>
            <p>Indtast dit navn, klik herefter på fortsæt. Derefter kan du vælge de billeder på din enhed, du vil dele med os.</p>
            <form id="nameForm">
                <label for="name">Indtast dit navn herunder:</label>
                <input type="text" id="name" name="name" autocomplete="name" required>
                <input type="submit" value="Fortsæt">
            </form>
            <form id="uploadForm" hidden enctype="multipart/form-data">
                <input type="file" name="fileToUpload[]" id="fileToUpload" accept="image/jpeg,image/png,image/gif,image/webp" multiple required disabled>
                <input type="submit" value="Upload billeder" name="submit">
            </form>
        </section>
        <section class="gallerie">
            <h2>Billedgalleri</h2>
            <p>Se billederne, de andre gæster allerede har delt. Klik på et billede for at se det stort.</p>
            <div id="gallery" aria-live="polite">
                <?php foreach ($initialPage['images'] as $index => $image): ?>
                    <button class="gallery-item" type="button" data-full-src="<?= htmlspecialchars($image['src'], ENT_QUOTES, 'UTF-8') ?>" data-uploader="<?= htmlspecialchars($image['uploader'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Åbn bryllupsbillede<?= $image['uploader'] !== '' ? ' indsendt af ' . htmlspecialchars($image['uploader'], ENT_QUOTES, 'UTF-8') : '' ?>">
                        <img src="<?= htmlspecialchars($image['thumb'], ENT_QUOTES, 'UTF-8') ?>" alt="Bryllupsbillede" width="480" height="480" loading="<?= $index < 8 ? 'eager' : 'lazy' ?>" decoding="async">
                    </button>
                <?php endforeach; ?>
                <div id="gallery-sentinel" aria-hidden="true"></div>
            </div>
            <p id="gallery-status" class="gallery-status" role="status"></p>
        </section>
    </div>

    <div id="myModal" class="modal" role="dialog" aria-modal="true" aria-label="Billedvisning" hidden>
        <header class="modal-toolbar">
            <div class="modal-meta">
                <span id="modal-counter" class="modal-counter" aria-live="polite"></span>
                <span id="modal-uploader" class="modal-uploader"></span>
            </div>
            <div class="modal-actions">
                <button class="modal-tool rotate-left" type="button" aria-label="Roter billedet mod venstre" title="Roter mod venstre (L)">
                    <span aria-hidden="true">↶</span><span class="tool-label">Roter venstre</span>
                </button>
                <button class="modal-tool rotate-right" type="button" aria-label="Roter billedet mod højre" title="Roter mod højre (R)">
                    <span aria-hidden="true">↷</span><span class="tool-label">Roter højre</span>
                </button>
                <button class="modal-tool close" type="button" aria-label="Luk billedvisning" title="Luk (Esc)">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </header>
        <div class="modal-stage">
            <button class="nav-button prev" type="button" aria-label="Vis forrige billede">&#10094;</button>
            <div class="modal-image-frame">
                <span class="modal-spinner" aria-hidden="true"></span>
                <img class="modal-content" id="img01" alt="Bryllupsbillede i stor størrelse">
            </div>
            <button class="nav-button next" type="button" aria-label="Vis næste billede">&#10095;</button>
        </div>
        <p class="modal-help">Brug piletasterne eller swipe for at skifte billede</p>
    </div>

    <script>
        window.galleryConfig = <?= json_encode([
            'nextPage' => $initialPage['nextPage'],
            'hasMore' => $initialPage['hasMore'],
        ], JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="script.js" defer></script>
</main>
<footer>
    <div class="footer1"><p><i>Bemærk: Billederne, som er taget via MySelfie-boksen, har vi mulighed for at hente ud efter festen.</i></p></div>
</footer>
</body>
</html>
