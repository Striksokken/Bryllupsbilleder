<?php
// Sørg for, at dette er øverst i din PHP-fil, før noget HTML sendes
if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
    $imagesPerPage = 12; // Antal billeder per batch
    $offset = ($page - 1) * $imagesPerPage;

    $images = glob("uploads/*.*");
    usort($images, function($a, $b) {
        return filemtime($b) - filemtime($a); // Nyeste billeder først
    });

    $imagesBatch = array_slice($images, $offset, $imagesPerPage);

    header('Content-Type: application/json'); // Fortæl browseren, at dette er JSON
    echo json_encode($imagesBatch);
    exit(); // Stop udførelsen af yderligere HTML
}

// Opret en liste over alle billeder, som bruges til navigation i modal
$allImages = glob("uploads/*.*");
	usort($allImages, function($a, $b) {
    return filemtime($b) - filemtime($a); // Nyeste billeder først
	});
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Simone og Jakob Brodersen Gugel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="icons/Couple.ico" type="image/x-icon">
    <link rel="icon" href="icons/Couple 32x32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="icons/Couple 96x96.png" sizes="96x96" type="image/png">
    <link rel="apple-touch-icon" href="icons/Couple 180x180.png" sizes="180x180">
  	<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js"></script>
</head>
<body>
<main>
    <h1>Velkommen til vores bryllupsbillede galleri!</h1>
    <p class="subtext"><i>Vi vil meget gerne se de øjeblikke i har fanget fra vores bryllup den 28. september 2024. Del dem med os her. Du kan også se de billeder de andre gæster har taget og delt med os.<br>De kærligste hilsener</i></p>
    <p class="sign">Simone og Jakob</p>
    <div class="flex-container">
        <div class="upload">
            <h2>Del dine billeder med os</h2>
            <p>Indtast dit navn, klik herefter på forsæt. Derefter kan du vælge de billeder på din enhed du vil dele med os.</p> 
        	<form id="nameForm">
            	<label for="name">Indtast dit navn herunder:</label>
            	<input type="text" id="name" name="name" required>
            	<input type="submit" value="Fortsæt">
        	</form>
        	<form id="uploadForm" style="display: none;" enctype="multipart/form-data">
            	<input type="file" name="fileToUpload[]" id="fileToUpload" multiple required>
            	<input type="submit" value="Upload Billeder" name="submit">
        	</form>
        </div>
		<div class="gallerie">
      		<h2>Billedgalleri</h2>
            <p>Se billederne de andre gæster allerede har delt herunder. - gør billederne store ved at klikke på dem</p>
        	<div id="gallery">
            	<?php
                    // Første batch billeder ved første indlæsning af siden
                    $imagesPerPage = 12;
                    $images = glob("uploads/*.*");
                    usort($images, function($a, $b) {
                        return filemtime($b) - filemtime($a); // Nyeste billeder først
                    });

                    $imagesBatch = array_slice($images, 0, $imagesPerPage);

                    foreach ($imagesBatch as $image) {
                        echo '<div class="gallery-item">';
                        echo '<img src="'.$image.'" alt="Bryllupsbillede" onclick="openModal(this.src)">';
                        echo '</div>';
                    }
                ?>
        	</div>
		</div>
    </div>

    <!-- Modal -->
    <div id="myModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="img01">
        <div class="arrows">
            <span class="prev" onclick="changeImage(-1)">&#10094; Forrige</span>
            <span class="next" onclick="changeImage(1)">Næste &#10095;</span>
        </div>
    </div>
    
    <script src="script.js"></script>
  	<script>
    // Opret en JavaScript-liste med alle billedstier
    let allImages = <?php echo json_encode($allImages); ?>;
	</script>

</main>
<footer>
    <div class="footer1"><p><i>Bemærk: Billederne som er taget via MySelfie-boksen har vi mulighed for at hente ud efter festen.</i></p></div>
</footer>
</body>
</html>

