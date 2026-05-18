#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php 2>/dev/null || true

# .env from Docker env_file + environment (no manual cp on host)
if [ ! -f .env ]; then
  if [ -f .env.docker ]; then
    cp .env.docker .env
  else
    cp .env.example .env
  fi
fi

# Dev compose with bind mounts: sync vendor with composer.lock
if [ ! -f vendor/autoload.php ] || [ ! -d vendor/laravel/sanctum ]; then
  echo "Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist --no-dev
  rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php 2>/dev/null || true
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

echo "Waiting for MySQL at ${DB_HOST:-mysql}..."
until php -r "
  try {
    new PDO(
      'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . getenv('DB_DATABASE'),
      getenv('DB_USERNAME'),
      getenv('DB_PASSWORD')
    );
    exit(0);
  } catch (Throwable \$e) {
    exit(1);
  }
"; do
  sleep 2
done

if [ -n "${REDIS_HOST:-}" ]; then
  echo "Waiting for Redis at ${REDIS_HOST}:${REDIS_PORT:-6379}..."
  until php -r "
    \$host = getenv('REDIS_HOST');
    \$port = (int) (getenv('REDIS_PORT') ?: 6379);
    \$fp = @fsockopen(\$host, \$port, \$errno, \$errstr, 2);
    if (\$fp) { fclose(\$fp); exit(0); }
    exit(1);
  "; do
    sleep 2
  done
fi

php artisan migrate --force

USER_COUNT=$(php -r "
  require 'vendor/autoload.php';
  \$app = require 'bootstrap/app.php';
  \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  echo Illuminate\Support\Facades\DB::table('users')->count();
" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ]; then
  echo "Seeding database..."
  php artisan db:seed --force
fi

echo "API ready."
exec "$@"
