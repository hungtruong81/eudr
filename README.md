# EUDR — full handover bundle

This repository is a single deployment bundle for the EUDR application.

- `frontend/`: Next.js 15 application
- `api/`: PHP 8.2+/Slim API source
- `database/`: MySQL schema, safe synthetic seed and restore script
- `infra/`: Apache and PM2 examples
- `docs/`: installation and handover notes

## Fast path

1. Follow `docs/INSTALL_VPS.md`.
2. Create `api/config/.env` from `api/config/.env.example` and fill new secrets.
3. Create `frontend/.env.production` from `frontend/.env.example` and point it to the new API URL.
4. Restore MySQL using `database/restore.sh`.
5. Build and start frontend with PM2; enable Apache virtual hosts.

The repository deliberately excludes runtime secrets, source dependencies, logs, uploads and production/test data. See `database/README.md`.
