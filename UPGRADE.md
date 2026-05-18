# Laravel 8 → 11 — problemas y soluciones

Migración del backend de **Laravel 8** a **Laravel 11** manteniendo la API legacy (`/api/*`), autenticación por token y comportamiento de negocio intencionalmente deficiente.

---

## Resumen

| Antes | Después |
|-------|---------|
| PHP `^7.4\|^8.0` | PHP `^8.2` |
| Laravel `^8.75` | Laravel `^11.0` |
| `Http\Kernel`, `RouteServiceProvider` | `bootstrap/app.php` (estilo L11) |
| Docker `php:8.0` + `composer:2` (PHP 8.4) | `php:8.3-cli` + Composer bin desde `composer:2` |
| `fruitcake/laravel-cors` | CORS integrado en el framework (no requerido para dev con proxy Vite) |
| `facade/ignition` | Collision 8 + PHPUnit 11 |

---

## Problemas encontrados y soluciones

### 1. Error de build Docker: desajuste de versión PHP en Composer

**Problema (original):** `composer:2` resolvía dependencias con PHP 8.4; `composer dump-autoload` en `php:8.0-cli` fallaba con:

```text
Your Composer dependencies require a PHP version ">= 8.4.0". You are running 8.0.30.
```

**Problema (siguiente intento):** La etiqueta `composer:2-php8.2` **no existe** en Docker Hub:

```text
composer:2-php8.2: not found
```

**Solución actual:**

- Imágenes de runtime y de dependencias: **`php:8.3-cli`** (≥ 8.2 requerido por Laravel 11; etiqueta estable en Docker Hub).
- Stage Composer: misma imagen `php:8.3-cli` + binario copiado con `COPY --from=composer:2 /usr/bin/composer`.
- `composer config platform.php 8.3.0` para alinear resolución de paquetes con el runtime del contenedor.
- `composer dump-autoload --no-scripts` en build; scripts en entrypoint/runtime.
- `composer.lock` versionado (generado con PHP 8.2+ en host).

---

### 2. Requisito de PHP para Laravel 11

**Problema:** Laravel 11 exige PHP **≥ 8.2**.

**Solución:** `composer.json` → `"php": "^8.2"`. Docker usa `php:8.3-cli` (compatible con `^8.2`).

---

### 3. Estructura de arranque obsoleta (Kernel + RouteServiceProvider)

**Problema:** Laravel 8 usaba:

- `app/Http/Kernel.php` — middleware global y grupos `api`/`web`
- `app/Console/Kernel.php` — comandos y schedule
- `app/Providers/RouteServiceProvider.php` — prefijo `api`, rate limiting, namespace

**Solución:**

- Nuevo `bootstrap/app.php` con `Application::configure()`:
  - `withRouting(api: ..., commands: ..., health: '/up')`
  - Rutas protegidas con `auth:sanctum` (fase 2; ver [API.md](./API.md))
- Rate limiting movido a `AppServiceProvider::boot()` (`RateLimiter::for('api', ...)`).
- Eliminados: `Http\Kernel`, `Console\Kernel`, `RouteServiceProvider`, `TrimStrings` (middleware por defecto del framework).

---

### 4. `public/index.php` y `artisan`

**Problema:** Bootstrap L8 instanciaba `Http\Kernel` manualmente.

**Solución:**

- `public/index.php` → `$app->handleRequest(Request::capture())`
- `artisan` → `$app->handleCommand(new ArgvInput)`

---

### 5. Paquete `fruitcake/laravel-cors`

**Problema:** Dependencia separada en L8.

**Solución:** Eliminada de `composer.json`. CORS viene vía `fruitcake/php-cors` como dependencia transitiva de Laravel. En desarrollo Docker el frontend usa proxy Vite (`/api` → `api:8000`), por lo que no hace falta CORS cross-origin en el navegador.

---

### 6. Dependencias de desarrollo

**Problema:** Ignition, Collision 5 y PHPUnit 9 no son compatibles con L11.

**Solución:**

| L8 | L11 |
|----|-----|
| `facade/ignition` | Eliminado (errores vía Collision / framework) |
| `nunomaduro/collision` ^5 | ^8.1 |
| `phpunit/phpunit` ^9 | ^11 |
| — | `laravel/pint` ^1.13 (opcional, formateo) |

---

### 7. `config/app.php` con lista manual de providers

**Problema:** L8 registraba todos los service providers y aliases a mano.

**Solución:** `config/app.php` reducido al estilo L11. Providers de aplicación en `bootstrap/providers.php` (`AppServiceProvider`).

---

### 8. Variables de entorno de caché

**Problema:** L11 usa `CACHE_STORE` en lugar de `CACHE_DRIVER`.

**Solución:** Actualizado en `.env.example` y `.env.docker`.

---

### 9. Controlador base con traits legacy

**Problema:** `Controller` usaba `AuthorizesRequests`, `DispatchesJobs`, `ValidatesRequests` (patrón L8).

**Solución:** Clase abstracta vacía, como el skeleton L11. Los controladores legacy no dependían de esos traits.

---

### 10. Tests: facade root not set

**Problema:** Tras migrar, PHPUnit fallaba con `A facade root has not been set`.

**Solución:** `tests/TestCase.php` crea la aplicación y ejecuta `Kernel::bootstrap()` antes de los tests. Eliminado trait `CreatesApplication` redundante. Añadido test de `/up` (health de framework L11).

---

### 11. Carpetas `storage/` y `bootstrap/cache/`

**Problema:** No existían en el repo; Laravel las necesita en runtime.

**Solución:** Estructura creada con `.gitignore`; el `entrypoint.sh` de Docker sigue creando directorios si faltan.

---

### 12. Rutas `/api` y prefijo

**Problema:** El frontend Vue depende del prefijo `/api` y de rutas conocidas.

**Solución:** Prefijo `/api` vía `withRouting(api: ...)` en `bootstrap/app.php`. Las rutas y verbos se mantienen; la **fase 2** (refactor) endureció validación, auth Sanctum y forma JSON (p. ej. paginación en productos). Ver [API.md](./API.md#migración-desde-legacy).

---

## Archivos eliminados

```text
app/Http/Kernel.php
app/Console/Kernel.php
app/Providers/RouteServiceProvider.php
app/Http/Middleware/TrimStrings.php
tests/CreatesApplication.php
```

## Archivos principales nuevos o reescritos

```text
bootstrap/app.php
bootstrap/providers.php
composer.json
composer.lock
public/index.php
artisan
config/app.php
config/database.php
config/logging.php
phpunit.xml
storage/** (.gitignore)
tests/TestCase.php
Dockerfile (PHP 8.3-cli)
```

## Comandos post-upgrade

```bash
composer install
cp .env.example .env   # si no existe
php artisan key:generate
php artisan migrate --seed
php artisan serve
php vendor/bin/phpunit
```

Docker:

```bash
docker compose up -d --build
```

---

## Health checks

| Ruta | Uso |
|------|-----|
| `GET /up` | Health de Laravel 11 (sin lógica legacy) |
| `GET /api/health` | Health legacy con comprobación de base de datos |

---

## Fase 2 — Refactor de API (posterior al upgrade)

Tras el upgrade de framework se aplicó la evolución descrita en [API.md](./API.md):

- Sanctum, Form Requests, Services, API Resources, auditoría, Scramble (OpenAPI), tests feature, índices/FK y caché de dashboard.
- Plan completo, optimizaciones y decisiones técnicas: sección **Plan de migración y evolución** y **Decisiones técnicas** en [API.md](./API.md).

---

## Referencias

- [Laravel 11.x Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
- [API.md](./API.md) — plan de evolución, decisiones técnicas y optimización
- [OPENAPI.md](./OPENAPI.md) — documentación Swagger
- [DOCKER.md](./DOCKER.md) — red Docker y proxy con el frontend
