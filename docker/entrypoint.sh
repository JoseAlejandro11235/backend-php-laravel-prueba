#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Avoid stale package manifest from host (e.g. dev-only Collision provider).
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php 2>/dev/null || true

if [ ! -f .env ]; then
  if [ -f .env.docker ]; then
    cp .env.docker .env
  else
    cp .env.example .env
  fi
fi

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate
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

php artisan migrate --force

USER_COUNT=$(php -r "
  require 'vendor/autoload.php';
  \$app = require 'bootstrap/app.php';
  \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  echo Illuminate\Support\Facades\DB::table('users')->count();
" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ]; then
  echo "Seeding database (first run may take a few minutes)..."
  php artisan db:seed --force
fi

exec "$@"
