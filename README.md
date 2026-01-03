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
npm install
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
npm run build
```

### Configurare Horizon

Proiectul foloseste Laravel Horizon pentru a procesa job-urile de import in paralel. Horizon ofera si un dashboard la ```/horizon``` pentru a monitoriza progresul importului in timp real.

Deschide intr-un terminal separat:
```bash
php artisan horizon
```

## Import Date

### Descarcarea fisierelor CSV

#### Fisiere Obligatorii

Descarca fisierele CSV de pe [data.gov.ro - Registrul Comertului](https://data.gov.ro/dataset/firme-inregistrate-la-registrul-comertului):
- `od_firme.csv` - Informatii despre firme
- `od_stare_firma.csv` - Starea firmelor
- `od_reprezentanti_legali.csv` - Reprezentanti legali
- `od_reprezentanti_if.csv` - Reprezentanti persoane fizice
- `od_sucursale_alte_state_membre.csv` - Sucursale in alte state membre

#### Fisiere Optionale (CAEN)

Pentru a importa clasificarile CAEN (Clasificarea Activităților în Economia Națională), descarca din [data.gov.ro - CAEN](https://data.gov.ro/dataset/codurile-caen):
- `n_caen.csv` - Definitii CAEN
- `n_caen_versiune.csv` - Versiuni CAEN
- `od_caen_autorizat.csv` - Legatura intre companii si coduri CAEN

**Nota**: Fisierele CAEN sunt optionale. Daca nu le descarci, importul va continua fara clasificarile CAEN.

Plaseaza toate fisierele in directorul `storage/app/imports/`

### Import Manual

#### Varianta 1: Import Toate Fisierele Deodată (Recomandat)

```bash
php artisan import:all
```

Aceasta va importa automat toate cele 5 fisiere CSV din directorul `storage/app/imports/`.

#### Varianta 2: Import Fisiere Individuale

Importa doar companiile:
```bash
php artisan import:companies storage/app/imports/od_firme.csv
```

Importa staile firmelor:
```bash
php artisan import:status storage/app/imports/od_stare_firma.csv
```

Importa reprezentantii legali:
```bash
php artisan import:legal-representatives storage/app/imports/od_reprezentanti_legali.csv
```

Importa reprezentantii persoane fizice:
```bash
php artisan import:natural-persons storage/app/imports/od_reprezentanti_if.csv
```

Importa sucursalele din alte state membre:
```bash
php artisan import:eu-branches storage/app/imports/od_sucursale_alte_state_membre.csv
```

#### Varianta 3: Import CAEN (Doar daca fisierele sunt disponibile)

Importa definitiile CAEN:
```bash
php artisan import:caen-definition storage/app/imports/n_caen.csv
```

Importa versiunile CAEN:
```bash
php artisan import:caen-version storage/app/imports/n_caen_versiune.csv
```

Importa legaturile companii-CAEN:
```bash
php artisan import:caen-company storage/app/imports/od_caen_autorizat.csv
```

**Ordinea Importului CAEN**: Trebuie importate in ordinea: definitii → versiuni → companii.

**Daca ai deja fisierele CAEN descarcate**: Cand rulezi `php artisan import:all`, acestea vor fi importate automat daca sunt in `storage/app/imports/`. Daca lipsesc, comanda va arata un mesaj cu instructiuni.

#### Monitorizare Import

In timp ce import-ul decurge, viziteaza dashboardul Horizon pentru a vedea progresul in timp real:

```
http://info-firme.test/horizon
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