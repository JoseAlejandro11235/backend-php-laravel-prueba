# Backend Legacy API

API legacy con problemas intencionales para prueba técnica. El framework fue actualizado a **Laravel 11**; la lógica de negocio legacy se mantiene.

## Stack

- Laravel 11
- PHP 8.2+ (Docker: `php:8.3-cli`)
- MySQL
- Docker (opcional)

Documentación:

- **[UPGRADE.md](./UPGRADE.md)** — migración Laravel 8 → 11 (problemas y soluciones)
- **[DOCKER.md](./DOCKER.md)** — Docker y red compartida con el frontend

## Instalación (local)

Requiere PHP 8.2+ y Composer.

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Docker

```bash
docker compose up -d --build
docker compose logs -f api
```

Ver [DOCKER.md](./DOCKER.md). Levantar el backend **antes** que el frontend (`legacy_shared`).

## URL base

```txt
http://127.0.0.1:8000/api
```

Health:

```txt
GET /up          # Laravel 11
GET /api/health  # Legacy (incluye DB)
```

## Credenciales de prueba

```txt
email: admin@legacy.test
password: password
```

## Endpoints legacy

```txt
POST /api/login
GET  /api/products
POST /api/products
GET  /api/products/{id}
PUT  /api/products/{id}
DELETE /api/products/{id}
GET  /api/categories
POST /api/categories
PUT  /api/categories/{id}
DELETE /api/categories/{id}
GET  /api/products/{id}/stock-movements
POST /api/products/{id}/stock-movements
GET  /api/dashboard
GET  /api/health
```

## Tests

```bash
php vendor/bin/phpunit
```

## Nota

Siguen existiendo errores intencionales de arquitectura, rendimiento, seguridad y mantenibilidad en el código de dominio. No tomar como ejemplo de buenas prácticas.
