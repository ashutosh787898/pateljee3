# JaiClub Pro — Vercel + Aiven

This version is prepared for the Vercel community PHP runtime and an Aiven MySQL database.

## Project layout

- `api/` — PHP serverless entrypoints and private PHP includes
- `images/` — static images
- `style.css`, `app.js`, `admin-style.css`, `admin-app.js` — static assets
- `vercel.json` — Vercel PHP runtime/routing configuration
- `database.sql` — fresh MySQL schema

## Required Vercel Environment Variables

### Aiven MySQL

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `SITE_URL`

### Jalwa API

- `JALWA_BASE_URL`
- `JALWA_USERNAME`
- `JALWA_PASSWORD`
- `JALWA_LANGUAGE` (normally `0`)

Do not put these values into PHP source code or GitHub.

## Database

Run `database.sql` against the existing `jaiclub` database before opening the deployed site.

## Aiven CA certificate

Copy your downloaded Aiven `ca.pem` into `api/ca.pem`.

## Deployment

The project uses `vercel-php@0.9.0`. PHP files are routed from the public `.php` URLs to the matching files in `api/` while CSS/JS/images remain static at the project root.

Do not restore `setup.php` to production.
