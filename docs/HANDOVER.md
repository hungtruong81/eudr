# Handover notes

## Snapshot source

- Frontend source was assembled from the server checkout that ran the Next.js application.
- API source was assembled from the live Apache document root, because the separate API Git checkout on the server had a heavily deleted working tree and was not reliable as the current deploy source.
- No existing `ngocquang` remote was modified or pushed.

## Excluded deliberately

- `.env` values and all third-party credentials
- JWT/authentication private keys
- dependency directories (`node_modules`, `vendor`) and frontend build output
- logs, runtime storage, uploaded files and raw database contents

## Follow-up ownership actions

1. Recipient creates and owns all new domain/DNS, VPS, database, object storage and third-party integration accounts.
2. Recipient generates new app/JWT/database credentials and configures them outside Git.
3. If sharing a data snapshot, use encrypted transfer and a separate password channel.
4. Do not reuse legacy password hashing for new product work; schedule password-hash migration.
5. Before go-live, run the acceptance checklist in `INSTALL_VPS.md`.
