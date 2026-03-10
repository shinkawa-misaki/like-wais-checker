#!/bin/sh
set -e

echo "=== Starting application ==="

# Wait for database to be ready
echo "Waiting for database..."
max_retries=30
retries=0
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" > /dev/null 2>&1; do
    retries=$((retries + 1))
    if [ "$retries" -ge "$max_retries" ]; then
        echo "Database not ready after ${max_retries} retries. Exiting."
        exit 1
    fi
    echo "Database not ready. Retry ${retries}/${max_retries}..."
    sleep 2
done

echo "Database is ready."

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed questions if empty
echo "Checking question data..."
QUESTION_COUNT=$(php artisan tinker --execute="echo App\Infrastructure\Persistence\Eloquent\Models\QuestionModel::count();" 2>/dev/null | tail -1)
if [ "$QUESTION_COUNT" = "0" ] || [ -z "$QUESTION_COUNT" ]; then
    echo "Seeding questions..."
    php artisan db:seed --force
fi

echo "=== Starting PHP-FPM ==="
exec php-fpm
