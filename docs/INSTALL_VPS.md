# Install on a new VPS

This runbook reproduces the observed topology: Apache terminates HTTP/TLS, PHP serves the API from its `public/` directory, MySQL and Memcached run locally, and PM2 runs the Next.js frontend on port 3000.

## 1. Base requirements

Use Ubuntu LTS. Install Apache, MySQL 8, Memcached, PHP 8.2+ and extensions required by the API (`mysqli`, `curl`, `gd`, `intl`, `mbstring`, `xml`, `zip`, `bcmath`, `memcached`), Composer, Node 24 LTS or a compatible Node version, pnpm and PM2.

Create an unprivileged deploy user and clone this repository to `/srv/eudr`. Do not run the web app as `root`.

## 2. Database

Create a local MySQL service account with a strong random password and database creation permission, then run:

```bash
cd /srv/eudr
export DB_NAME=eudr
export DB_USER=eudr
export DB_PASSWORD='new-strong-password'
./database/restore.sh
```

The seed installs only synthetic demo data. It is not a production data migration.

## 3. API

```bash
cd /srv/eudr/api
cp config/.env.example config/.env
# Set all values in config/.env for the new infrastructure.
composer install --no-dev --optimize-autoloader
```

At minimum, configure `APP_URL`, `API_URL`, MySQL settings, `MEMCACHED_HOST`, a newly generated `SECRET_KEY`, and new authentication key pairs. Generate or obtain new credentials for S3, SMTP, OAuth, SMS, Google, OpenAI, Telegram and reCAPTCHA only if those integrations are required.

Copy `infra/apache/api.conf.example` to an Apache site, replace domain placeholders, enable necessary Apache modules (`rewrite`, SSL when TLS is configured), enable the site and reload Apache. Obtain a new TLS certificate for the recipient's domains.

## 4. Frontend

```bash
cd /srv/eudr/frontend
cp .env.example .env.production
# Set the public API base URL to the new api hostname.
pnpm install --frozen-lockfile
pnpm build
pm2 start ../infra/pm2/ecosystem.config.cjs
pm2 save
```

Install the PM2 startup unit for the deploy user. Copy `infra/apache/app.conf.example` to an Apache site, replace the domain, enable it and reload Apache.

## 5. Acceptance checklist

- `mysql` restores `schema.sql` then `seed.sql` without error.
- API health/basic route responds through its new hostname.
- Frontend opens through its new hostname and calls only the new API hostname.
- Demo admin, farmer, purchaser, factory and trader accounts can authenticate.
- Replace or disable all demo accounts and rotate every temporary secret before real use.
- Configure backups for MySQL and uploads/object storage; perform one restore drill.

## Production data and uploads

This Git repository contains no production dump or uploaded assets. If a complete production snapshot is contractually required, transfer database dump plus `public/uploads`/object storage content through encrypted storage, validate checksums, then rotate all credentials after import.
