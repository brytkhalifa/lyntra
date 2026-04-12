# Lyntra

Lyntra is a URL shortener built with **Laravel**, **Inertia.js**, and **Vue 3**. Authenticated users can create short links, view a dashboard with aggregate metrics, inspect per-link analytics (clicks over time and device breakdowns), open the **QR code** menu on each link to **view** (SVG in a new tab) or **download** PNG, and resolve external short URLs safely via the expand tool. The QR endpoint also accepts `?format=svg` and `?inline=1` for inline display.

## Requirements

- PHP **8.3+** with common extensions (`pdo`, `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`). For **PNG** QR output, enable the **GD** extension; **SVG** QR (`?format=svg`) does not require GD.
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) and npm

## Quick start

```bash
git clone <repository-url> lyntra
cd lyntra
composer run setup
```

The `setup` script installs PHP and Node dependencies, creates `.env` if missing, generates an app key, runs migrations, and builds frontend assets. If you use SQLite and `migrate` fails, create the database file first: `touch database/database.sqlite`.

Alternatively, run the steps manually:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # if using SQLite (default in .env.example)
php artisan migrate
npm install
npm run build
```

Configure `APP_URL` in `.env` so generated short URLs match your environment.

## Development

Run the app with Vite, queue worker, logs, and the PHP server together:

```bash
composer run dev
```

Or run pieces separately, for example:

```bash
php artisan serve
npm run dev
```

After changing Laravel routes, regenerate typed route helpers (include form bindings used by Inertia):

```bash
php artisan wayfinder:generate --with-form --no-interaction
```

## Testing and quality

```bash
php artisan test --compact
```

Composer aggregates checks used in CI-style workflows:

```bash
composer run ci:check
```

That runs ESLint, Prettier, `vue-tsc`, and the full PHPUnit suite (see `composer.json`).

Format after edits:

- **PHP:** `composer lint` runs Laravel Pint with `--parallel` and **rewrites** files (same as formatting the PHP tree). Use `composer lint:check` for a no-write check (`pint --test`). To limit Pint to changed PHP files only, use `vendor/bin/pint --dirty`.
- **Frontend:** `npm run format` runs Prettier on `resources/` (Vue, JS, CSS). That is separate from Composer; there is no `composer format` script in this project.

## Optional: click geolocation (MaxMind)

Click records can store country and related fields when a GeoIP2-compatible database is configured.

1. Obtain a **GeoLite2 City** or **GeoIP2 City** `.mmdb` file from [MaxMind](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data).
2. Place it on disk (for example `storage/geo/GeoLite2-City.mmdb`; the `storage/geo/` directory is gitignored for real databases).
3. In `.env`, set:

   ```env
   GEOIP_ENABLED=true
   GEOIP_DATABASE_PATH=/absolute/path/to/GeoLite2-City.mmdb
   ```

Tests can use a small fixture database and disable real lookups via `GEOIP_ENABLED=false` (see `phpunit.xml` and `tests/fixtures/`).

## Production build (with SSR)

```bash
npm run build:ssr
```

## License

Released under the MIT License (see `composer.json`).
