# Troubleshooting

## Installer Cannot Connect
- Confirm MariaDB is running.
- Confirm `.env` database values match the local database and user.
- Confirm PHP has `pdo_mysql`, `openssl`, `zip`, `intl`, and `fileinfo`.

## Admin 403
- Confirm the user has `is_admin=1` and `is_disabled=0`.

## SMTP Fails
- Re-enter the password; encrypted passwords are not exported.
- Check the `email_delivery_logs` table for non-secret status details.

## AI Models Do Not Refresh
- Confirm the provider API key is valid.
- Use the manual fallback model list if the provider API is unavailable.

## Import Or Update Fails
- Run a dry run first.
- Restore the pre-import or pre-update backup before retrying.
