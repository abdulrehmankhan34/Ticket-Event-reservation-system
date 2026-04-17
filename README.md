# Online Ticket Sales Platform (Symfony + PostgreSQL)

## Requirements (Windows)
- PHP 8.2+
- Composer
- PostgreSQL 16+ (running locally)

## Setup
1) Install dependencies:

```bash
composer install
```

2) Create `.env.local` (already gitignored) and set:
- `APP_SECRET`
- `DATABASE_URL`

Example:

```dotenv
APP_SECRET="your-random-secret"
DATABASE_URL="postgresql://postgres:1234@localhost:5432/mydb?serverVersion=16&charset=utf8"
```

3) Verify database connectivity:

```bash
php bin/console doctrine:query:sql "SELECT 1"
```

4) Create database (if needed):

```bash
php bin/console doctrine:database:create --if-not-exists
```

## Background workers / scheduled tasks
### E-ticket generation (Messenger worker)
E-tickets are generated asynchronously after checkout. Run a worker:

```bash
php bin/console messenger:consume async -vv
```

### Expire stale seat reservations
Reservations expire after 10 minutes. You can run the expirer manually:

```bash
php bin/console app:expire-reservations
```

## Concurrency notes (how to reproduce oversell attempt)
- Create a tier with `totalSeats = 1`
- Open the tier in two browsers/sessions
- Reserve + checkout simultaneously
- Expected: one succeeds, the other gets a sold-out style error, and `soldCount` never exceeds `totalSeats`

## Run locally
Use one of these:

```bash
symfony serve
```

or:

```bash
php -S localhost:8000 -t public
```

## Notes
- Do not put real secrets in `.env` (committed). Put them in `.env.local` (uncommitted).
