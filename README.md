# Samay

Service booking app: customer login karke service book karta hai, aur service owner (admin) sab kuch ek dashboard se sambhalta hai.

| Hissa | Kis se bana | Folder |
|---|---|---|
| API | Laravel 13 + Sanctum (token login) + MySQL | `api/` |
| Panel (user + admin) | Vue 3 + PrimeVue **Sakai** template | `web/` |
| Landing page | Vue + **Three.js** (3D ghadi aur slot cards) | `web/src/views/Landing.vue` |
| Hosting | nginx + PHP-FPM, ek hi AWS EC2 server pe | `deploy/` |

```
browser ──> nginx ──┬──>  /api/...   => Laravel   (api/public/index.php)
                    └──>  baaki sab  => Vue build (web/dist)
```

## Kya-kya hai

**Customer:** register / login → services dekhe → din aur khaali time chune → book kare → apni bookings dekhe ya cancel kare.

**Admin (service owner):** dashboard (pending, aaj ki bookings, kamai, agle 7 din ka chart) → bookings confirm / reject / complete kare → services add / edit / band kare → customers ki list.

**Niyam:** ek slot do logon ko nahi milta, beete hue time pe booking nahi hoti, aur koi register karke khud ko admin nahi bana sakta.

---

## Server pe live karna (AWS EC2, Ubuntu 24.04 ya naya)

Zaroorat: EC2 server chalu ho, aur uske **Security group** mein **HTTP (80)** khula ho (HTTPS ke liye baad mein 443 bhi).

**1. Server mein login karo**

```bash
ssh -i tumhari-key.pem ubuntu@SERVER-KA-IP
```

**2. Code server pe lao**

```bash
sudo mkdir -p /var/www && sudo chown ubuntu:ubuntu /var/www
git clone https://github.com/swarajjumde/samay.git /var/www/samay
cd /var/www/samay
```

**3. Server taiyaar karo (sirf pehli baar, 3-5 minute)**

```bash
sudo bash deploy/setup-server.sh
```

Ye nginx, PHP (8.3 ya naya), MySQL, Composer, Node install karta hai, database banata hai aur nginx config lagata hai. Script khol ke padho, har step pe comment hai.

**4. Deploy karo**

```bash
bash deploy/deploy.sh
```

Ye packages install karta hai, `.env` banata hai, **tests chalata hai**, database tables banata hai aur Vue build karta hai. Tests fail hue to deploy ruk jata hai.

**5. Apna admin account banao**

```bash
cd /var/www/samay/api
php artisan app:make-admin tumhara@email.com --name="Tumhara Naam"
```

**6. Browser mein kholo:** `http://SERVER-KA-IP`

### Code update kaise karein

GitHub pe naya code aane ke baad server pe bas:

```bash
cd /var/www/samay && bash deploy/deploy.sh
```

### Domain + HTTPS (lock wala)

1. EC2 → Elastic IPs → Allocate → apne server se Associate (IP fix ho jayega).
2. Domain ke DNS mein **A record** banao jo us IP pe jaye.
3. Security group mein **HTTPS (443)** kholo.
4. Server pe:

```bash
sudo sed -i 's/server_name _;/server_name tumhara-domain.in;/' /etc/nginx/sites-available/samay
sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d tumhara-domain.in
```

5. `api/.env` mein `APP_URL=https://tumhara-domain.in` karo, phir `cd /var/www/samay/api && php artisan config:cache`.

---

## Settings

`api/.env` mein (badalne ke baad `php artisan config:cache`):

| Key | Matlab | Default |
|---|---|---|
| `APP_NAME` | Business ka naam | `Samay` |
| `BOOKING_OPEN` / `BOOKING_CLOSE` | Kaam ke ghante | `09:00` / `18:00` |
| `BOOKING_STEP_MINUTES` | Har kitne minute pe slot | `30` |
| `BOOKING_CAPACITY` | Ek time pe kitni bookings | `1` |
| `BOOKING_CLOSED_DAYS` | Chhutti (0=Sunday ... 6=Saturday), jaise `0` | khaali |
| `BOOKING_MAX_DAYS_AHEAD` | Kitne din aage tak booking | `30` |

Website pe naam/tagline: `web/src/config/brand.js` (badal ke dobara deploy).

## API

Sabke aage `/api`. Login ke baad header: `Authorization: Bearer <token>`

| Method | Path | Kaun | Kaam |
|---|---|---|---|
| POST | `/register`, `/login` | sab | account / token |
| GET | `/services`, `/services/{id}/slots?date=YYYY-MM-DD`, `/business` | sab | services, khaali slots |
| GET, POST | `/me`, `/logout` | login | apni jaankari, logout |
| GET, POST | `/bookings` | login | meri bookings, nayi booking |
| PATCH | `/bookings/{id}/cancel` | login | apni booking cancel |
| GET | `/admin/dashboard`, `/admin/customers` | admin | stats, customers |
| GET, POST, PUT, DELETE | `/admin/services` | admin | services sambhalna |
| GET, PATCH | `/admin/bookings`, `/admin/bookings/{id}/status` | admin | bookings, status badalna |

Routes: `api/routes/api.php`. Slot ka hisaab: `api/app/Support/SlotGenerator.php`.

## Apne PC pe chalana (seekhne ke liye)

Chahiye: PHP 8.3+, Composer, Node 20+.

```bash
# Terminal 1: API
cd api
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan app:make-admin admin@example.com
php artisan serve                      # http://localhost:8000

# Terminal 2: Website
cd web
npm install
npm run dev                            # http://localhost:5173  (/api apne aap Laravel ko jaata hai)
```

Tests: `cd api && php artisan test`

## Kuch gadbad ho to

| Dikkat | Kya dekhna hai |
|---|---|
| Page nahi khul raha | Security group mein port 80 khula hai? `sudo systemctl status nginx` |
| 502 Bad Gateway | `systemctl status 'php*-fpm'` |
| API se 500 error | `tail -n 50 /var/www/samay/api/storage/logs/laravel.log` |
| nginx ka error | `sudo tail -n 50 /var/log/nginx/error.log` |
| `.env` badla par asar nahi | `cd api && php artisan config:cache` |

## Credits

Panel ka layout [Sakai Vue](https://github.com/primefaces/sakai-vue) (PrimeTek, MIT license, dekho `web/LICENSE.md`) par bana hai.
