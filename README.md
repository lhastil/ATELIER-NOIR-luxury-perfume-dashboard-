# ATELIER NOIR: Luxury Perfume Admin Panel

An admin panel for managing a luxury perfume store: product catalog, categories, orders, customers, messages and store analytics. Built as a portfolio project with Laravel and server-rendered Blade views.

> **Project status: early development.** The Laravel foundation, the admin layout (design tokens, sidebar, top navigation, mobile drawer), the dashboard structure, administrator login and product management are in place. The dashboard shows empty states until order data exists. The other admin features below are not built yet; they are added phase by phase and this README is updated as each one lands.

## Features

| Area | Status |
| --- | --- |
| Laravel 13 foundation, MySQL setup, test tooling | Done |
| Design tokens, typography and admin layout (sidebar, top navigation, mobile drawer) | Done |
| Dashboard structure and reusable content components (empty states until store data exists) | Done |
| Admin authentication (login, logout, login throttling, protected routes) | Done |
| Products (create, edit, delete, image upload, search, filters, pagination) and seeded categories | Done |
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
git clone https://github.com/lhastil/ATELIER-NOIR-luxury-perfume-dashboard-.git
cd ATELIER-NOIR-luxury-perfume-dashboard-

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan storage:link   # makes uploaded product images publicly reachable
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

Seeding creates the demo administrator described below, the product categories and the sample catalog. It can be run again safely: nothing is duplicated, and products you have edited are not overwritten. Running it again does reset the demo administrator's name and password to the values below.

### Sample data

The seeded catalog is **sample data for demonstration**: 15 fictional perfumes from fictional brands, spread over five categories (Floral, Woody, Amber, Fresh and Gourmand). It contains no real products, prices or stock levels, and the sample products have no images. Products created in the panel are stored the same way and behave exactly like the seeded ones.

- `database/seeders/CategorySeeder.php`: the five categories. Categories have no management page yet, so this seeder is also needed on a fresh installation.
- `database/seeders/SampleProductSeeder.php`: the sample products (`php artisan db:seed --class=SampleProductSeeder`, which also seeds the categories). Sample products are matched by SKU, so if you change a sample product's SKU in the panel, the next seed run adds the original sample again.

### Demo administrator (local only)

| | |
| --- | --- |
| Email | `admin@atelier-noir.test` |
| Password | `atelier-demo` |

These credentials are published in this README on purpose, so anyone can try the project locally. They are for local demonstration only: never use them, or this account, on a real or publicly reachable installation. The seeder (`database/seeders/DemoAdminSeeder.php`) refuses to run when `APP_ENV=production`, and the password is stored hashed like any other.

To create only this account: `php artisan db:seed --class=DemoAdminSeeder`. Running it again resets the account's name and password to the values above.

### Running the application

```bash
npm run build          # or `npm run dev` for hot reloading
php artisan serve
```

Then open <http://localhost:8000> and sign in with the demo administrator. Every admin page requires signing in; there is no public registration.

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
- `resources/css/components/`: styles for shared layout and content components
- `resources/css/pages/`: page-specific layout (dashboard grid)
- `resources/js/ui/`: navigation drawer, dropdown and scrollable-table behaviour (vanilla JavaScript)
- `resources/views/components/`: Blade components (`layouts/admin`, `admin/*`, `icon`, `avatar`, `dropdown`, `page-header`, `card`, `stat-card`, `chart-panel`, `empty-state`, `table`)
- `compose.yaml`: local MySQL server
- `docker/mysql/init/`: one-time MySQL initialisation (creates the testing database)

## Business rules

- **Prices** are shown in euros. The currency is set once, with `APP_CURRENCY` in `.env` (read in `config/app.php`).
- **Product images** are stored on Laravel's `public` disk in `storage/app/public/products`. Replacing or removing an image, or deleting its product, deletes only that product's file.
  Image links are built from `APP_URL`, so set it to the address you open the panel at (for example `http://localhost:8000` with `php artisan serve`), and run `php artisan storage:link` once.
- **Revenue** counts only orders with the status `delivered`. Pending, processing, cancelled and refunded orders are excluded. The rule will live in one place in the code when orders are implemented, so it can be changed deliberately.

## Design system

All colours, type sizes, spacing (4px scale), radii, shadows and motion timings are CSS custom properties in `resources/css/base/tokens.css`. The palette follows the ATELIER NOIR specification. Two darker shades of the specified secondary text and gold colours (`--color-text-secondary`, `--color-accent-strong`) are used for small text so that it meets WCAG AA contrast. The specified `#858078` and `#A48A60` are kept for large text, icons and decoration.

The layout has three breakpoints: desktop (1024px and up) with a persistent 248px sidebar, tablet (768–1023px) and mobile (below 768px), where the sidebar becomes an accessible slide-in drawer. Transitions are 150–220ms and are switched off when the user prefers reduced motion.

## License

Released under the [MIT License](LICENSE). Third-party fonts and icons are listed in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## Known limitations

- The dashboard has no data source yet: every key figure, chart and list shows an empty state, and no sample figures are used. Charts (Chart.js) and real figures are added once orders, products and customers are stored.
- Sections that are not built yet appear in the navigation as unavailable.
- Authentication covers login and logout only: there is no registration, password reset, "remember me" or role system. Administrator accounts are created by seeders.
- The search field in the top bar is not connected yet; products are searched and filtered on the Products page.
- Categories are fixed by the seeder; there is no page to add or rename them.
- Screenshots will be added once the interface is built.
