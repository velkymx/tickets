# Installation

This guide covers installing Tickets on a fresh server or local machine. For Docker Compose, jump to the [Docker section](#docker-compose).

## Requirements

| Dependency | Minimum version |
|------------|----------------|
| PHP | 8.2 |
| Composer | 2.x |
| Node.js | 24 |
| MariaDB | 11.8 (or MySQL 8.0 / PostgreSQL 12 / SQLite 3.35) |

## Manual Installation

### 1. Clone and install dependencies

```bash
git clone https://github.com/velkymx/tickets.git
cd tickets
composer install
npm install
```

### 2. Create the database

```sql
CREATE DATABASE tickets;
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tickets
DB_USERNAME=root
DB_PASSWORD=your_password
```

Also set `APP_URL` to the URL the app will be served from, e.g. `http://localhost:8000`. This is used for avatar storage links and email notifications.

**Mail:** Set `MAIL_*` values if you want email notifications. For local development, [Mailpit](https://mailpit.axllent.org/) works well. Set `MAIL_MAILER=smtp` and point it at Mailpit's SMTP port.

**Queue:** Set `QUEUE_CONNECTION=database` (default) for in-process queuing. For production, switch to `redis`.

### 4. Run migrations and link storage

```bash
php artisan migrate
php artisan storage:link
```

### 5. Seed default data

This step is required. It creates default lookup tables (statuses, types, importance levels) and the administrator account.

```bash
php artisan db:seed --class=DefaultsSeeder
php artisan db:seed --class=UserSeeder
```

Default accounts created:

| Email | Password | Notes |
|-------|----------|-------|
| administrator | password123 | **Change this immediately after first login** |
| unassigned | *(none)* | System account — do not log in as this user |

> **Security:** The default administrator password is public knowledge. Change it before exposing the app to any network. Navigate to **Profile → Edit Profile** after logging in.

### 6. Build frontend assets

```bash
npm run build
```

### 7. Start the server

```bash
php artisan serve
```

Open `http://localhost:8000` and log in with the administrator account.

### 8. Start the queue worker

Email notifications and automation jobs run via the queue. Start the worker in a separate terminal (or configure it as a system service):

```bash
php artisan queue:work
```

For production, use a process manager like Supervisor to keep the worker running.

### 9. Run tests (optional)

```bash
php artisan test
```

All tests should pass on a clean install.

---

## Docker Compose

The Docker setup includes PHP, Nginx, MariaDB, Redis, and a queue worker. No local
PHP or Node required — the image builds the Composer dependencies and front-end
assets, and the app container runs migrations, seeding, and cache warming
automatically on first boot.

### 1. Create your environment file

```bash
cp .env.example .env
```

In `.env`, set these before starting:

- `DB_USERNAME` — any **non-root** name, e.g. `tickets` (MariaDB refuses to create a
  user called `root`).
- `DB_PASSWORD` — a password of your choice. The database container and the app
  both read it from here, so they always match.

Leave `DB_HOST`, `DB_PORT`, `REDIS_HOST`, and the cache/session/queue drivers alone
— Compose wires them to the Docker services automatically.

### 2. Generate an application key

```bash
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
```

Copy the printed `base64:...` value into `APP_KEY=` in `.env`. (The app reads its
config from `.env`, so the key must live there before you start the stack.)

### 3. Build and start

```bash
docker compose up -d --build
```

On first boot the app container migrates the database, seeds the defaults, and
caches config/routes/views. Follow along with `docker compose logs -f app`.

### 4. Open the app

Visit `http://localhost`. If port 80 is already in use, set `APP_PORT=8080` in
`.env` and use `http://localhost:8080`. Log in as `administrator` / `password123`
and change the password immediately.

> **Rebuilding:** the containers serve the code baked into the image via a shared
> volume, so after changing code and rebuilding you must recreate that volume:
> `docker compose down -v && docker compose up -d --build`.

---

## See also

- [API](api.md) — REST API reference and token setup
- [CSV Import](csv-import.md) — bulk ticket creation
