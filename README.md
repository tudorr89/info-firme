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

Clonare repo
```sh
git clone git@github.com:tudorr89/info-firme.git
```
Creare .env
```sh
cd info-firme/ && cp .env.example .env
```
Instalare packete
```sh
composer install
```
Se editeaza detaliile conexiunilor Redis si Mariadb in ```.env``` si se ruleaza migrarile

```php
php artisan migrate
```
Se genereaza cheia de encriptie
```php
php artisan key:generate
```

### Nota:
Proiectul vine cu horizon instalat. Joburile ruleaza mai bine sub el + exista dashboard pe url-ul ```/horizon``` pentru a vedea progresul importului in timp real.

Rulare horizon
```php
php artisan horizon
```

Se descarca CSV-urile de pe data.gov.ro de [aici](https://data.gov.ro/dataset/firme-inregistrate-la-registrul-comertului-pana-la-data-de-07-aprilie-2024)(la data scrierii acestea erau cele mai recente, CSV-urile se actualizeaza o data la 4 luni) si se copiaza in directorul proiectului.

Import Nomenclator
```php
php artisan import:nomenclator 5nomenclator_stari_firma.csv
```
Import Firme (toate cele 4 cu numele respective)
```php
php artisan import:companies 4firme_radiate_cu_sediu.csv
```

## Docker (Optional)
Proiectul vine cu laravel octane si frankenphp ca server web.

Build imagine:
```sh
docker build -t <image-name>:<tag> -f FrankenPHP.Dockerfile .
```
(inlocuiti image-name si tag cu ce vreti)

Rulare imagine (default port 8000)
```sh
docker run -d -e WITH_HORIZON=true -p <port>:8000 --rm <image-name>:<tag>
```

Rulare comenzi artisan
```sh
docker run --rm <image-name>:<tag> php artisan migrate
```

## Interogare API prin GET

Pe baza CUI:
```http
curl -L https://lista-firme.info/api/v1/info?cui=XXXXXXX
```
Pe baza Nume Companie (netestat suficient):
```http
curl -L https://lista-firme.info/api/v1/info?name=NumeFirma
```