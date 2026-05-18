# API — arquitectura y referencia

API REST refactorizada sobre **Laravel 11** con validación, capa de servicios, auditoría, tests y documentación OpenAPI.

Este documento concentra el **plan de evolución**, las **decisiones técnicas** y las **optimizaciones** del backend. La migración de framework (Laravel 8 → 11) y problemas de Docker están en [UPGRADE.md](./UPGRADE.md).

---

## Plan de migración y evolución

Migración en dos fases, manteniendo rutas `/api/*` y compatibilidad con el frontend.

| Fase | Objetivo | Entregables | Doc |
|------|----------|-------------|-----|
| **1 — Framework** | Subir a PHP 8.2+ y Laravel 11 sin romper contrato HTTP | `bootstrap/app.php`, Docker PHP 8.3, health `/up`, tests base | [UPGRADE.md](./UPGRADE.md) |
| **2 — API** | Calidad de producción: auth, validación, capas, observabilidad | Sanctum, Form Requests, Services, Resources, auditoría, Scramble, índices FK, PHPUnit feature | Este documento |

Orden recomendado en un entorno nuevo:

1. `composer install` / `docker compose up` (migraciones incluyen Sanctum, `audits`, índices).
2. `php artisan migrate --seed` (o entrypoint Docker).
3. Login de nuevo (`users.api_token` eliminado; tokens en `personal_access_tokens`).
4. Verificar tests: `php vendor/bin/phpunit`.
5. Revisar contrato en `/docs/api` y ajustar frontend si cambió paginación (`GET /api/products`).

---

## Decisiones técnicas

| Decisión | Elección | Motivo |
|----------|----------|--------|
| Autenticación API | **Laravel Sanctum** (Bearer) | Ligero, first-party, sin OAuth; sustituye `api_token` en columna de usuario |
| Validación | **Form Requests** (`app/Http/Requests`) | Reglas y autorización por acción; sin validación en controladores |
| Lógica de negocio | **Services** (`app/Services`) | Consultas, transacciones, jobs; sin Eloquent directo en controladores |
| Respuestas JSON | **API Resources** (`app/Http/Resources`) | Formato HTTP; controladores no arman `response()->json([...])` |
| Controladores | Orquestación | Form Request → Service → Resource |
| Auditoría | **owen-it/laravel-auditing** | Trazabilidad en `audits` sin lógica manual en cada modelo |
| OpenAPI | **dedoc/scramble** | Generación desde código (rutas, requests, resources); UI en `/docs/api` |
| CORS en dev | Proxy Vite `/api` → backend | Evita CORS en navegador; en Docker el hostname es `api` vía red `legacy_shared` |
| Tests | PHPUnit + **SQLite en memoria** | CI/local rápido; no depende de MySQL para la suite |
| Docker backend | Imagen con **vendor en build** + entrypoint | Menos “funciona en mi máquina”; seed sin Faker en prod (`--no-dev`) |
| Caché dashboard | **Redis** (`Cache::remember` 60s) + invalidación en writes | MySQL sigue siendo la fuente de verdad; Redis solo acelera lecturas |
| Colas | **Redis** + worker `queue` en Docker | Job `RefreshDashboardCache` tras movimientos de stock |
| Base de datos | **MySQL** | Redis **no** sustituye MySQL (usuarios, productos, auditoría) |
| Tipos de movimiento | **Enum** `StockMovementType` | Valores explícitos en PHP 8.2+ |

Alternativas descartadas en este alcance: **Passport** (OAuth innecesario), **L5-Swagger** (mantenimiento manual del spec), **token legacy en BD** (sin revocación ni rotación).

---

## Stack

| Capa | Tecnología |
|------|------------|
| Auth | Laravel Sanctum (Bearer tokens) |
| Validación | Form Requests |
| Respuestas | API Resources (JSON consistente) |
| Reglas de negocio | Services (`app/Services`) |
| Auditoría | [owen-it/laravel-auditing](https://github.com/owen-it/laravel-auditing) |
| Documentación | [dedoc/scramble](https://github.com/dedoc/scramble) → `/docs/api` |
| Caché / colas (Docker) | **Redis 7** (Predis) — MySQL para datos persistentes |
| Tests | PHPUnit 11 (SQLite en memoria; `array` cache, `sync` queue) |

---

## Documentación interactiva

Con el servidor en marcha:

```txt
http://localhost:8000/docs/api
```

Export OpenAPI: `php artisan scramble:export` → `api.json`

---

## Autenticación

```http
POST /api/login
Content-Type: application/json

{ "email": "admin@legacy.test", "password": "password" }
```

Respuesta:

```json
{
  "token": "1|…",
  "user": { "id": 1, "name": "…", "email": "…" }
}
```

Peticiones protegidas:

```http
Authorization: Bearer {token}
```

---

## Validación y errores

Errores de validación (422):

```json
{
  "message": "Validation failed.",
  "errors": { "field": ["…"] }
}
```

401 si falta o es inválido el token.

---

## Paginación

`GET /api/products?per_page=15&q=foo&category_id=1&status=1`

Respuesta estándar Laravel:

```json
{
  "data": [ … ],
  "links": { … },
  "meta": { "current_page", "per_page", "total", … }
}
```

`GET /api/categories` devuelve:

```json
{
  "categories": [ … ],
  "meta": { … }
}
```

---

## Auditoría

Cambios en `User`, `Category`, `Product`, `StockMovement` se registran en la tabla `audits` (evento, usuario, valores old/new).

Consulta ejemplo:

```sql
SELECT * FROM audits WHERE auditable_type = 'App\\Models\\Product' ORDER BY id DESC;
```

---

## Optimización

| Área | Medida | Detalle |
|------|--------|---------|
| **Esquema** | Índices + FK | `2025_05_18_000001_optimize_schema`: `products` (category, status, name, stock), `categories` (status, name), `stock_movements` (product+fecha, type) |
| **Consultas** | Eloquent + eager load | `category`, `withCount` en productos; sin SQL concatenado |
| **Listados** | Paginación | Productos y movimientos de stock (`per_page` acotado en Form Request) |
| **Dashboard** | Redis 60s | `DashboardService`; invalidación síncrona en writes + job `RefreshDashboardCache` en cola |
| **Colas** | Worker `queue` | `php artisan queue:work redis` en Docker |
| **Seed / Docker** | Volumen configurable | `SEED_PRODUCTS`, `SEED_CATEGORIES`, `SEED_STOCK_MOVEMENTS` en `.env.docker`; seeder sin Faker para `--no-dev` |
| **Auth** | Tabla Sanctum | `2025_05_18_000002` elimina `api_token`; tokens revocables por usuario |

---

## Tests

```bash
php vendor/bin/phpunit
```

Cubre login, productos (CRUD + paginación), categorías, movimientos de stock, dashboard y health.

---

## Migración desde legacy

| Legacy | Actual |
|--------|--------|
| Token plano en `users.api_token` | Sanctum `personal_access_tokens` |
| Middleware `LegacyTokenAuth` | `auth:sanctum` |
| Validación en controlador | Form Requests |
| SQL crudo / N+1 | Eloquent + servicios |
| Sin paginación | Paginación en productos y movimientos |
| Sin auditoría | Tabla `audits` |

Tras `migrate:fresh --seed`, volver a hacer login (tokens anteriores dejan de ser válidos).

---

## Endpoints

Ver OpenAPI en `/docs/api` o [README.md](./README.md).
