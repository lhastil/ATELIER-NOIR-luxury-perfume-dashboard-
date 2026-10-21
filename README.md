# ATELIER NOIR: Luxury Perfume Admin Panel

An admin panel for managing a luxury perfume store: product catalog, categories, orders, customers, messages and store analytics. Built as a portfolio project with Laravel and server-rendered Blade views.

> **Project status: early development.** The Laravel foundation and the admin layout (design tokens, sidebar, top navigation, mobile drawer) are in place. The admin features below are not built yet. They are added phase by phase and this README is updated as each one lands.

## Features

| Area | Status |
| --- | --- |
| Laravel 13 foundation, MySQL setup, test tooling | Done |
| Design tokens, typography and admin layout (sidebar, top navigation, mobile drawer) | Done |
| Dashboard content and reusable page components | Planned |
| Admin authentication (login, logout, remember me, protected routes) | Planned |
| Categories and products (CRUD, image upload, search, filters, pagination) | Planned |
| Customers, orders and order items (status updates, totals) | Planned |
| Dashboard analytics (revenue and order charts) | Planned |
| Messages, profile, password change and store settings | Planned |

## Technology stack

- **Backend:** PHP 8.3+, Laravel 13
- **Frontend:** Blade templates, HTML, custom CSS and vanilla JavaScript, bundled with Vite. Chart.js will be added for the dashboard. No CSS or JS framework.
- **Typography and icons:** Cormorant Garamond and Inter (self-hosted via Fontsource), Lucide icons as inline SVG
- **Database:** MySQL 8.4 (target). Automated tests run on SQLite in memory by default, and can also run against MySQL.
- **Testing and quality:** PHPUnit 12, Laravel Pint

## Prerequisites

- PHP 8.3 or newer with the `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl` and `fileinfo` extensions (`gd` will be needed once product image uploads are added)
- Composer 2
- Node.js 22.13+ and npm
- MySQL 8.4, either installed locally or through Docker (a `compose.yaml` is included)

## Local setup

```bash
git clone https://github.com/lhastil/ATELIER-NOIR-luxury-perfume-admin-panel.git
cd ATELIER-NOIR-luxury-perfume-admin-panel

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### Environment configuration

`.env.example` contains placeholders only. In your `.env`, set the database values for your MySQL server. Always choose your own password:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=atelier_noir
DB_USERNAME=atelier
DB_PASSWORD=choose-a-local-password
```

Never commit `.env`. It is excluded by `.gitignore`.

### MySQL with Docker (optional)

`compose.yaml` starts a MySQL 8.4 server that uses the `DB_*` values from your `.env`:

```bash
docker compose up -d --wait mysql
```

On first start it creates two databases:

- `atelier_noir`, for development
- `atelier_noir_testing`, used only by `composer test:mysql`, so test runs never touch development data

Data is stored in a named Docker volume (`<project-directory>_mysql-data`). `docker compose down` stops the server and keeps the data; `docker compose down -v` also deletes it.

The databases are only created when the volume is first initialised. If `composer test:mysql` reports an unknown `atelier_noir_testing` database (for example after changing `.env` on an existing volume), run `docker compose down -v` and start the server again. This deletes local MySQL data.

> If Docker Hub rate-limits the image download, pull the official image from Google's mirror and tag it locally:
> `docker pull mirror.gcr.io/library/mysql:8.4 && docker tag mirror.gcr.io/library/mysql:8.4 mysql:8.4`

### Database migrations and seeding

```bash
php artisan migrate
php artisan db:seed
```

> The current seeder is Laravel's default. It creates a `test@example.com` user with the password `password`. That is acceptable only while the app has no login. It will be replaced by a controlled admin-account seeder and command when authentication is added.

### Running the application

```bash
npm run build          # or `npm run dev` for hot reloading
php artisan serve
```

Then open <http://localhost:8000>.

## Testing and quality checks

```bash
composer test          # PHPUnit on SQLite in memory (fast, no server needed)
composer test:mysql    # the same suite against MySQL, using the atelier_noir_testing database
vendor/bin/pint --test # code style check (run `vendor/bin/pint` to fix)
npm run build          # production asset build
```

`composer test:mysql` uses the host, port and credentials from `.env`, and needs the MySQL server (for example `docker compose up -d --wait mysql`) and the `atelier_noir_testing` database to exist.

Run tests through these Composer scripts, which clear any cached configuration first. As a safeguard, the test suite refuses to start unless it is connected to an in-memory or `*_testing` database, so it can never reset development data.

## Project structure

Standard Laravel layout, plus:

- `resources/css/base/`: design tokens (`tokens.css`), fonts and global styles
- `resources/css/layout/`: application shell, sidebar and top navigation
- `resources/css/components/`: styles for shared layout components
- `resources/js/ui/`: navigation drawer and dropdown behaviour (vanilla JavaScript)
- `resources/views/components/`: Blade components (`layouts/admin`, `admin/*`, `icon`, `avatar`, `dropdown`, `page-header`)
- `compose.yaml`: local MySQL server
- `docker/mysql/init/`: one-time MySQL initialisation (creates the testing database)

## Design system

All colours, type sizes, spacing (4px scale), radii, shadows and motion timings are CSS custom properties in `resources/css/base/tokens.css`. The palette follows the ATELIER NOIR specification. Two darker shades of the specified secondary text and gold colours (`--color-text-secondary`, `--color-accent-strong`) are used for small text so that it meets WCAG AA contrast. The specified `#858078` and `#A48A60` are kept for large text, icons and decoration.

The layout has three breakpoints: desktop (1024px and up) with a persistent 248px sidebar, tablet (768–1023px) and mobile (below 768px), where the sidebar becomes an accessible slide-in drawer. Transitions are 150–220ms and are switched off when the user prefers reduced motion.

## License

Released under the [MIT License](LICENSE). Third-party fonts and icons are listed in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## Known limitations

- Only the layout exists. The dashboard page shows a temporary "In preparation" notice, and sections that are not built yet appear in the navigation as unavailable.
- There is no login yet, so the admin layout is publicly reachable during local development. Search is shown disabled until the catalogue exists.
- Screenshots will be added once the interface is built.
