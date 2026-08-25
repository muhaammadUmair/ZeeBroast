# ZeeBroast — Crispy Broast Chicken Ordering Website

A complete PHP + MySQL restaurant ordering website for ZeeBroast, built to match the
provided design (dark theme, red accent, Poppins typography) with a full storefront,
cart/checkout/payment flow, order tracking, customer accounts, and an admin panel.

> **Note on images:** the design mockup's food photography isn't included as source
> assets. Every product, category and deal has an `image` column in the database and
> a working upload feature in the admin panel — until a photo is uploaded, the
> storefront shows a styled emoji placeholder tile (matching the app's color scheme)
> in its place. Uploading a real photo from **Admin → Products/Categories/Deals**
> replaces the placeholder immediately, site-wide, with no code changes needed.

## Requirements

- PHP 8.0+ with the `pdo_mysql` extension
- MySQL 5.7+ / MariaDB 10.3+
- Apache (with `mod_rewrite`/`.htaccess` support) or Nginx, or PHP's built-in server for local testing

## Setup

1. **Create the database** — import the schema (creates the database, tables, and seed data):
   ```bash
   mysql -u root -p < database/schema.sql
   ```
2. **Configure the database connection** — either edit `config/database.php` directly,
   or set environment variables:
   ```
   DB_HOST=localhost
   DB_NAME=zeebroast
   DB_USER=your_db_user
   DB_PASS=your_db_password
   ```
3. **Point your web server's document root at the project root** (this folder), so that
   `/assets`, `/admin`, `/api` and `/uploads` are all reachable from the site root.
4. **Make `/uploads` writable** by the web server user (used for product/category/deal
   image uploads from the admin panel):
   ```bash
   chmod -R 755 uploads
   ```
5. Visit the site at your configured host, and the admin panel at `/admin/login.php`.

### Default admin login

```
Email:    admin@zeebroast.com
Password: Admin@123
```

**Change this password immediately after first login** (Admin → Admin Users, or update
directly in the `admin_users` table).

## Local testing with PHP's built-in server

```bash
DB_HOST=localhost DB_NAME=zeebroast DB_USER=root DB_PASS= php -S localhost:8000
```
Then open `http://localhost:8000/`.

## Project structure

```
/                     Storefront pages (index, menu, category, deals, cart, checkout, ...)
/admin/               Admin panel (dashboard, products, categories, deals, orders, coupons,
                       customers, messages, site settings, admin users)
/api/cart_action.php  AJAX endpoint for add/update/remove cart operations
/assets/              CSS/JS for the storefront
/config/              Database connection + app bootstrap (session, constants)
/database/schema.sql  Full database schema with seed data (categories, products, deals, admin)
/includes/            Shared PHP includes (header, footer, helper functions)
/uploads/             Admin-uploaded product/category/deal images (PHP execution disabled here)
```

## Features

**Storefront**
- Home page: hero, trust badges, signature menu, deals, delivery banner
- Menu page with category filter, price filter, sorting, and pagination
- Category overview + individual category listing pages
- Deals & combos page
- Product detail page with related items
- Cart → Checkout (delivery/takeaway, address, scheduling) → Payment (COD/JazzCash/EasyPaisa/Card) → Order confirmation
- Order tracking by Order ID
- Customer accounts: register, login, order history
- Contact form (saved to database) and About page

**Admin panel**
- Dashboard with revenue/order stats
- Categories, products and deals CRUD with image upload
- Order management with status + payment status updates
- Coupons management
- Customer list with block/unblock
- Contact message inbox
- Site-wide settings (branding, hero text, contact info, delivery fees, socials)
- Admin user management with roles (super admin / manager / staff)

## Security notes

- Passwords are hashed with `password_hash()` / verified with `password_verify()`.
- All forms are protected with CSRF tokens.
- All database queries use PDO prepared statements.
- `/config`, `/database` and dotfiles are blocked from direct web access via `.htaccess`.
- PHP execution is disabled inside `/uploads` to prevent uploaded-file exploits.
