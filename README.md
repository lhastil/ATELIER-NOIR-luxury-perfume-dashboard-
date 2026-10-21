# ATELIER NOIR: Luxury Perfume Admin Panel

An admin panel for managing a luxury perfume store: product catalog, categories, orders, customers, messages and store analytics. Built as a portfolio project with Laravel and server-rendered Blade views.

> **Project status: early development.** The Laravel foundation, the admin layout (design tokens, sidebar, top navigation, mobile drawer), the dashboard structure, administrator login, product management, customers, orders and the dashboard figures and charts are in place. The other admin features below are not built yet; they are added phase by phase and this README is updated as each one lands.

## Features

| Area | Status |
| --- | --- |
| Laravel 13 foundation, MySQL setup, test tooling | Done |
| Design tokens, typography and admin layout (sidebar, top navigation, mobile drawer) | Done |
| Dashboard structure and reusable content components | Done |
| Admin authentication (login, logout, login throttling, protected routes) | Done |
| Products (create, edit, delete, image upload, search, filters, pagination) and seeded categories | Done |
| Customers (search, details, order history) and orders (search, status filter, details, totals, status updates) | Done |
| Dashboard figures, revenue and order charts (Chart.js), recent orders and best sellers from the stored data | Done |
| Messages, profile, password change and store settings | Planned |

## Technology stack

- **Backend:** PHP 8.3+, Laravel 13
- **Frontend:** Blade templates, HTML, custom CSS and vanilla JavaScript, bundled with Vite. Chart.js draws the dashboard charts and is loaded only on pages that have a chart. No CSS or JS framework.
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

Seeding creates the product categories, the demo administrator described below and the sample catalog, customers and orders. It can be run again safely: nothing is duplicated, products you have edited are not overwritten, and order statuses you have changed are kept. Running it again does reset the demo administrator's name and password to the values below.

In production (`APP_ENV=production`), `php artisan db:seed --force` creates only the five categories: the demo administrator and all sample data seeders skip themselves there.

### Sample data

The seeded catalog, customers and orders are **sample data for demonstration**:

- 15 fictional perfumes from fictional brands, spread over five categories (Floral, Woody, Amber, Fresh and Gourmand), with no images. There are no real products, prices or stock levels.
- 24 fictional customers. Email addresses use the reserved `example.test` domain. Phone numbers come from the `06 39 98` block that Arcep, the French telecoms regulator, reserves for films and other fiction; they are stored in international format (`+33639980001`) and shown in the French format (`06 39 98 00 01`). Customer search finds a phone number typed with or without spaces, and with either `06…` or `+33 6…`. Three customers have no orders yet.
- 40 orders (`ORD-10001` to `ORD-10040`) with one to three items each, across every order status. Order dates count back up to 118 days from the day the orders are first seeded.

Records created or changed in the panel are stored the same way and behave exactly like the seeded ones.

- `database/seeders/CategorySeeder.php`: the five categories. Categories have no management page yet, so this seeder is also needed on a fresh installation.
- `database/seeders/SampleProductSeeder.php`: the sample products (`php artisan db:seed --class=SampleProductSeeder`, which also seeds the categories). Sample products are matched by SKU, so if you change a sample product's SKU in the panel, the next seed run adds the original sample again.
- `database/seeders/CustomerSeeder.php`: the sample customers, matched by email.
- `database/seeders/SampleOrderSeeder.php`: the sample orders (`php artisan db:seed --class=SampleOrderSeeder`, which also seeds the customers, products and categories). Orders are matched by number, and items are added only when an order is first created.

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
- `resources/css/pages/`: page-specific layout (dashboard, login, products, orders)
- `resources/js/ui/`: navigation drawer, dropdown, scrollable-table, image preview, delete-confirmation and dashboard chart behaviour (vanilla JavaScript)
- `resources/views/components/`: Blade components (`layouts/admin`, `admin/*`, `form/*`, `icon`, `avatar`, `badge`, `flash`, `dropdown`, `page-header`, `card`, `stat-card`, `chart-panel`, `empty-state`, `table`)
- `resources/views/products/`, `customers/`, `orders/`: the admin pages
- `compose.yaml`: local MySQL server
- `docker/mysql/init/`: one-time MySQL initialisation (creates the testing database)

## Database relationships

The Eloquent relationships defined in `app/Models`:

| Model | Relationship | Purpose |
| --- | --- | --- |
| `Category` | `products()`: has many `Product` | The products in a perfume family. |
| `Product` | `category()`: belongs to `Category` | The family a product is listed under. A category cannot be deleted while products use it. |
| `Customer` | `orders()`: has many `Order` | A customer's order history. A customer cannot be deleted while they have orders. |
| `Order` | `customer()`: belongs to `Customer` | Who placed the order. |
| `Order` | `items()`: has many `OrderItem` | The product lines of the order; they are deleted together with the order. |
| `OrderItem` | `order()`: belongs to `Order` | The order the line belongs to. |
| `OrderItem` | `product()`: belongs to `Product` | The catalog product, used to link to it. It is empty once the product has been deleted. |

Each order item keeps its own copy of the product name, SKU and unit price from when the order was placed, so editing or deleting a product never changes an existing order.

## Business rules

- **Prices** are shown in euros. The currency is set once, with `APP_CURRENCY` in `.env` (read in `config/app.php`).
- **Product images** are stored on Laravel's `public` disk in `storage/app/public/products`. Replacing or removing an image, or deleting its product, deletes only that product's file.
  Image links are built from `APP_URL`, so set it to the address you open the panel at (for example `http://localhost:8000` with `php artisan serve`), and run `php artisan storage:link` once.
- **Order statuses** are `pending`, `processing`, `shipped`, `delivered` and `cancelled` (`Order::STATUSES`). An administrator can set any of them on the order page; other values are rejected. Changing a status does not change product stock.
- **Order totals** are always calculated from the stored order items (quantity × unit price, added up in whole cents); no separate total is stored.
- **Revenue** counts only orders with the status `delivered` (`Order::delivered()`); pending, processing, shipped and cancelled orders are excluded. It is added up in whole cents from the order items' stored quantities and unit prices, and formatted in euros only for display.
- **Revenue month:** there is no delivery date, so a delivered order's revenue is counted in the month the order was **placed** (`placed_at`), not the month it was delivered.

### Dashboard figures

All figures are read from the database on each visit; nothing is stored or cached.

| Section | Rule |
| --- | --- |
| Total Revenue | Revenue from every delivered order, all time. |
| Total Orders | Every order, in all five statuses. |
| Products in Catalog | Every product, hidden ones included; the number of active products is shown below it. |
| Total Customers | Every customer. |
| Revenue chart | Delivered revenue per calendar month of `placed_at` (UTC) for the last six months, including the current month. Months without delivered orders show €0. |
| Order Analytics | The number of orders in each status, in the fixed order pending, processing, shipped, delivered, cancelled, including statuses with no orders. |
| Recent Orders | The five newest orders by `placed_at`, newest first (the most recently created first when two have the same time). |
| Best Sellers | The five products with the most units in **delivered** orders, grouped by the SKU stored on the order item and shown with its stored product name, so products deleted since still count. If a product was renamed between orders, the name from its most recently placed delivered order is shown (the most recently created order or item when two share a time). Ties in units are ordered by SKU. |

The key figures show their real value, including `0` and `€0.00`. The charts and lists show an explanatory empty state when they have nothing to display. Each chart also lists its figures in a table for screen readers, and chart animation is switched off when the system asks for reduced motion. Without JavaScript, or if the chart script cannot be loaded, each chart panel shows a short note and its figures as a visible table instead.

## Design system

All colours, type sizes, spacing (4px scale), radii, shadows and motion timings are CSS custom properties in `resources/css/base/tokens.css`. The palette follows the ATELIER NOIR specification. Two darker shades of the specified secondary text and gold colours (`--color-text-secondary`, `--color-accent-strong`) are used for small text so that it meets WCAG AA contrast. The specified `#858078` and `#A48A60` are kept for large text, icons and decoration.

The layout has three breakpoints: desktop (1024px and up) with a persistent 248px sidebar, tablet (768–1023px) and mobile (below 768px), where the sidebar becomes an accessible slide-in drawer. Transitions are 150–220ms and are switched off when the user prefers reduced motion.

## License

Released under the [MIT License](LICENSE). Third-party fonts and icons are listed in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## Known limitations

- The dashboard has no date filter or comparison with earlier periods. The sample orders are dated relative to the day they were first seeded, so as time passes their revenue moves out of the six-month chart.
- The dashboard loads every delivered order with its items to add up revenue, which is fine for a store of this size but would need a database-level sum for a very large order history.
- Sections that are not built yet appear in the navigation as unavailable.
- Authentication covers login and logout only: there is no registration, password reset, "remember me" or role system. The only administrator account is the local demo account created by the seeder; a production installation needs another way to create its first administrator, which is planned for the deployment phase.
- The search field in the top bar is not connected yet; products are searched and filtered on the Products page.
- Categories are fixed by the seeder; there is no page to add or rename them.
- Customers and orders are read-only apart from the order status: there are no pages to add, edit or delete them, and orders come only from the seeder.
- Screenshots will be added once the interface is built.
