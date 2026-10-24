## Informatii Firme

Reprezinta un proiect simplu scris in laravel, care ofera informatii despre toate firmele inregistrate in Romania. Nu are interfata, totul se face din cli.

## De ce ? Exista API web ANAF

Deoarece e instabil si din experienta mea, mai mereu in mentenanta.

## Stack folosit

- Laravel
- Redis
- Mariadb
- Docker (optional)

## Instalare

### Preconditii
- PHP 8.3+
- Composer
- Redis
- MariaDB/MySQL
- Laravel Herd (sau Docker)

### Pasi de instalare

Clonare repo
```sh
git clone git@github.com:tudorr89/info-firme.git
cd info-firme
```

Creare fisier .env
```sh
cp .env.example .env
```

Instalare dependente
```sh
composer install
bun install
```

Configurare baza de date
```sh
# Editeaza .env cu detaliile conexiunii MariaDB/MySQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=info_firme
# DB_USERNAME=root
# DB_PASSWORD=
```

Generare cheia de aplicatie si rulare migrari
```sh
php artisan key:generate
php artisan migrate
```

Build assets frontend
```sh
bun run build
```

### Configurare Horizon

Proiectul foloseste Laravel Horizon pentru a procesa job-urile de import in paralel. Horizon ofera si un dashboard la ```/horizon``` pentru a monitoriza progresul importului in timp real.

Deschide intr-un terminal separat:
```bash
php artisan horizon
```

## Import Date

### Descarcarea fisierelor CSV (automat)

Descarcarea este complet automatizata. Comanda dedesubt interogheaza API-ul CKAN al
[data.gov.ro/organization/onrc](https://data.gov.ro/organization/onrc), alege cele mai
recente pachete `firme-*` si `nomenclatoare-*` (dupa data din numele pachetului) si
descarca toate resursele CSV in `storage/app/imports/`.

```bash
php artisan onrc:download
```

Fisierele descarcate (nume normalizate):

| Fisier | Continut |
| --- | --- |
| `od_firme.csv` | Firme (date de identificare + adresa) |
| `od_stare_firma.csv` | Starea firmelor |
| `od_reprezentanti_legali.csv` | Reprezentanti legali |
| `od_reprezentanti_if.csv` | Reprezentanti persoane fizice |
| `od_sucursale_alte_state_membre.csv` | Sucursale in alte state membre |
| `od_caen_autorizat.csv` | Legatura intre companii si coduri CAEN |
| `n_caen.csv` | Definitii CAEN |
| `n_versiune_caen.csv` | Versiuni CAEN |
| `n_stare_firma.csv` | Nomenclator stari firma |

Comportamentul este controlat din `.env`:

```dotenv
ONRC_ORGANIZATION=onrc
ONRC_API_URL=https://data.gov.ro/api/3/action
ONRC_TIMEOUT=30            # metadata (CKAN) request timeout
ONRC_DOWNLOAD_TIMEOUT=1800 # per-file download timeout, in seconds
ONRC_DOWNLOAD_RETRIES=3    # HTTP retries with backoff for slow hosts
IMPORT_DIRECTORY=imports   # storage/app subdirectory for the CSV files
```

Fisierele ONRC au sute de MB, iar hostul poate fi lent, asa ca descarcarea este
**streamuita direct pe disc** (fara buffering in memorie) si scrisa intai intr-un
fisier `.part`, apoi mutata atomic. Fiecare fisier este descarcat de un job separat
(`OnrcDownloadJob`) cu timeout de 20 de minute, 5 incercari si backoff progresiv
(1min → 5min → 15min → 30min).

```bash
php artisan onrc:download            # pune joburile in coada (recomandat)
php artisan onrc:download --force    # re-descarca si fisierele existente
php artisan onrc:download --sync     # descarca inline, fara cozi
```

### Import

Importa toate fisierele gasite in directorul de import, in ordinea corecta
(companii → nomenclatoare → CAEN → restul):

```bash
php artisan import:all
```

Optional, specifica alt director:

```bash
php artisan import:all --dir=/path/to/csv
```

`php artisan import:auto` ruleaza `import:all` doar daca au trecut cel putin 3 luni
de la ultimul import (`--force` ignora verificarea).

Toate importurile sunt **idempotente**: re-rularea aceluiasi snapshot actualizeaza
randurile existente in loc sa le dubleze.

### Import manual (fisiere individuale)

```bash
php artisan import:companies storage/app/imports/od_firme.csv
php artisan import:nomenclator storage/app/imports/n_stare_firma.csv
php artisan import:caen-version storage/app/imports/n_versiune_caen.csv
php artisan import:caen-definition storage/app/imports/n_caen.csv
php artisan import:status storage/app/imports/od_stare_firma.csv
php artisan import:caen-company storage/app/imports/od_caen_autorizat.csv
php artisan import:legal-representatives storage/app/imports/od_reprezentanti_legali.csv
php artisan import:natural-persons storage/app/imports/od_reprezentanti_if.csv
php artisan import:eu-branches storage/app/imports/od_sucursale_alte_state_membre.csv
```

### CIF-uri blocate (banned)

Companiile cu CUI-ul in lista `SKIP_CIFS_IMPORT` (separate prin virgula) sunt ignorate
la import. Joburile care importa datele legate (stari, reprezentanti, sucursale, CAEN)
rezolva `companies.reg_com` intr-un singur query per batch si sari peste randurile fara
companie existenta, deci nu se creeaza date orfane pentru CIF-urile blocate.

```dotenv
SKIP_CIFS_IMPORT=52491237,12345678
```

### Legarea datelor (company links)

Fiecare rand importat este legat de compania lui la momentul inserarii. Daca datele
ajung inaintea companiilor (import paralel) sau exista randuri vechi fara legatura,
ruleaza:

```bash
php artisan companies:link            # completeaza company_id
php artisan companies:link --dry-run  # doar raporteaza cate randuri nu sunt legate
php artisan companies:link --prune    # sterge si randurile companiilor neimportate
```

`import:all` programeaza automat un `LinkCompanyDataJob` (dupa 60 de minute, configurabil
cu `--link-delay=0` pentru a-l dezactiva), iar scheduler-ul ruleaza `companies:link`
zilnic si `companies:link --prune` lunar.

#### Monitorizare Import

In timp ce import-ul decurge, viziteaza dashboardul Horizon pentru a vedea progresul in timp real:

```
http://info-firme.test/admin/horizon
```

Dashboardul arata:
- Joburile in asteptare (pending)
- Joburile in progres (processing)
- Joburile finalizate (completed)
- Joburile care au esuat (failed)
- Statistici despre performanta

### Detalii despre import

- Import-ul este **asincron** - joburile sunt procesate in background de Horizon
- Procesare in **batch-uri de 1000 inregistrari** pentru performanta optima
- **Retry logic automat** - daca importul esueaza pe deadlock MySQL, se incearca din nou (max 3 incercari)
- **Deduplicare intre batch-uri** - nu se importeaza inregistrarile duplicate
- **Validare CUI** - se importeaza doar inregistrarile cu CUI valid (non-gol si != "0")
- **Timeout de 4 ore** - pentru a permite procesarea fisierelor mari cu milioane de inregistrari

## Rulare (Development)

### Cu Laravel Herd (Recomandat)

Laravel Herd lanseaza automat aplicatia la `http://info-firme.test` in background.

Asigura-te ca Horizon ruleaza intr-un terminal separat:
```bash
php artisan horizon
```

Viziteaza dashboardul Horizon la `http://info-firme.test/horizon` pentru a monitoriza joburile.

### Cu Docker (Optional)

Proiectul vine cu Laravel Octane si FrankenPHP ca server web.

Build imagine:
```sh
docker build -t info-firme:latest -f FrankenPHP.Alpine.Dockerfile .
```

Rulare imagine (default port 8000):
```sh
docker run -d -e WITH_HORIZON=true -p 8000:8000 --rm info-firme:latest
```

Rulare comenzi artisan in container:
```sh
docker run --rm info-firme:latest php artisan migrate
```

## API

### Interogare prin GET

Pe baza CUI:
```bash
curl -L https://lista-firme.info/api/v1/info?cui=XXXXXXX
```

Pe baza Nume Companie:
```bash
curl -L https://lista-firme.info/api/v1/info?name=NumeFirma
```

### Raspunsuri API

Raspunsul se returneaza in format JSON cu informatii despre firma, adresa, reprezentanti, etc.