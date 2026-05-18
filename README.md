# Backend Legacy Laravel 8

Este proyecto representa una API legacy con problemas intencionales. El objetivo del candidato es migrarlo, optimizarlo y refactorizarlo.

## Stack actual legacy

- Laravel 8
- PHP 7.4 / 8.0
- MySQL
- Sin Swagger
- Sin Telescope
- Sin auditoría formal
- Sin pruebas reales

## Instalación inicial

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## URL base

```txt
http://127.0.0.1:8000/api
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

## Nota

Este proyecto tiene errores intencionales de arquitectura, rendimiento, seguridad, manejo de errores y mantenibilidad. No se debe tomar como ejemplo de buenas prácticas.
