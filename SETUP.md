# Project Setup & Installation Guide

This guide provides simple, step-by-step instructions to set up and run the **Invoice Management System** on another computer (Windows, macOS, Linux) or a web server.

Even if you are not a developer, you can follow each step in order to get the project working smoothly.

---

## Table of Contents
1. [System Requirements](#1-system-requirements)
2. [Step-by-Step Installation Guide](#2-step-by-step-installation-guide)
   - [Step 1: Download or Clone the Project](#step-1-download-or-clone-the-project-required)
   - [Step 2: Install PHP Dependencies (Composer)](#step-2-install-php-dependencies-composer-required)
   - [Step 3: Install Frontend Dependencies (NPM)](#step-3-install-frontend-dependencies-npm-required)
   - [Step 4: Create the Environment File (.env)](#step-4-create-the-environment-file-env-required)
   - [Step 5: Generate the Application Encryption Key](#step-5-generate-the-application-encryption-key-required)
   - [Step 6: Create the Database & Configure `.env`](#step-6-create-the-database--configure-env-required)
   - [Step 7: Configure Other Environment Variables](#step-7-configure-other-environment-variables-recommended)
   - [Step 8: Run Database Migrations & Seed Demo Data](#step-8-run-database-migrations--seed-demo-data-required)
   - [Step 9: Create the Public Storage Link](#step-9-create-the-public-storage-link-required)
   - [Step 10: Configure File Permissions (Linux / macOS / VPS)](#step-10-configure-file-permissions-linux--macos--vps-required-on-linux)
   - [Step 11: Compile Frontend Assets](#step-11-compile-frontend-assets-required)
   - [Step 12: Start the Application](#step-12-start-the-application-required)
3. [Sample `.env` Configuration](#3-sample-env-configuration)
4. [Demo Accounts & Login Information](#4-demo-accounts--login-information)
5. [Useful Daily Commands](#5-useful-daily-commands)
6. [Troubleshooting & Common Errors](#6-troubleshooting--common-errors)

---

## 1. System Requirements

Before you begin, make sure your computer or server has the following software installed:

| Software | Minimum Version | Recommended Version | Purpose |
| :--- | :--- | :--- | :--- |
| **PHP** | `8.2.0` | `8.2` or `8.3` / `8.4` | Backend runtime |
| **Composer** | `2.2+` | `2.7+` | PHP package manager |
| **Node.js** | `18.x` | `20.x` or `22.x LTS` | Frontend asset compiler |
| **NPM** | `9.x+` | `10.x+` | Node package manager |
| **MySQL / MariaDB** | MySQL `8.0+` or MariaDB `10.4+` | MySQL `8.0+` | Database server |
| **Git** | `2.x+` | Latest | Version control / cloning |

### Required PHP Extensions
Ensure these PHP extensions are enabled in your `php.ini` file (all are standard in modern PHP / XAMPP / Herd / Docker installations):
- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `filter`
- `gd` *(Required for image manipulation and PDF rendering)*
- `hash`
- `mbstring`
- `openssl`
- `pcre`
- `pdo`
- `pdo_mysql` *(Required for MySQL database connection)*
- `session`
- `tokenizer`
- `xml`
- `zip`

> **Tip for Windows (XAMPP):** Open the XAMPP Control Panel, click **Config** next to Apache -> `php.ini`, search for `;extension=gd` or `;extension=zip`, remove the semicolon `;` at the beginning of the line, and restart Apache.

---

## 2. Step-by-Step Installation Guide

Follow these steps in the exact order shown.

### Step 1: Download or Clone the Project `[Required]`

#### Option A: Using Git (Recommended)
Open your terminal or command prompt and clone the repository:
```bash
git clone <repository-url> "invoice-system"
cd "invoice-system"
```

#### Option B: From a ZIP file
1. Extract the downloaded ZIP file to your preferred folder (e.g., `C:\xampp\htdocs\invoice-system` or `/var/www/invoice-system`).
2. Open your terminal / command prompt and navigate into the folder:
   ```bash
   cd path/to/invoice-system
   ```

---

### Step 2: Install PHP Dependencies (Composer) `[Required]`

This command downloads Laravel framework libraries and packages (such as `barryvdh/laravel-dompdf` for generating PDF invoices).

Run the following command in the project root:
```bash
composer install
```

> **What this does:** Reads `composer.json` and installs all PHP packages into the `vendor/` directory.

---

### Step 3: Install Frontend Dependencies (NPM) `[Required]`

This command installs Vite and Tailwind CSS to compile project assets:
```bash
npm install
```

> **What this does:** Reads `package.json` and installs frontend build tools into the `node_modules/` directory.

---

### Step 4: Create the Environment File (`.env`) `[Required]`

Laravel uses a configuration file called `.env` in the root of the project to store your database password, app URL, and settings. This file is kept private and is not tracked in Git.

Duplicate the provided `.env.example` file to create your own `.env`:

- **On Linux / macOS:**
  ```bash
  cp .env.example .env
  ```
- **On Windows (Command Prompt):**
  ```cmd
  copy .env.example .env
  ```
- **On Windows (PowerShell):**
  ```powershell
  Copy-Item .env.example .env
  ```

---

### Step 5: Generate the Application Encryption Key `[Required]`

Laravel requires a 32-character encryption key for sessions, user passwords, and cookies.

Run:
```bash
php artisan key:generate
```

> **What this does:** Automatically creates a unique key and fills in the `APP_KEY=base64:...` variable inside your `.env` file.

---

### Step 6: Create the Database & Configure `.env` `[Required]`

#### 1. Create a MySQL Database
Open your database management tool (such as **phpMyAdmin**, **MySQL Workbench**, **DBeaver**, or the MySQL command line) and create an empty database with `utf8mb4` encoding.

**Using MySQL Command Line:**
```sql
CREATE DATABASE invoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 2. Enter Database Details into `.env`
Open the `.env` file in any text editor (VS Code, Notepad, Sublime) and locate the database lines (around line 23). Update them to match your MySQL setup:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoice
DB_USERNAME=root
DB_PASSWORD=
```

- **`DB_CONNECTION`**: Always keep as `mysql`.
- **`DB_HOST`**: Usually `127.0.0.1` or `localhost`.
- **`DB_PORT`**: Standard MySQL port is `3306`.
- **`DB_DATABASE`**: The name of the database you just created (e.g., `invoice`).
- **`DB_USERNAME`**: Your MySQL username (e.g., `root` on local XAMPP/WAMP/Herd).
- **`DB_PASSWORD`**: Your MySQL user password (leave blank `DB_PASSWORD=` if using XAMPP default without password).

---

### Step 7: Configure Other Environment Variables `[Recommended]`

In the same `.env` file, check and update the following values:

#### Application Details:
```env
APP_NAME="Invoice Manager"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```
- **`APP_URL`**: Set this to the exact URL where you access your site (e.g., `http://127.0.0.1:8000` or `https://yourdomain.com`). This ensures links in PDFs and emails point to the correct address.
- **`APP_DEBUG`**: Keep `true` for local development. Set to `false` on a live production server for security.

#### Mail / SMTP Settings (Optional):
For local development, email sending is set to `log` by default (emails are written to `storage/logs/laravel.log` so no mail server is needed):
```env
MAIL_MAILER=log
```

If you want to send actual emails (e.g., via Mailtrap, Gmail, or your hosting SMTP), configure:
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

### Step 8: Run Database Migrations & Seed Demo Data `[Required]`

#### 1. Run the Migrations `[Required]`
Create all the necessary tables (users, customers, invoices, invoice items, payments, sessions, and cache):
```bash
php artisan migrate
```

#### 2. Seed Demo Data `[Optional but Recommended]`
If you want sample test data (a default login user, sample customers, invoices, and payment records for the dashboard charts):

- **Seed Default User & Customers:**
  ```bash
  php artisan db:seed
  ```
  *Creates test account `test@example.com` with password `Password123` and 15 sample customers.*

- **Seed Dashboard Demo Invoices & Charts:**
  ```bash
  php artisan db:seed --class=DashboardSeeder
  ```
  *Populates realistic invoices across recent months so the dashboard revenue chart, status widgets, and recent tables display sample data right away.*

---

### Step 9: Create the Public Storage Link `[Required]`

Users can upload profile photos and avatars in the application. Laravel saves these files in `storage/app/public/` and exposes them through a symbolic link in `public/storage`.

Run:
```bash
php artisan storage:link
```

> **What this does:** Creates a shortcut link from `public/storage` pointing to `storage/app/public` so images can load in the browser.

---

### Step 10: Configure File Permissions (Linux / macOS / VPS) `[Required on Linux]`

If you are running on Ubuntu, Debian, CentOS, or a live VPS, the web server user (`www-data` or `nginx`) needs write permissions to the `storage/` and `bootstrap/cache/` directories.

Run these commands from your project root:
```bash
# Set ownership to your user and the web server group
sudo chown -R $USER:www-data storage bootstrap/cache

# Grant read, write, and execute permissions
sudo chmod -R 775 storage bootstrap/cache
```

*(On Windows with XAMPP or Herd, this step is handled automatically by Windows file permissions).*

---

### Step 11: Compile Frontend Assets `[Required]`

Build the frontend styles and scripts using Vite:

```bash
npm run build
```

> **For Development with Live Hot-Reloading:**
> If you are developing and modifying CSS/JS files actively, you can run:
> ```bash
> npm run dev
> ```
> *(Leave that terminal open while you work).*

---

### Step 12: Start the Application `[Required]`

Start the built-in Laravel development server:

```bash
php artisan serve
```

Your terminal will display:
```
INFO  Server running on [http://127.0.0.1:8000].
```

Open your web browser and navigate to:
👉 **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

---

## 3. Sample `.env` Configuration

Below is a safe reference `.env` file with placeholder values:

```env
APP_NAME="Invoice Manager"
APP_ENV=local
APP_KEY=base64:GENERATE_WITH_PHP_ARTISAN_KEY:GENERATE
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug

# Database Configuration (Modify these for your computer/server)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoice
DB_USERNAME=root
DB_PASSWORD=

# Session and Cache (Handled automatically via database tables)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

# Email Configuration (Set to 'log' for local testing)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

---

## 4. Demo Accounts & Login Information

If you ran the seeders in **Step 8**, you can log in with:

| Field | Value |
| :--- | :--- |
| **Login URL** | `http://127.0.0.1:8000/login` |
| **Email** | `test@example.com` |
| **Password** | `Password123` |

You can also click **Register** on the login page to create a brand new administrator account.

---

## 5. Useful Daily Commands

| Task | Command | Description |
| :--- | :--- | :--- |
| **Start local server** | `php artisan serve` | Starts web server on `http://127.0.0.1:8000` |
| **Clear application cache** | `php artisan optimize:clear` | Clears config, routes, views, and app cache |
| **Re-run fresh migrations** | `php artisan migrate:fresh --seed` | ⚠️ Deletes all tables and recreates sample data |
| **Compile assets for production** | `npm run build` | Builds minified CSS and JS into `public/build/` |
| **Link storage folder** | `php artisan storage:link` | Recreates public storage shortcut |
| **Run automated tests** | `php artisan test` | Runs the PHPUnit test suite |

---

## 6. Troubleshooting & Common Errors

### 1. `No application encryption key has been specified`
- **Cause:** The `APP_KEY` variable in your `.env` is empty.
- **Fix:** Run:
  ```bash
  php artisan key:generate
  php artisan config:clear
  ```

---

### 2. `SQLSTATE[HY000] [2002] Connection refused` or `Access denied for user`
- **Cause:** MySQL is not running, or the database name, username, or password in `.env` is incorrect.
- **Fix:**
  1. Make sure your MySQL service is started (e.g., in XAMPP, click **Start** next to MySQL).
  2. Verify that the database named in `DB_DATABASE` actually exists in MySQL.
  3. Verify `DB_USERNAME` and `DB_PASSWORD` in `.env`.
  4. After editing `.env`, clear cache:
     ```bash
     php artisan config:clear
     ```

---

### 3. `The stream or file ".../storage/logs/laravel.log" could not be opened: failed to open stream: Permission denied`
- **Cause:** Your web server user does not have permission to write to the `storage` or `bootstrap/cache` folders.
- **Fix (Linux / macOS):**
  ```bash
  sudo chown -R $USER:www-data storage bootstrap/cache
  sudo chmod -R 775 storage bootstrap/cache
  ```

---

### 4. Profile photos or uploaded images return 404 (Not Found)
- **Cause:** The public storage symbolic link is missing.
- **Fix:** Run:
  ```bash
  php artisan storage:link
  ```
  If on Windows and the shortcut failed, open Command Prompt as Administrator and run the command again.

---

### 5. `Vite manifest not found at: .../public/build/manifest.json`
- **Cause:** Frontend assets have not been built yet.
- **Fix:** Run:
  ```bash
  npm install
  npm run build
  ```

---

### 6. Changes in `.env` are not taking effect
- **Cause:** Laravel has cached the previous configuration.
- **Fix:** Run:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  ```

---

### 7. PDF Invoice Download shows missing symbols or question marks
- **Cause:** Missing multibyte PHP extensions (`mbstring` or `gd`) or non-UTF8 currency symbols.
- **Fix:**
  - Ensure `extension=mbstring` and `extension=gd` are enabled in your `php.ini`.
  - The system includes built-in standard currency representations (e.g. `Rs.`, `$`, `€`, `£`, `¥`) to ensure cross-platform rendering in `dompdf`.

---

## 7. Production Deployment Tips (Web Server / VPS)

When moving the application to a production server (Apache / Nginx):

1. **Document Root:** Set your web server document root to the project's **`public/`** folder (never the project root).
   - *Example Nginx:* `root /var/www/invoice-system/public;`
   - *Example Apache:* `DocumentRoot /var/www/invoice-system/public`
2. **Environment Mode:** In `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   ```
3. **Speed & Cache Optimization:** Run these commands on the server after deployment:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```

