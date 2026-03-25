# PharmaCare — Complete System Documentation
> **Author:** Taguenang Leslie  
> **Version:** 3.0  
> **Date:** March 2026  
> **Stack:** PHP 8+, MySQL, Bootstrap 5, Chart.js

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [System Architecture](#2-system-architecture)
3. [Directory Structure](#3-directory-structure)
4. [Database Schema](#4-database-schema)
5. [User Roles & Access Control](#5-user-roles--access-control)
6. [Core Modules](#6-core-modules)
7. [Bilingual Support (EN/FR)](#7-bilingual-support-enfr)
8. [Platform Tax System](#8-platform-tax-system)
9. [Authentication & Security](#9-authentication--security)
10. [Installation & Deployment](#10-installation--deployment)
11. [Test Accounts](#11-test-accounts)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. Project Overview

**PharmaCare** is a multi-tenant Pharmacy Management System designed to serve a network of pharmacies under one centralized platform. It enables a **Global Platform Administrator** to onboard, monitor, and tax participating pharmacies, while each pharmacy manages its own staff, inventory, sales, and customers independently.

### Key Features

| Feature | Description |
|---------|-------------|
| Multi-Tenancy | Complete data isolation per pharmacy branch |
| Role-Based Access | 5 distinct roles with granular permission scoping |
| Point of Sale (POS) | Fast, searchable cart-based sales terminal |
| Inventory Management | Stock tracking, reorder alerts, expiry monitoring |
| Platform Tax Engine | Passive revenue collection on all sales for the system owner |
| Bilingual UI | Full English / French language support per user |
| Reporting & Analytics | Sales charts, expense tracking, revenue breakdowns |
| Audit Logging | Every admin action is tracked with timestamps |
| Prescription Management | Workflow for digital prescription handling |
| Customer Portal | Public-facing drug browsing and order placement |

---

## 2. System Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                    PharmaCare Platform                       │
│                                                              │
│  ┌────────────────┐    ┌─────────────────┐                  │
│  │  Global Admin  │    │  Landing / Auth  │                  │
│  │  (No Branch)   │    │  index.php       │                  │
│  └────────┬───────┘    │  login.php       │                  │
│           │            │  register.php    │                  │
│           │            └─────────────────┘                  │
│           ▼                                                  │
│  ┌────────────────────────────────────────────────────┐      │
│  │           Multi-Tenant Pharmacy Instance           │      │
│  │                                                    │      │
│  │   Branch Admin  →  Admin Panel, Users, Settings   │      │
│  │   Pharmacist    →  Inventory, Prescriptions, POS  │      │
│  │   Cashier       →  POS, Pending Sales, Customers  │      │
│  │   Customer      →  Browse Inventory, Place Orders │      │
│  └────────────────────────────────────────────────────┘      │
│                                                              │
│  ┌──────────────────────────────────┐                        │
│  │        MySQL Database            │                        │
│  │  pharmacies / users / medicines  │                        │
│  │  sales / settings / audit_logs   │                        │
│  └──────────────────────────────────┘                        │
└──────────────────────────────────────────────────────────────┘
```

### Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8+, PDO (MySQL) |
| Frontend | HTML5, Bootstrap 5, Vanilla CSS/JS |
| Database | MySQL via XAMPP |
| Charts | Chart.js CDN |
| Icons | Font Awesome 6 |
| Session | PHP Native Sessions |

---

## 3. Directory Structure

```
Pharmacy_Management_System/
│
├── index.php                  # Public landing page
├── login.php                  # Authentication entry point
├── register.php               # Customer self-registration
├── register_pharmacy.php      # Pharmacy onboarding form (multi-step)
├── install.php                # ONE-TIME installation & seeding script ⭐
│
├── dashboard.php              # Core admin/staff dashboard
├── pos.php                    # Point of Sale terminal
├── inventory.php              # Medicine stock management
├── customers.php              # Customer directory & management
├── suppliers.php              # Supplier management
├── expenses.php               # Expense tracking
├── reports.php                # Sales & financial reports
├── prescriptions.php          # Prescription workflow
├── pending_sales.php          # Pharmacist-to-cashier pending orders
├── platform_revenue.php       # Global Admin revenue tracking ⭐
├── pharmacies.php             # Platform Admin: pharmacy list & approval
├── users.php                  # Admin: user management
├── settings.php               # System configuration
├── audit_logs.php             # Action audit trail
├── backup.php                 # Database backup utility
├── support_messages.php       # Admin: support inbox
│
├── profile.php                # User profile & password change
├── cart.php                   # Customer shopping cart
├── checkout.php               # Customer checkout flow
├── orders.php                 # Customer order history
├── explore.php                # Public pharmacy browser
│
├── process_sale.php           # Sale finalization handler
├── finalize_sale.php          # Checkout finalization for customer orders
├── place_order.php            # Customer order submission
├── change_language.php        # Language preference switcher
├── mark_notifs_read.php       # Notification dismissal endpoint
├── receipt.php                # Post-sale receipt view
│
├── database/
│   └── schema.sql             # Master database schema
│
├── includes/
│   ├── config/
│   │   └── database.php       # DB connection + global constants
│   ├── functions/
│   │   ├── auth.php           # Login, session, role helpers
│   │   ├── helpers.php        # format_currency(), sanitize, log_activity()
│   │   └── lang.php           # EN/FR translation dictionary + __() function
│   └── templates/
│       ├── header.php         # Global sidebar nav + top bar
│       └── footer.php         # JS includes, dark mode, footer
│
├── assets/
│   ├── css/style.css          # Global theme & custom styles
│   ├── js/                    # Client-side scripts
│   └── img/                   # Static images
│
└── uploads/                   # User-uploaded files (IDs, licenses, avatars)
```

---

## 4. Database Schema

### Tables Overview

| Table | Purpose |
|-------|---------|
| `pharmacies` | Each registered pharmacy branch |
| `users` | All platform users (any role) |
| `medicines` | Medication inventory per pharmacy |
| `suppliers` | Supplier directory per pharmacy |
| `customers` | Customer directory per pharmacy |
| `sales` | Completed and pending sale records |
| `sale_items` | Line items for each sale |
| `prescriptions` | Uploaded/managed prescription records |
| `expenses` | Pharmacy operational expenses |
| `settings` | Configurable key-value settings (global or per-pharmacy) |
| `audit_logs` | System-wide action audit trail |
| `support_messages` | Contact form messages |
| `cart_reservations` | Real-time cart stock hold records |

### Key Relationships

```
pharmacies (1) ──< users (many)
pharmacies (1) ──< medicines (many)
pharmacies (1) ──< suppliers (many)
pharmacies (1) ──< customers (many)
pharmacies (1) ──< sales (many)
pharmacies (1) ──< expenses (many)
pharmacies (1) ──< settings (many)
sales (1) ──< sale_items (many)
medicines (many) >── sale_items (many)
users (1) ──< sales (many)
```

### Notable Columns

#### `users`
| Column | Type | Notes |
|--------|------|-------|
| `pharmacy_id` | INT NULL | NULL = Global Admin or customer |
| `role` | ENUM | admin, pharmacist, cashier, customer |
| `language` | VARCHAR(10) | 'en' or 'fr' — user language preference |

#### `sales`
| Column | Type | Notes |
|--------|------|-------|
| `platform_tax` | DECIMAL(10,2) | Tax taken by Global Admin from this sale |
| `payment_status` | ENUM | paid, pending, cancelled |
| `order_status` | ENUM | pending, processing, completed, cancelled |

#### `settings`
| Column | Type | Notes |
|--------|------|-------|
| `pharmacy_id` | INT NULL | NULL = global platform setting |
| `setting_key` | VARCHAR(50) | e.g. `tax_rate`, `system_name` |
| `setting_value` | TEXT | The configured value |

---

## 5. User Roles & Access Control

### Role Hierarchy

```
Global Admin (pharmacy_id = NULL)
    └── Branch Admin (pharmacy_id = X)
            ├── Pharmacist
            ├── Cashier
            └── Customer
```

### Permission Matrix

| Feature | Global Admin | Branch Admin | Pharmacist | Cashier | Customer |
|---------|:---:|:---:|:---:|:---:|:---:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ❌ |
| Inventory (view) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Inventory (edit) | ✅ | ✅ | ✅ | ❌ | ❌ |
| POS / Make Sale | ❌ | ✅ | ✅* | ✅ | ❌ |
| Pending Sales | ❌ | ✅ | ✅ | ✅ | ❌ |
| Prescriptions | ❌ | ✅ | ✅ | ❌ | ✅ |
| Customers | ❌ | ✅ | ✅ | ✅ | ❌ |
| Expenses | ❌ | ✅ | ❌ | ❌ | ❌ |
| Reports | ✅ | ✅ | ❌ | ❌ | ❌ |
| Users Management | ✅ | ✅ | ❌ | ❌ | ❌ |
| Settings | ✅ | ✅ | ❌ | ❌ | ❌ |
| Platform Tax Config | ✅ | ❌ | ❌ | ❌ | ❌ |
| Platform Revenue | ✅ | ❌ | ❌ | ❌ | ❌ |
| Pharmacies | ✅ | ❌ | ❌ | ❌ | ❌ |
| Support Inbox | ✅ | ❌ | ❌ | ❌ | ❌ |
| Browse & Order | ❌ | ❌ | ❌ | ❌ | ✅ |

> *Pharmacist uses POS to create **Pending Sales** routed to cashier confirmation

---

## 6. Core Modules

### 6.1 Point of Sale (POS) — `pos.php`
- Real-time searchable product list
- Cart managed client-side in JavaScript
- Supports tax calculation display
- **Pharmacists** create Pending Sales forwarded to Cashier
- **Cashiers** directly complete and receipt sales
- Payment methods: Cash, Card, Mobile Money

### 6.2 Inventory — `inventory.php`
- Full CRUD: Add/Edit/Delete medicines
- Low Stock indicator with reorder level alert
- Expiry Date tracking (colour-coded urgency)
- Global Admin sees all pharmacy inventory grouped
- Customers browse by selecting a pharmacy branch
- Expiring medicines on the dashboard are **clickable links** to the direct inventory entry

### 6.3 Dashboard — `dashboard.php`
- **Stats Widgets**: Today's Revenue, Total Sales, Low Stock Count, Total Medicines, Tax Profit (Global Admin)
- **Sales Analytics Chart**: 7-day line graph
- **Recent Sales** sidebar with customer names
- **Low Stock Alerts** table with Restock shortcut
- **Expiring Soon** table — click any medicine name to navigate directly to its inventory record

### 6.4 Platform Revenue — `platform_revenue.php` *(Global Admin Only)*
- **Today's Revenue**, **This Month's Revenue**, **Lifetime Revenue** from platform tax
- Per-pharmacy breakdown table: Sales count, Gross Volume, Tax Collected
- Grand Total footer row across all pharmacies

### 6.5 Reports — `reports.php`
- Sales Report: date-filtered, exportable
- Stock / Expiry Reports
- Financial summary graphs

### 6.6 Prescriptions — `prescriptions.php`
- Pharmacists accept uploaded prescriptions from customers
- Status workflow: Pending → Filled → Cancelled
- Customers can upload prescriptions via their portal

### 6.7 Pharmacy Onboarding — `register_pharmacy.php`
- Multi-step form: Business details, legal documents, staff info
- Uploads: Owner ID, Pharmacist License, Business Registration
- Status starts as `pending` — requires Global Admin approval

### 6.8 Audit Logs — `audit_logs.php`
- Every create/update/delete action is logged with user ID, IP, timestamp
- Filterable by action type and user

---

## 7. Bilingual Support (EN/FR)

### How It Works

1. **User Preference**: Each user has a `language` column in the `users` table (`en` or `fr`).
2. **Session**: On login, the user's language is loaded into `$_SESSION['language']`.
3. **Switching**: A globe 🌐 icon in the header triggers `change_language.php`, which updates both the session and the database.
4. **Translation Function**: All UI text is rendered via the `__()` function:

```php
// includes/functions/lang.php
function __($key) {
    global $translations;
    $lang = $_SESSION['language'] ?? 'en';
    return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
}
```

5. **Dictionary**: All translations live in `includes/functions/lang.php` as a nested array with `en` and `fr` keys.

### Covered UI Areas
- Navigation sidebar (all links)
- Dashboard headings, stat widgets, table headers
- Point of Sale: cart labels, payment options, buttons
- Inventory: table columns, add/restock actions
- Common: buttons, empty-state messages, date labels

---

## 8. Platform Tax System

### How It Works

1. **Configuration**: Only the **Global System Administrator** can set the `platform_tax_rate` in `settings.php`. It is stored with `pharmacy_id = NULL`.

2. **Automatic Calculation**: When any sale is processed via `process_sale.php`, the global tax rate is fetched and the platform tax is calculated:

```php
// process_sale.php
$tax_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='platform_tax_rate' AND pharmacy_id IS NULL");
$platform_tax_rate = (float)($tax_stmt->fetchColumn() ?? 0);
$platform_tax = round($grand_total * ($platform_tax_rate / 100), 2);
```

3. **Storage**: Each `sales` row has a `platform_tax` column storing the exact amount collected.

4. **Visibility**: The Global Admin's Dashboard shows today's "Tax Profit" widget. The **Platform Revenue** page (`platform_revenue.php`) provides a full breakdown per pharmacy.

---

## 9. Authentication & Security

### Login Flow (`auth.php`)
1. User submits credentials on `login.php`
2. `password_verify()` checks against bcrypt hash
3. On success: `pharmacy_id`, `role`, `language`, `avatar`, `full_name` loaded into session
4. Redirected to `dashboard.php`

### Security Measures

| Mechanism | Implementation |
|-----------|---------------|
| Password Hashing | PHP `password_hash()` with `PASSWORD_BCRYPT` |
| SQL Injection | PDO Prepared Statements throughout |
| XSS Prevention | `htmlspecialchars()` on all output |
| CSRF Token | Auto-generated, stored in session |
| Role Guards | `require_role()` / `has_role()` on every page |
| Multi-Tenant Isolation | All queries scoped by `pharmacy_id` from session |
| Session Management | `session_start()` in `database.php`; `logout.php` destroys session |

### Key Auth Functions (`includes/functions/auth.php`)

```php
is_logged_in()       // Verifies active session
require_login()      // Redirects to login if not authenticated
require_role($role)  // Terminates with 403 if role not matched
has_role($role)      // Boolean check — can accept array of roles
```

---

## 10. Installation & Deployment

### Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.4+
- Apache / XAMPP

### Step 1: Copy Files
Place the project folder inside your XAMPP `htdocs` directory:
```
C:/xampp/htdocs/Pharmacy_Management_System/
```

### Step 2: Configure Database
Open `includes/config/database.php` and update credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmacy_db');     // Will be auto-created
define('DB_USER', 'root');
define('DB_PASS', '');                // XAMPP default
```

### Step 3: Run Installer
Start Apache and MySQL in XAMPP, then navigate to:
```
http://localhost/Pharmacy_Management_System/install.php
```

This single script will:
- Create the database if it doesn't exist
- Run the full schema (`database/schema.sql`)
- Apply all schema upgrades (missing columns, new tables)
- Seed 3 sample pharmacies
- Create all test accounts
- Seed sample inventory & customers

### Step 4: Delete Installer (Security)
After running, **delete or restrict access to** `fix_settings.php` and `tmp_migration.php`.

### Step 5: Access the System
```
http://localhost/Pharmacy_Management_System/
```
Log in with your desired test account (see Section 11).

---

## 11. Test Accounts

| Username | Password | Role | Pharmacy |
|----------|----------|------|----------|
| `admin` | `Admin@123` | Global Admin | Platform-Wide |
| `pharmacist` | `Pharma@123` | Pharmacist | Main PharmaCare |
| `cashier` | `Cashier@123` | Cashier | Main PharmaCare |
| `elite_pharma` | `Elite@123` | Pharmacist | Elite Wellness |
| `clinic_cash` | `Clinic@123` | Cashier | Community Clinic |
| `test_customer` | `Customer@123` | Customer | Public |

---

## 12. Troubleshooting

### "Database Connection Error"
- Ensure MySQL is running in XAMPP
- Check credentials in `includes/config/database.php`

### "settings: Column 'pharmacy_id' cannot be null"
- Visit `http://localhost/Pharmacy_Management_System/fix_settings.php`
- This rebuilds the settings table with correct NULL handling

### Language not switching
- Ensure `change_language.php` is accessible
- Check that the `users.language` column exists (run `install.php` if not)
- Clear browser cache

### Blank pages / PHP Errors
- Enable PHP error reporting in `php.ini`: `display_errors = On`
- Check `logs/` directory for error files

### POS cart not updating
- Ensure jQuery and Bootstrap JS are loading (check browser console)
- Verify `pos.php` JavaScript is not throwing fetch errors

---

## Changelog Summary

| Phase | Version | Key Changes |
|-------|---------|------------|
| 1–10 | 1.0 | Core schema, auth, inventory, POS, basic dashboard |
| 11–20 | 1.5 | Multi-tenancy, RBAC isolation, customer portal, supplier & expense modules |
| 21–23 | 2.0 | Cart/checkout system, prescription uploads, pending sales workflow |
| 24–25 | 2.2 | Dashboard redesign, notification bell, dark mode, UI overhaul |
| 26 | 2.5 | Pharmacist-to-cashier POS workflow, audit logging improvements |
| 27 | 2.7 | Platform Tax Engine, language switcher, EN/FR bilingual support |
| 28 | 2.8 | Comprehensive UI localization (dashboard, POS, inventory, navigation) |
| 29 | 2.9 | Architecture cleanup — centralized all migrations to `install.php` |
| 30 | 3.0 | Platform Revenue dashboard, expiring-soon clickable links |

---

*© 2026 Taguenang Leslie. All Rights Reserved.*
