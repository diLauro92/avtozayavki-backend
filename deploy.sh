#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
  echo "Есть незакоммиченные изменения. Деплой остановлен."
  git status --short --untracked-files=no
  exit 1
fi

echo "→ Обновляю код"
LOCK_BEFORE=$(git rev-parse HEAD:composer.lock)
git pull --ff-only
LOCK_AFTER=$(git rev-parse HEAD:composer.lock)

if [[ "$LOCK_BEFORE" != "$LOCK_AFTER" ]]; then
  echo "→ composer.lock изменился, ставлю зависимости"
  composer install --no-dev --optimize-autoloader
fi

echo "→ Миграции"
php artisan migrate --force

echo "→ Кеширую конфиг и роуты"
php artisan config:cache
php artisan route:cache

echo "→ Перезагружаю PHP-FPM"
sudo systemctl reload php8.5-fpm

echo "✓ Готово: $(git log --oneline -1)"
