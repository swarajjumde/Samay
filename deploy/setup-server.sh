#!/usr/bin/env bash
# =============================================================================
# Samay: server ko PEHLI BAAR taiyaar karne ka script (Ubuntu 24.04, AWS EC2)
#
# Chalao (sirf ek baar):   sudo bash deploy/setup-server.sh
# Dobara chalane se kuch tootega nahi, jo ho chuka hai wo skip ho jayega.
#
# Ye kya-kya karta hai:
#   1. Swap (extra "nakli RAM") banata hai, chhoti machine pe build ke liye zaroori
#   2. nginx, PHP 8.3, MySQL install karta hai
#   3. Composer (PHP ka package manager) install karta hai
#   4. Node.js 22 install karta hai (Vue build ke liye)
#   5. MySQL mein database + user banata hai (random password ke saath)
#   6. Laravel ke storage folders ki permissions set karta hai
#   7. nginx ka config lagata hai
# =============================================================================
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
    echo "Isko sudo ke saath chalao:  sudo bash deploy/setup-server.sh" >&2
    exit 1
fi

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_USER="${SUDO_USER:-ubuntu}"
DB_NAME="samay"
DB_USER="samay"
CRED_FILE="/etc/samay/db.env"

step() { echo; echo "==> $*"; }

if [[ "$APP_DIR" != "/var/www/samay" ]]; then
    echo "Project /var/www/samay mein hona chahiye (abhi: $APP_DIR). README ka Step 2 dekho." >&2
    exit 1
fi

# ---- 1. Swap -----------------------------------------------------------------
step "1/7 Swap check"
if swapon --show | grep -q .; then
    echo "Swap pehle se hai, skip."
else
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile >/dev/null
    swapon /swapfile
    grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
    echo "2 GB swap ban gaya."
fi

# ---- 2. Packages ---------------------------------------------------------------
step "2/7 nginx, PHP 8.3, MySQL install"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y nginx mysql-server \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-sqlite3 php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl \
    git curl unzip acl ca-certificates gnupg openssl

# ---- 3. Composer ---------------------------------------------------------------
step "3/7 Composer"
if command -v composer >/dev/null; then
    echo "Composer pehle se hai, skip."
else
    # Official tareeka: installer download karo aur uska checksum milao, tabhi chalao.
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    if [[ "$EXPECTED" != "$ACTUAL" ]]; then
        rm -f /tmp/composer-setup.php
        echo "Composer installer ka checksum match nahi hua, ruk gaye." >&2
        exit 1
    fi
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

# ---- 4. Node.js 22 -------------------------------------------------------------
step "4/7 Node.js 22"
if command -v node >/dev/null && [[ "$(node -v | cut -d. -f1 | tr -d v)" -ge 20 ]]; then
    echo "Node $(node -v) pehle se hai, skip."
else
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor --yes -o /etc/apt/keyrings/nodesource.gpg
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" > /etc/apt/sources.list.d/nodesource.list
    apt-get update -y
    apt-get install -y nodejs
fi

# ---- 5. Database ---------------------------------------------------------------
step "5/7 MySQL database"
systemctl enable --now mysql
if [[ -f "$CRED_FILE" ]]; then
    echo "Database pehle se bana hai ($CRED_FILE), skip."
else
    DB_PASS="$(openssl rand -hex 16)"
    mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
    # Password ek alag file mein rakhte hain jo sirf root aur tum padh sakte ho. deploy.sh ise .env mein daalta hai.
    mkdir -p /etc/samay
    umask 027
    cat > "$CRED_FILE" <<CREDS
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}
CREDS
    chown root:"$APP_USER" /etc/samay "$CRED_FILE"
    chmod 750 /etc/samay
    chmod 640 "$CRED_FILE"
    umask 022
    echo "Database '${DB_NAME}' aur user '${DB_USER}' ban gaye."
fi

# ---- 6. Permissions ------------------------------------------------------------
step "6/7 Laravel folders ki permissions"
# Laravel ko storage/ aur bootstrap/cache/ mein likhna padta hai (logs, cache).
# Website "www-data" user se chalti hai aur tum "$APP_USER" se commands chalate ho,
# isliye dono ko likhne ki ijazat dete hain (ACL se, nayi files pe bhi lagu rahega).
chown -R "$APP_USER":"$APP_USER" "$APP_DIR"
setfacl -R  -m u:www-data:rwX,u:"$APP_USER":rwX "$APP_DIR/api/storage" "$APP_DIR/api/bootstrap/cache"
setfacl -dR -m u:www-data:rwX,u:"$APP_USER":rwX "$APP_DIR/api/storage" "$APP_DIR/api/bootstrap/cache"

# ---- 7. nginx ------------------------------------------------------------------
step "7/7 nginx config"
systemctl enable --now php8.3-fpm nginx
if [[ -f /etc/nginx/sites-available/samay ]]; then
    echo "nginx config pehle se hai, usse chheda nahi (HTTPS settings bachi rahein)."
else
    cp "$APP_DIR/deploy/nginx.conf" /etc/nginx/sites-available/samay
    ln -sf /etc/nginx/sites-available/samay /etc/nginx/sites-enabled/samay
    rm -f /etc/nginx/sites-enabled/default
fi
nginx -t
systemctl reload nginx

echo
echo "Server taiyaar hai. Ab ye chalao (sudo ke BINA):"
echo "    bash deploy/deploy.sh"
