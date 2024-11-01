// Global variabel for at holde alle billedstier
if (typeof allImages === 'undefined') {
    var allImages = []; // Brug var, så det ikke kolliderer med andre globale definitioner
}

let currentIndex = 0; // Bruges til modal navigation
let page = 2; // Start med den første batch af billeder
let loading = false; // Undgå at hente flere billeder, mens vi allerede loader

// Funktion til at åbne modal med valgt billede
function openModal(src) {
    let modal = document.getElementById("myModal");
    let modalImg = document.getElementById("img01");

    // Find billedets index i allImages-listen
    currentIndex = allImages.indexOf(src);

    // Vis modal og sæt billedet
    modal.style.display = "block";
    modalImg.src = src;
}

// Luk modal
function closeModal() {
    let modal = document.getElementById("myModal");
    modal.style.display = "none";
}

// Gå til næste billede i modal
function showNextImage() {
    currentIndex = (currentIndex + 1) % allImages.length;
    let modalImg = document.getElementById("img01");
    modalImg.src = allImages[currentIndex];
}

// Gå til forrige billede i modal
function showPrevImage() {
    currentIndex = (currentIndex - 1 + allImages.length) % allImages.length;
    let modalImg = document.getElementById("img01");
    modalImg.src = allImages[currentIndex];
}

// Initial image load function (infinite scroll)
function loadMoreImages() {
    if (loading) return;
    loading = true;

    fetch(`index.php?page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                const gallery = document.getElementById('gallery');
                data.forEach(imageSrc => {
                    // Tilføj nyt billede til DOM og modalens billedliste
                    const newImage = document.createElement('div');
                    newImage.classList.add('gallery-item');
                    newImage.innerHTML = `<img src="${imageSrc}" alt="Bryllupsbillede" onclick="openModal('${imageSrc}')">`;
                    gallery.appendChild(newImage);

                    // Tilføj til global billedliste til modal navigation
                    allImages.push(imageSrc);
                });
                page++; // Gå til næste side
                loading = false; // Tillad nye anmodninger
            } else {
                console.log("Ingen flere billeder at indlæse.");
                loading = false;
            }
        })
        .catch(error => {
            console.error('Fejl ved indlæsning af billeder:', error);
            loading = false;
        });
}

//Initialiserer infinite scroll
document.addEventListener('DOMContentLoaded', function () {
    const scrollableElement = document.getElementById('gallery'); // Erstat med det korrekte element ID

    loadMoreImages(); // Hent første batch af billeder

    // Lyt efter scroll-hændelser for infinite scroll på det specifikke element
    scrollableElement.addEventListener('scroll', () => {
        if ((scrollableElement.scrollTop + scrollableElement.clientHeight) >= scrollableElement.scrollHeight - 100 && !loading) {
            loadMoreImages(); // Hent flere billeder når vi er tæt på bunden af elementet
        }
    });
});


// Håndtering af billedupload
document.getElementById('nameForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const name = document.getElementById('name').value;
    if (name.trim() === '') {
        alert('Indtast venligst dit navn');
        return;
    }
    document.getElementById('nameForm').style.display = 'none';
    document.getElementById('uploadForm').style.display = 'flex';

    document.getElementById('uploadForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const fileInput = document.getElementById('fileToUpload');
        if (fileInput.files.length === 0) {
            alert('Vælg venligst et billede at uploade');
            return;
        }

        const formData = new FormData();
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append('fileToUpload[]', fileInput.files[i]);
        }
        formData.append('name', name);

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.status === 'success') {
                const gallery = document.getElementById('gallery');
                data.filenames.forEach(filename => {
                    const newImage = document.createElement('div');
                    newImage.classList.add('gallery-item');
                    // Tilføj billedet til galleriet
                    newImage.innerHTML = `<img src="uploads/${filename}" alt="Bryllupsbillede" onclick="openModal('uploads/${filename}')">`;
                    gallery.appendChild(newImage);

                    // Tilføj det uploadede billede til modalens billedliste
                    allImages.push(`uploads/${filename}`);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
});
function downloadAllFiles() {
    // Vi sender blot en anmodning til download.php for at hente ZIP-filen
    fetch('download.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to download the zip file');
            }
            return response.blob(); // Hent ZIP-filen som en blob
        })
        .then(blob => {
            // Opret et download-link og simuler et klik for at downloade ZIP-filen
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'uploads.zip'; // Navnet på den downloadede ZIP-fil
            document.body.appendChild(a);
            a.click(); // Start download
            window.URL.revokeObjectURL(url); // Ryd op
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Kunne ikke hente ZIP-filen.');
        });
}



// Modal handling
document.addEventListener('DOMContentLoaded', function () {
    let modal = document.getElementById("myModal");
    let closeBtn = document.getElementsByClassName("close")[0];
    let prevBtn = document.getElementsByClassName("prev")[0];
    let nextBtn = document.getElementsByClassName("next")[0];

    // Luk modal ved klik på "X"
    closeBtn.onclick = function () {
        modal.style.display = "none";
    };

    // Naviger mellem billederne i modal
    nextBtn.onclick = function () {
        showNextImage();
    };

    prevBtn.onclick = function () {
        showPrevImage();
    };

    // Luk modal, hvis man klikker udenfor billedet
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });

    // Brug piletaster til navigation i modal
    document.onkeydown = function (e) {
        if (modal.style.display === "block") {
            if (e.key === 'ArrowRight') {
                showNextImage();
            } else if (e.key === 'ArrowLeft') {
                showPrevImage();
            } else if (e.key === 'Escape') {
                modal.style.display = "none";
            }
        }
    };
});
