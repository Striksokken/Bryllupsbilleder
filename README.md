# Bryllupsbilleder

## Kør lokalt med Docker

Docker Desktop skal være startet. Byg og start derefter løsningen fra repoets rod:

```powershell
docker compose up --build
```

Åbn <http://localhost:8080> i browseren. Stop løsningen med `Ctrl+C`.

Uploads gemmes i Docker-volumen `bryllupsbilleder_gallery_uploads`, så de bevares mellem genstarter uden at havne i Git.

Start i baggrunden:

```powershell
docker compose up --build -d
docker compose down
```

Fjern også lokale testbilleder permanent, hvis de ikke længere skal bruges:

```powershell
docker compose down --volumes
```

Containeren bruger PHP 8.4 med GD, JPEG, PNG og WebP og matcher hostingens relevante uploadgrænser. Produktionsfilen `.htaccess` bliver bevidst ignoreret lokalt. Ellers ville dens redirect og IP-begrænsninger forhindre lokal test. Containeren slår også Apaches indbyggede `/icons/`-alias fra, så projektets egen `icons`-mappe kan bruges.
