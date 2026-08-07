(() => {
    'use strict';

    const gallery = document.getElementById('gallery');
    const sentinel = document.getElementById('gallery-sentinel');
    const status = document.getElementById('gallery-status');
    const modal = document.getElementById('myModal');
    const modalImage = document.getElementById('img01');
    const imageFrame = modal.querySelector('.modal-image-frame');
    const modalStage = modal.querySelector('.modal-stage');
    const modalCounter = document.getElementById('modal-counter');
    const modalUploader = document.getElementById('modal-uploader');
    const closeButton = modal.querySelector('.close');
    const previousButton = modal.querySelector('.prev');
    const nextButton = modal.querySelector('.next');
    const rotateLeftButton = modal.querySelector('.rotate-left');
    const rotateRightButton = modal.querySelector('.rotate-right');
    const config = window.galleryConfig || { nextPage: 2, hasMore: false };

    let nextPage = config.nextPage;
    let hasMore = config.hasMore;
    let loading = false;
    let currentIndex = 0;
    let lastFocusedElement = null;
    let swipeStart = null;
    const imageSources = [];
    const knownSources = new Set();
    const uploaders = new Map();
    const rotations = new Map();

    function rememberSource(source, uploader = '', atStart = false) {
        if (uploader) uploaders.set(source, uploader);
        if (knownSources.has(source)) return;
        knownSources.add(source);
        if (atStart) imageSources.unshift(source);
        else imageSources.push(source);
    }

    gallery.querySelectorAll('[data-full-src]').forEach(item => {
        rememberSource(item.dataset.fullSrc, item.dataset.uploader || '');
    });

    function createGalleryItem(image, prepend = false) {
        if (!image || !image.src || knownSources.has(image.src)) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'gallery-item';
        button.dataset.fullSrc = image.src;
        button.dataset.uploader = image.uploader || '';
        button.setAttribute('aria-label', image.uploader
            ? `Åbn bryllupsbillede indsendt af ${image.uploader}`
            : 'Åbn bryllupsbillede');

        const img = document.createElement('img');
        img.src = image.thumb;
        img.alt = 'Bryllupsbillede';
        img.width = 480;
        img.height = 480;
        img.loading = 'lazy';
        img.decoding = 'async';
        button.appendChild(img);

        rememberSource(image.src, image.uploader || '', prepend);
        if (prepend) gallery.prepend(button);
        else gallery.insertBefore(button, sentinel);
    }

    async function loadMoreImages() {
        if (loading || !hasMore) return;
        loading = true;
        status.textContent = 'Henter flere billeder…';

        try {
            const response = await fetch(`index.php?page=${encodeURIComponent(nextPage)}`, {
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const result = await response.json();
            result.images.forEach(image => createGalleryItem(image));
            hasMore = Boolean(result.hasMore);
            nextPage = result.nextPage;
            status.textContent = hasMore ? '' : 'Alle billeder er indlæst.';
            if (!hasMore) observer.disconnect();
        } catch (error) {
            console.error('Fejl ved indlæsning af billeder:', error);
            status.textContent = 'Flere billeder kunne ikke hentes. Scroll lidt væk og tilbage for at prøve igen.';
        } finally {
            loading = false;
        }
    }

    const observer = new IntersectionObserver(entries => {
        if (entries.some(entry => entry.isIntersecting)) loadMoreImages();
    }, { root: gallery, rootMargin: '200px 0px' });
    if (hasMore) observer.observe(sentinel);

    function preloadAdjacentImage(offset) {
        if (imageSources.length < 2) return;
        const index = (currentIndex + offset + imageSources.length) % imageSources.length;
        const preloader = new Image();
        preloader.src = imageSources[index];
    }

    function showImage(index) {
        if (!imageSources.length) return;
        currentIndex = (index + imageSources.length) % imageSources.length;
        modal.classList.add('is-loading');
        modalCounter.textContent = `${currentIndex + 1} / ${imageSources.length}`;
        const uploader = uploaders.get(imageSources[currentIndex]) || '';
        modalUploader.textContent = uploader ? `Indsendt af ${uploader}` : '';
        modalUploader.hidden = !uploader;
        updateImageTransform();
        modalImage.src = imageSources[currentIndex];
        preloadAdjacentImage(1);
    }

    function updateImageTransform() {
        const rotation = rotations.get(imageSources[currentIndex]) || 0;
        modalImage.style.setProperty('--rotation', `${rotation}deg`);

        requestAnimationFrame(() => {
            if (!modalImage.offsetWidth || !modalImage.offsetHeight) return;
            const quarterTurn = Math.abs(rotation % 180) === 90;
            const rotatedWidth = quarterTurn ? modalImage.offsetHeight : modalImage.offsetWidth;
            const rotatedHeight = quarterTurn ? modalImage.offsetWidth : modalImage.offsetHeight;
            const scale = Math.min(
                1,
                imageFrame.clientWidth / rotatedWidth,
                imageFrame.clientHeight / rotatedHeight
            );
            modalImage.style.setProperty('--fit-scale', String(Math.max(0.1, scale)));
        });
    }

    function rotateImage(delta) {
        const source = imageSources[currentIndex];
        if (!source) return;
        const rotation = ((rotations.get(source) || 0) + delta) % 360;
        rotations.set(source, rotation);
        updateImageTransform();
    }

    function openModal(source, trigger) {
        const index = imageSources.indexOf(source);
        if (index < 0) return;
        lastFocusedElement = trigger;
        modal.hidden = false;
        document.body.classList.add('modal-open');
        showImage(index);
        closeButton.focus();
    }

    function closeModal() {
        modal.hidden = true;
        modalImage.removeAttribute('src');
        document.body.classList.remove('modal-open');
        if (lastFocusedElement) lastFocusedElement.focus();
    }

    gallery.addEventListener('click', event => {
        const item = event.target.closest('[data-full-src]');
        if (item) openModal(item.dataset.fullSrc, item);
    });
    closeButton.addEventListener('click', closeModal);
    rotateLeftButton.addEventListener('click', () => rotateImage(-90));
    rotateRightButton.addEventListener('click', () => rotateImage(90));
    previousButton.addEventListener('click', () => showImage(currentIndex - 1));
    nextButton.addEventListener('click', async () => {
        if (currentIndex === imageSources.length - 1 && hasMore) await loadMoreImages();
        showImage(currentIndex + 1);
    });
    modalImage.addEventListener('load', () => {
        modal.classList.remove('is-loading');
        updateImageTransform();
    });
    modalImage.addEventListener('error', () => modal.classList.remove('is-loading'));
    window.addEventListener('resize', updateImageTransform);
    modal.addEventListener('click', event => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', event => {
        if (modal.hidden) return;
        if (event.key === 'ArrowRight') nextButton.click();
        else if (event.key === 'ArrowLeft') previousButton.click();
        else if (event.key.toLowerCase() === 'r') rotateImage(90);
        else if (event.key.toLowerCase() === 'l') rotateImage(-90);
        else if (event.key === 'Escape') closeModal();
    });

    modalStage.addEventListener('pointerdown', event => {
        if (event.pointerType === 'mouse') return;
        swipeStart = { x: event.clientX, y: event.clientY };
    });
    modalStage.addEventListener('pointerup', event => {
        if (!swipeStart || event.pointerType === 'mouse') return;
        const deltaX = event.clientX - swipeStart.x;
        const deltaY = event.clientY - swipeStart.y;
        swipeStart = null;
        if (Math.abs(deltaX) < 55 || Math.abs(deltaX) < Math.abs(deltaY)) return;
        if (deltaX < 0) nextButton.click();
        else previousButton.click();
    });

    const nameForm = document.getElementById('nameForm');
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileToUpload');
    let uploaderName = '';

    nameForm.addEventListener('submit', event => {
        event.preventDefault();
        uploaderName = document.getElementById('name').value.trim();
        if (!uploaderName) {
            document.getElementById('name').focus();
            return;
        }
        nameForm.hidden = true;
        uploadForm.hidden = false;
        fileInput.disabled = false;
        fileInput.focus();
    });

    uploadForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!uploaderName) {
            uploadForm.hidden = true;
            fileInput.disabled = true;
            nameForm.hidden = false;
            document.getElementById('name').focus();
            return;
        }
        if (!fileInput.files.length) {
            alert('Vælg venligst mindst ét billede.');
            return;
        }

        const submitButton = uploadForm.querySelector('[type="submit"]');
        const formData = new FormData();
        [...fileInput.files].forEach(file => formData.append('fileToUpload[]', file));
        formData.append('name', uploaderName);
        submitButton.disabled = true;
        submitButton.value = 'Uploader…';

        try {
            const response = await fetch('upload.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Upload mislykkedes.');
            [...result.images].reverse().forEach(image => createGalleryItem(image, true));
            fileInput.value = '';
            alert(result.message);
        } catch (error) {
            console.error('Fejl ved upload:', error);
            alert(error.message || 'Billederne kunne ikke uploades.');
        } finally {
            submitButton.disabled = false;
            submitButton.value = 'Upload billeder';
        }
    });
})();
