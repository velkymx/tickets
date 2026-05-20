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

The Docker setup includes PHP, Nginx, MariaDB, and a queue worker. No local PHP or Node required.

### 1. Copy environment file

```bash
cp .env.example .env
```

Leave all `DB_*` values as-is — Docker sets them automatically.

### 2. Start the stack

```bash
docker compose up -d
```

### 3. Run setup commands

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
docker compose exec app php artisan db:seed --class=DefaultsSeeder
docker compose exec app php artisan db:seed --class=UserSeeder
```

Open `http://localhost:8000`. Log in as `administrator` / `password123` and change the password immediately.

---

## See also

- [API](api.md) — REST API reference and token setup
- [CSV Import](csv-import.md) — bulk ticket creation
