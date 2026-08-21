# Deployment notes

The old server used Apache as a reverse proxy for the frontend on port 3000 and as the API document-root server. PM2 ran `pnpm start` in `frontend/`. MySQL and Memcached were local services.

Use `infra/` as a template only. Replace all domains, paths, users and certificate references for the recipient's VPS. Production TLS, firewall, backups, log rotation, monitoring and a non-root deployment account are mandatory operational work.
