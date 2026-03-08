#!/bin/bash
set -e

echo "Starting container..."
echo "PHP version: $(php -v | head -n 1)"
echo "Environment: ${APP_ENV:-unknown}"

# データベース接続テスト
echo "Testing database connection..."
echo "DB_HOST: ${DB_HOST:-not set}"
echo "DB_DATABASE: ${DB_DATABASE:-not set}"

# 最大30秒待機してデータベース接続を確認
timeout=30
counter=0
while [ $counter -lt $timeout ]; do
    if php artisan db:show 2>/dev/null; then
        echo "Database connection successful!"
        break
    fi
    counter=$((counter + 1))
    if [ $counter -lt $timeout ]; then
        echo "Waiting for database... ($counter/$timeout)"
        sleep 1
    else
        echo "WARNING: Database connection failed after ${timeout}s. Skipping migrations."
        echo "PHP-FPM will start anyway to accept health checks."
    fi
done

# マイグレーション実行（失敗しても継続）
if [ $counter -lt $timeout ]; then
    echo "Running migrations..."
    php artisan migrate --force || echo "Migration failed, continuing..."

    echo "Running seeders..."
    php artisan db:seed --force || echo "Seeding failed, continuing..."
else
    echo "Skipping migrations due to database connection failure"
fi

# キャッシュクリア
echo "Clearing caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# PHP-FPM起動
echo "Starting PHP-FPM..."
exec php-fpm

