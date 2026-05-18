# Docker — backend (MySQL + API)

Documentación para el evaluador y detalle técnico del stack backend.

**Instrucciones rápidas:** [README.md](./README.md) · proyecto completo: [../README.md](../README.md)

Migración Laravel: **[UPGRADE.md](./UPGRADE.md)**.

---

## Arranque (evaluador)

```bash
cd backend-legacy-laravel8
docker compose up -d --build
docker compose logs -f api
```

| Servicio Compose | Rol | Puerto host |
|------------------|-----|-------------|
| `mysql` | MySQL 8.0 — **base de datos principal** | **3307** → 3306 |
| `redis` | Caché + colas (no sustituye MySQL) | solo red Docker |
| `api` | Laravel 11 (`artisan serve`) | **8000** |
| `queue` | `php artisan queue:work redis` | — |

Red Docker: `legacy_shared` (nombre fijo). Alias DNS **`api`** para el frontend.

**Verificación:**

```bash
docker compose ps
curl -s http://localhost:8000/api/health
```

MySQL desde DBeaver/CLI en el host: `127.0.0.1:3307`, `root` / `root`.

---

## Objetivo

- Un `docker-compose.yml` **por repositorio** (este archivo = paso 1; frontend = paso 2).
- Arranque: `docker compose up -d --build` en `backend-legacy-laravel8`, luego en `frontend-legacy-vue2`.
- DNS interno (`api`, `mysql`) en la red `legacy_shared`; sin IP fija.
- Sin `composer install` ni `.env` manual en cada máquina (`.env.docker` + entrypoint).

---

## Problemas y soluciones

### 1. Solo existía MySQL en Docker, no la API

**Problema:** El `docker-compose.yml` original del backend definía únicamente el servicio `mysql`. Laravel seguía pensado para ejecutarse en el host (`php artisan serve`) con `DB_HOST=127.0.0.1`.

**Solución:**

- Servicio `api` con `Dockerfile` (`php:8.3-cli` + extensiones `pdo_mysql`, `zip`).
- Comando por defecto: `php artisan serve --host=0.0.0.0 --port=8000`.
- Servicio renombrado/conceptualizado como `api` para que otros stacks lo resuelvan por nombre.

---

### 2. Base de datos: `127.0.0.1` no funciona dentro de un contenedor

**Problema:** En `.env.example`, `DB_HOST=127.0.0.1` apunta al propio contenedor de la API, no al de MySQL.

**Solución:**

- Archivo `.env.docker` con `DB_HOST=mysql` (nombre del servicio en Compose).
- En `docker-compose.yml`, refuerzo explícito: `environment: DB_HOST: mysql`.
- MySQL expuesto al host en `3307:3306` solo para herramientas externas (DBeaver, etc.); la API habla con `mysql:3306` por la red interna.

---

### 3. El navegador no puede usar nombres de servicio Docker (`api`, `mysql`)

**Problema:** Si se configura `VITE_API_URL=http://api:8000/api`, el **navegador** (fuera de Docker) no resuelve el hostname `api`. Las peticiones fallan aunque los contenedores estén en la misma red.

**Solución (patrón proxy en Vite):**

| Capa | Qué hace |
|------|----------|
| Navegador | Llama a `http://localhost:5173/api/...` (`VITE_API_URL=/api`, mismo origen). |
| Contenedor `frontend` (Vite) | Proxy `/api` → `http://api:8000` vía `VITE_PROXY_TARGET` (sí resuelve `api` en `legacy_shared`). |
| Contenedor `api` | Atiende Laravel en el puerto 8000. |

Cambios:

- `vite.config.js`: `server.proxy` para `/api`, `host: '0.0.0.0'`, `loadEnv` para leer `VITE_PROXY_TARGET`.
- `src/api/client.js`: `baseURL` por defecto `/api` en lugar de `http://127.0.0.1:8000/api`.
- `frontend/docker-compose.yml`: `VITE_PROXY_TARGET=http://api:8000`.

En desarrollo local sin Docker, el proxy apunta por defecto a `http://127.0.0.1:8000`.

---

### 4. Vite atado a `localhost` dentro del contenedor

**Problema:** Sin `host: '0.0.0.0'`, el servidor de desarrollo de Vite solo escucha en la interfaz loopback del contenedor y el puerto publicado `5173:5173` no responde desde el host.

**Solución:** `server.host: '0.0.0.0'` en `vite.config.js` (ya alineado con `npm run dev --host 0.0.0.0` en `package.json`).

---

### 5. Dos Compose files en proyectos distintos: una sola red

**Problema:** Backend y frontend viven en carpetas/repositorios separados. Un único `docker-compose.yml` en la raíz no era el requisito; hacía falta que el frontend alcanzara al backend por nombre.

**Solución:**

- El **backend** crea la red nombrada `legacy_shared` (`name: legacy_shared`).
- El **frontend** usa `networks.legacy_shared.external: true` y se une a la misma red.
- Orden de arranque: **backend primero** (crea la red), luego frontend.

Los servicios se referencian por nombre DNS de Compose: `api`, `mysql`, `frontend`.

---

### 6. Proyecto Laravel incompleto para contenedor (sin `vendor`, sin `storage`)

**Problema:**

- No había carpeta `vendor/` (hay que ejecutar Composer).
- No existían directorios `storage/` ni `bootstrap/cache/` (Laravel los necesita para logs, vistas, caché).

**Solución:**

- **Multi-stage Dockerfile:** stage Composer instala dependencias; la imagen final copia `vendor/` (sin bind mount del código en Compose).
- `docker/entrypoint.sh` crea `storage/` y `bootstrap/cache/` al arrancar.
- Si falta `vendor` (p. ej. desarrollo con montajes), ejecuta `composer install --no-dev`.

---

### 7. La API arrancaba antes de que MySQL estuviera listo

**Problema:** `depends_on` sin healthcheck solo espera que el contenedor **inicie**, no que MySQL acepte conexiones. Las migraciones fallaban de forma intermitente.

**Solución:**

- `healthcheck` en `mysql` con `mysqladmin ping`.
- `depends_on.mysql.condition: service_healthy` en el servicio `api`.
- Bucle en `entrypoint.sh` que prueba PDO contra `DB_HOST` antes de migrar.

---

### 8. Seed masivo (~10 000 productos) en cada reinicio

**Problema:** El seeder inserta miles de filas; ejecutarlo en cada `docker compose up` alargaría mucho cada arranque y podría duplicar datos.

**Solución:** En `entrypoint.sh`, migrar siempre; ejecutar `db:seed` **solo si** la tabla `users` está vacía (`USER_COUNT = 0`). La primera subida puede tardar varios minutos; los reinicios posteriores son rápidos.

---

### 9. Dependencias en la imagen (sin bind mount de código)

**Problema:** Montar el código del host sobre `/var/www/html` ocultaría `vendor/` generado en build.

**Solución actual:** El `docker-compose.yml` del backend **no** monta el código del host; `vendor` va en la imagen. Volumen nombrado solo para datos: `legacy_mysql_data` (MySQL). El frontend empaqueta `node_modules` en su imagen (`npm ci` en build).

---

### 10. Variables de entorno y `.env` en Docker

**Problema:** `.env.example` apunta a instalación local (`DB_HOST=127.0.0.1`, sin `APP_KEY`).

**Solución:**

- Backend: `.env.docker` copiado a `.env` en el entrypoint si no existe.
- Frontend: `.env.docker` y variables en `docker-compose.yml` (`VITE_API_URL`, `VITE_PROXY_TARGET`).
- `.dockerignore` excluye `.env` local del contexto de build por seguridad.

---

## Archivos añadidos o modificados

### Backend (`backend-legacy-laravel8`)

| Archivo | Rol |
|---------|-----|
| `Dockerfile` | PHP 8.3-cli, Composer multi-stage, `vendor` en imagen. |
| `docker-compose.yml` | Servicios `api` + `mysql`, red `legacy_shared`, volumen MySQL. |
| `docker/entrypoint.sh` | Storage, Composer, espera MySQL, migrate/seed condicional. |
| `.env.docker` | Variables para contenedor (`DB_HOST=mysql`, etc.). |
| `.dockerignore` | Excluye `vendor`, `.env`, artefactos innecesarios del build. |

### Frontend (`frontend-legacy-vue2`)

| Archivo | Rol |
|---------|-----|
| `Dockerfile` | Node 18, `npm install`, `npm run dev`. |
| `docker-compose.yml` | Servicio `frontend`, red externa `legacy_shared`. |
| `vite.config.js` | Proxy `/api`, `0.0.0.0`, `loadEnv`. |
| `src/api/client.js` | `baseURL` relativo `/api`. |
| `.env.docker` / `.env.example` | Documentan proxy local vs Docker. |
| `.dockerignore` | Excluye `node_modules`, `dist`. |

---

## Diagrama de flujo (desarrollo Docker)

```
[Navegador]
    |  GET/POST http://localhost:5173/api/*
    v
[frontend :5173]  --proxy VITE_PROXY_TARGET-->  [api :8000]
                                                    |
                                                    | DB_HOST=mysql
                                                    v
                                               [mysql :3306]
```

Red Docker: `legacy_shared` — resolución DNS por nombre de servicio.

---

## Comandos de referencia

```bash
# Backend (crea red legacy_shared)
cd backend-legacy-laravel8
docker compose up -d --build
docker compose logs -f api

# Frontend (requiere red existente)
cd ../frontend-legacy-vue2
docker compose up -d --build
```

| URL | Uso |
|-----|-----|
| http://localhost:5173 | Aplicación Vue |
| http://localhost:8000/api | API directa (Postman, health) |
| localhost:3307 | MySQL desde el host |

---

### 11. Composer en build: PHP y etiquetas de imagen

**Problema:** (a) `composer:2` con PHP 8.4 vs runtime `php:8.0-cli` rompía `platform_check.php`. (b) La etiqueta `composer:2-php8.2` no existe en Docker Hub.

**Solución:** Ver [UPGRADE.md](./UPGRADE.md) §1 — stage de dependencias en `php:8.3-cli`, binario Composer desde `composer:2`, `platform.php 8.3.0`, `composer.lock` versionado, `dump-autoload --no-scripts` en build.

---

## Limitaciones conocidas (no abordadas en esta iteración)

- **Solo entorno de desarrollo:** `artisan serve` y Vite dev, no Nginx + PHP-FPM ni build de producción multi-stage.
- **CORS:** Con el proxy de Vite en dev, el navegador no llama cross-origin al backend; no se añadió configuración CORS específica para Docker.
- **Un solo Compose “orquestador”:** No hay script raíz que levante ambos; el orden manual backend → frontend es intencional para Compose separados.
- **Credenciales en texto plano** en `.env.docker` / Compose: aceptable para prueba técnica local, no para producción.

---

## Relación con el README

Instrucciones rápidas de uso: [README.md](./README.md).  
Este documento explica el **por qué** de cada cambio técnico.
