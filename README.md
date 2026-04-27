# Server Room Supervision

Production-ready Laravel application for supervising server room telemetry, environmental sensors, maintenance workflows, reports, notifications, and user administration.

## Stack

- Laravel 12, PHP 8.2+
- MySQL
- Blade UI with Vite assets and embedded React monitoring widgets
- Laravel Reverb / Echo for WebSocket updates
- Database queues and cache by default
- ESP32 sensor ingestion and Python server monitoring agent ingestion

## Requirements

- PHP 8.2 or newer with common Laravel extensions
- Composer
- Node.js LTS and npm
- MySQL 8 or compatible MariaDB
- Web server such as Nginx or Apache
- Supervisor or systemd for queue workers and Reverb in production
- Redis is optional, but recommended if Reverb scaling or Redis cache/queues are enabled

## Local Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
npm run dev
php artisan serve
```

For Windows PowerShell, replace the copy command with:

```powershell
Copy-Item .env.example .env
```

## Production Deployment

1. Clone the repository on the server.
2. Create a production `.env` file from `.env.example`.
3. Set real production values for `APP_URL`, database credentials, mail, Reverb, API tokens, and secrets.
4. Install optimized dependencies and build assets:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Use `php artisan key:generate` only when creating a new environment. Do not rotate `APP_KEY` on an existing production app unless you understand the impact on encrypted data and sessions.

## Required Permissions

The web server user must be able to write to `storage/` and `bootstrap/cache/`.

Linux example:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

On shared hosting, make sure both folders are writable by the PHP process.

## Environment Notes

Production values should include:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=Africa/Tunis
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

Keep `.env` private. Never commit real keys, database passwords, ESP32 tokens, Telegram tokens, Groq keys, mail passwords, or Reverb secrets.

## Queue, Scheduler, And Cache

This project uses database-backed queues and cache by default. Run migrations before starting workers.

Production queue worker example:

```bash
php artisan queue:work --tries=3 --timeout=90
```

Production scheduler cron:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

If you switch to Redis, update `CACHE_STORE`, `QUEUE_CONNECTION`, and the Redis credentials in `.env`, then restart workers.

## Reverb And WebSocket Deployment

For local development:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

For production, run Reverb under Supervisor or systemd and proxy WebSocket traffic through Nginx or Apache with SSL enabled.

Common production `.env` values:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_ALLOWED_ORIGINS=https://your-domain.com
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

After changing Reverb or Vite environment values, run `npm run build` again and restart the Reverb process.

## ESP32 And Python Agent

- Configure the ESP32 and Python monitoring agent to send only real telemetry values.
- Keep ingestion tokens private and rotate them if exposed.
- Confirm server time is set correctly and Laravel uses `APP_TIMEZONE=Africa/Tunis` for local display and timestamps where applicable.
- Monitor ingestion logs after deployment to confirm CPU, RAM, disk, temperature, network, humidity, air flow, and power metrics continue arriving.

## Production Checklist

- `.env` exists on the server and is not committed.
- `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL` uses HTTPS.
- Database credentials are production credentials, not local root credentials.
- `APP_KEY` is generated and stored safely.
- `storage/` and `bootstrap/cache/` are writable.
- `composer install --no-dev --optimize-autoloader` completed.
- `npm ci && npm run build` completed.
- `php artisan migrate --force` completed.
- `php artisan storage:link` completed.
- `php artisan config:cache`, `route:cache`, `view:cache`, and `optimize` completed.
- Queue worker is running and supervised.
- Scheduler cron is installed.
- Reverb is running and supervised if WebSockets are enabled.
- SSL certificate is installed and auto-renewal is configured.
- Backups are configured for MySQL and uploaded files.
- Logs are monitored and rotated.
- No `.env`, `vendor/`, `node_modules/`, build artifacts, logs, database dumps, or cache files are committed.

## Development Commands

```bash
composer test
npm run build
php artisan config:clear
php artisan route:clear
php artisan optimize:clear
```

## Git Hygiene

Commit source code, configuration examples, migrations, tests, public source assets, and documentation.

Do not commit generated dependencies or secrets:

- `.env`
- `vendor/`
- `node_modules/`
- `public/build/`
- `public/storage/`
- `storage/logs/`
- `storage/framework/*` runtime files
- `bootstrap/cache/*.php`
- local database dumps
- IDE workspace files
