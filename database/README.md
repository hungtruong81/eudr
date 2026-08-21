# Database restore

`schema.sql` contains the complete MySQL structure exported from the running EUDR API database. `seed.sql` contains synthetic demo companies, roles and accounts only.

Demo roles included:

- admin
- farmer (nông hộ)
- purchaser (thu mua)
- factory (nhà máy)
- trader

All demo accounts use the temporary password `ChangeMe-EUDR-2026!`. Change or disable these accounts immediately after the first login. The current API uses a legacy `md5(md5(password + salt))` format; plan a password migration before production use.

## Restore

```bash
export DB_NAME=eudr
export DB_USER=eudr
export DB_PASSWORD='use-a-secret-manager'
./database/restore.sh
```

Do not commit production dumps, `.env` files, API keys, JWT keys, password hashes, logs or uploaded files to GitHub. If a production/test data snapshot is required for the commercial handover, transfer it separately through encrypted storage.
