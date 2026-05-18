# Backend Legacy API

API de productos: **Laravel 11**, Sanctum, auditoría, tests y OpenAPI.

## Docker — paso 1 (MySQL + Redis + API)

> **Evaluador:** levantar **este** repositorio antes que el frontend. Incluye **MySQL** (datos), **Redis** (caché y colas), la **API** y el worker **`queue`**. Redis no sustituye MySQL.

Desde **esta carpeta**:

```bash
docker compose up -d --build
docker compose logs -f api
```

Esperar en logs: `API ready.` y `Laravel development server started`.

```bash
docker compose ps
curl -s http://localhost:8000/api/health
```

Servicios esperados: `mysql`, `redis`, `api`, `queue` (worker).

| Recurso | URL / datos |
|---------|-------------|
| API | http://localhost:8000/api |
| Health | http://localhost:8000/api/health |
| **OpenAPI / Swagger UI** | http://localhost:8000/docs/api |
| OpenAPI JSON | http://localhost:8000/docs/api.json |
| **MySQL (host)** | `127.0.0.1:3307` — `root` / `root` — BD `legacy_products` |

**Automático en el contenedor:** `.env.docker`, migraciones, seed si no hay usuarios, dependencias Composer en la imagen.

**Siguiente paso:** [frontend README](../frontend-legacy-vue2/README.md) — `docker compose up` en `frontend-legacy-vue2`.

Detalle técnico: [DOCKER.md](./DOCKER.md) · OpenAPI: [OPENAPI.md](./OPENAPI.md)

### Credenciales aplicación

```txt
email: admin@legacy.test
password: password
```

### Parar backend + MySQL

```bash
docker compose down
docker compose down -v   # además borra datos MySQL
```

---

## Instalación local (sin Docker)

PHP 8.2+ y Composer. MySQL en el host con `.env` propio.

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

---

## Documentación

- [DOCKER.md](./DOCKER.md) — arquitectura Docker, problemas resueltos
- [API.md](./API.md) — plan de migración, decisiones técnicas, optimización
- [UPGRADE.md](./UPGRADE.md) — Laravel 8 → 11
- [OPENAPI.md](./OPENAPI.md) — Swagger

## Tests (host, sin Docker obligatorio)

```bash
php vendor/bin/phpunit
```
