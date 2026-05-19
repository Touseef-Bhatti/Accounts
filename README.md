# Bhatti Export Documents

PHP web application for **Bhatti Trader** and **Bhatti Chemicals Industry** to create export document packages:

- Proforma Invoice  
- Commercial Invoice  
- Packing List  
- Export Sales Contract  

Features: OTP email login, MySQL storage, field suggestions, company logos, PDF download, Docker & shared hosting support.

## Quick start (Docker)

1. Copy environment file:
   ```bash
   copy .env.example .env
   ```
2. Edit `.env` — set `AUTHORIZED_EMAILS` (SMTP defaults to **MailHog** for local Docker).
3. Start containers:
   ```bash
   docker compose up -d --build
   ```
4. Install dependencies inside web container (first time):
   ```bash
   docker compose exec web composer install
   ```
5. Open **http://localhost:8080/install.php** and click **Run Installation**.
6. Login at **http://localhost:8080/login.php** — OTP emails appear in **MailHog**: http://localhost:8025
7. phpMyAdmin: **http://localhost:8081** (user `bhatti`, password `bhatti_secret`).

### Local email (MailHog)

OTP messages are sent to MailHog automatically when using Docker. Open http://localhost:8025 to read them — no real SMTP account needed on localhost. For production, switch `MAIL_*` in `.env` to your real SMTP provider.

## Shared hosting (cPanel / Plesk)

1. Upload all files to `public_html` (or subdomain folder).
2. Create MySQL database and user in hosting panel.
3. Copy `.env.example` to `.env` and set:
   - `DB_HOST=localhost` (often `localhost` on shared hosting)
   - `DB_NAME`, `DB_USER`, `DB_PASS`
   - `APP_URL=https://yourdomain.com`
   - `AUTHORIZED_EMAILS=your@email.com`
   - SMTP settings for OTP mail
4. Run **Composer** locally or via SSH:
   ```bash
   composer install --no-dev
   ```
   Upload the `vendor/` folder if you cannot run Composer on the server.
5. Set folder permissions: `uploads/` and `storage/` writable (755 or 775).
6. Visit `https://yourdomain.com/install.php` once, then **delete or rename** `install.php`.
7. Use the site via `login.php`.

## Workflow

1. **Login** — OTP email to authorized addresses only.  
2. **Select company** — Bhatti Trader or Bhatti Chemicals Industry.  
3. **Create documents** — tabbed forms with line items and autocomplete from past entries.  
4. **Review** — preview and download individual or combined PDFs.  
5. **Settings** — upload logo and edit company/bank details per account.

## Environment variables

| Variable | Description |
|----------|-------------|
| `AUTHORIZED_EMAILS` | Comma-separated emails allowed to log in |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP for OTP |
| `APP_DEBUG=true` | Shows OTP on screen if email fails (dev only) |
| `DB_*` | MySQL connection |

## Security notes

- Remove `install.php` after setup on production.  
- Keep `.env` outside public access (`.htaccess` blocks it).  
- Use HTTPS on production for OTP and sessions.

## File structure

```
install.php          # DB schema + seed (run once)
index.php            # Company selection
login.php            # OTP authentication
documents/create.php # Forms
documents/review.php # Preview + PDF links
documents/pdf.php    # PDF generator
settings.php         # Logo & company profile
api/suggestions.php  # Autocomplete API
```

## Customizing PDF templates

Edit files in `templates/` (`proforma_pdf.php`, `commercial_pdf.php`, etc.) to match your exact letterhead layout. Upload logos via **Settings**.
