#!/usr/bin/env bash
# =============================================================================
# Samay: code ko live karne ka script. Har update ke baad yahi chalana hai.
#
# Chalao (sudo ke BINA):   bash deploy/deploy.sh
#
# Kaam ka kram:
#   1. GitHub se naya code kheenchna (git pull)
#   2. API: packages install, .env banana (pehli baar), tests chalana
#   3. Tests pass hue tabhi: database migrate + cache
#   4. Web: Vue build (web/dist banta hai)
#   5. PHP aur nginx reload
#
# Tests skip karne ho (jaldi mein):   SKIP_TESTS=1 bash deploy/deploy.sh
# =============================================================================
set -euo pipefail

if [[ $EUID -eq 0 ]]; then
    echo "Isko sudo ke bina chalao:  bash deploy/deploy.sh" >&2
    exit 1
fi

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CRED_FILE="/etc/samay/db.env"

step() { echo; echo "==> $*"; }

# .env mein KEY=value set karta hai (line ho to badalta hai, na ho to jodta hai).
set_env() {
    local key="$1" value="$2"
    if grep -qE "^#? ?${key}=" .env; then
        sed -i -E "s|^#? ?${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

cd "$APP_DIR"

step "1/5 Naya code (git pull)"
if git rev-parse --abbrev-ref --symbolic-full-name '@{u}' >/dev/null 2>&1; then
    git pull --ff-only
else
    echo "Git remote set nahi hai, pull skip."
fi

# ------------------------------- API -------------------------------------------
cd "$APP_DIR/api"

step "2/5 API: packages aur .env"
composer install --no-interaction --prefer-dist

FIRST_RUN=0
if [[ ! -f .env ]]; then
    FIRST_RUN=1
    if [[ ! -r "$CRED_FILE" ]]; then
        echo "$CRED_FILE nahi mili. Pehle chalao:  sudo bash deploy/setup-server.sh" >&2
        exit 1
    fi
    # shellcheck disable=SC1090
    source "$CRED_FILE"

    cp .env.example .env
    chmod 640 .env
    set_env APP_ENV production
    set_env APP_DEBUG false
    set_env APP_URL "${APP_URL:-http://localhost}"
    set_env LOG_LEVEL warning
    set_env DB_CONNECTION mysql
    set_env DB_HOST 127.0.0.1
    set_env DB_PORT 3306
    set_env DB_DATABASE "$DB_DATABASE"
    set_env DB_USERNAME "$DB_USERNAME"
    set_env DB_PASSWORD "$DB_PASSWORD"
    setfacl -m u:www-data:r .env          # website (www-data) .env padh sake, baaki koi nahi
    php artisan key:generate --force
    echo ".env ban gayi (production settings + database password)."
fi

if [[ "${SKIP_TESTS:-0}" != "1" ]]; then
    step "    API tests (fail hue to deploy yahin ruk jayega)"
    # Zaroori: cache hatao, warna tests asli database pe chal sakte hain.
    # Cache hatne ke baad tests phpunit.xml ke hisaab se memory wali sqlite database use karte hain.
    php artisan config:clear
    php artisan route:clear
    if ! php artisan test; then
        echo >&2
        echo "Tests fail ho gaye, isliye deploy rok diya. Upar ka output Claude ko bhejo." >&2
        exit 1
    fi
fi

step "3/5 API: production install, database, cache"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force            # sample services (dobara chalane pe duplicate nahi banta)
php artisan config:cache
php artisan route:cache

# ------------------------------- WEB -------------------------------------------
cd "$APP_DIR/web"

step "4/5 Web: Vue build"
npm ci --no-audit --no-fund
npm run build

step "5/5 Reload"
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo
echo "Deploy ho gaya. Browser mein kholo:  http://$(curl -fsS --max-time 5 https://checkip.amazonaws.com 2>/dev/null || echo '<server-ka-IP>')"
if [[ $FIRST_RUN -eq 1 ]]; then
    echo
    echo "Ab apna admin (service owner) account banao:"
    echo "    cd $APP_DIR/api && php artisan app:make-admin tumhara@email.com --name=\"Tumhara Naam\""
fi
