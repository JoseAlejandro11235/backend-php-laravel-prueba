# OpenAPI / Swagger (Scramble)

La API incluye documentación **OpenAPI 3** generada automáticamente con [Scramble](https://github.com/dedoc/scramble) a partir de rutas, Form Requests y API Resources.

## URLs (con la API en marcha)

| Recurso | URL |
|---------|-----|
| **UI interactiva (Swagger-like)** | http://localhost:8000/docs/api |
| **Especificación JSON** | http://localhost:8000/docs/api.json |

En Docker, tras `docker compose up` en `backend-legacy-laravel8`, mismas rutas en el puerto **8000**.

> Acceso permitido con `APP_ENV=local` o `testing`, o con `APP_DEBUG=true` (`.env.docker` usa `local`).

## Autenticación en “Try it”

1. Ejecutar **POST** `/api/login` con `admin@legacy.test` / `password`.
2. Copiar el `token` de la respuesta.
3. En la UI de documentación, pulsar **Authorize** (esquema **Bearer / Sanctum**) y pegar el token.

Las rutas protegidas usan middleware `auth:sanctum`.

## Exportar archivo OpenAPI

```bash
php artisan scramble:export
```

Genera `api.json` en la raíz del backend (útil para Postman, Insomnia o CI).

## Qué documenta

- Grupos: Authentication, Products, Categories, Dashboard
- Cuerpos y query params desde Form Requests
- Respuestas desde tipos de retorno y Resources
- Errores 401 / 422 habituales

## Paquete

```json
"dedoc/scramble": "^0.13.22"
```

Configuración: `config/scramble.php`, `app/Providers/ScrambleServiceProvider.php`.
