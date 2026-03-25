# PharmaCare — Complete System Documentation
> **Author:** Taguenang Leslie | **Version:** 3.1 | **Date:** March 2026  
> **Stack:** PHP 8+, MySQL, Bootstrap 5, Chart.js

---

## Table of Contents
1. [Project Overview](#1-project-overview)
2. [System Architecture](#2-system-architecture)
3. [Directory Structure](#3-directory-structure)
4. [Database Schema](#4-database-schema)
5. [User Roles & Permissions](#5-user-roles--permissions)
6. [Core Modules (Dynamic Features)](#6-core-modules-dynamic-features)
7. [Bilingual Support (EN/FR)](#7-bilingual-support-enfr)
8. [Platform Tax System](#8-platform-tax-system)
9. [Pharmacy CRUD Management](#9-pharmacy-crud-management)
10. [Authentication & Security](#10-authentication--security)
11. [Installation & Deployment](#11-installation--deployment)
12. [Test Accounts](#12-test-accounts)
13. [Troubleshooting](#13-troubleshooting)
14. [Changelog](#14-changelog)

---

## 1. Project Overview

**PharmaCare** is a fully dynamic, multi-tenant Pharmacy Management System. Every user action — sales, settings, language preferences, pharmacy approvals, expense records, and audit entries — is **persisted to the MySQL database in real time**. There are no static placeholder values in any user-facing flow.

### What "Fully Dynamic" Means in This System

| Feature | Database Table | Persisted |
|---------|---------------|-----------|
| User language preference | `users.language` | ✅ |
| Platform tax rate | `settings` (pharmacy_id IS NULL) | ✅ |
| Platform tax per sale | `sales.platform_tax` | ✅ |
| Pharmacy status (approve/suspend/delete) | `pharmacies.status` | ✅ |
| User role auto-upgrade on approval | `users.role`, `users.pharmacy_id` | ✅ |
| Inventory changes | `medicines` | ✅ |
| Every admin action | `audit_logs` | ✅ |
| System settings | `settings` | ✅ |
| Cart → Sale → Stock decrement | `sales`, `sale_items`, `medicines.quantity` | ✅ |

---

## 2. System Architecture

```
Browser (HTML5 / Bootstrap 5 / JS)
         │
         │  HTTP
         ▼
    Apache (XAMPP)
         │
         ▼
    PHP 8+ Runtime
    ┌──────────────────────────────────────────┐
    │  includes/config/database.php            │ ← PDO connection, BASE_URL, constants
    │  includes/functions/auth.php             │ ← RBAC, login, session guards
    │  includes/functions/helpers.php          │ ← format_currency, sanitize, log_activity
    │  includes/functions/lang.php             │ ← EN/FR translation dictionary + __()
    │  includes/templates/header.php           │ ← Sidebar nav, topbar, language switcher
    │  includes/templates/footer.php           │ ← Dark mode, Chart.js, JS includes
    └──────────────────────────────────────────┘
         │
         ▼
    MySQL Database (pharmacy_db)
    ├── pharmacies     ├── sales          ├── settings
    ├── users          ├── sale_items     ├── audit_logs
    ├── medicines      ├── prescriptions  ├── support_messages
    ├── suppliers      ├── expenses       └── cart_reservations
    └── customers
```

### Multi-Tenancy

Every data table carries a `pharmacy_id` foreign key. All queries in staff-facing pages are automatically scoped to `$_SESSION['pharmacy_id']`. The Global Admin (`pharmacy_id = NULL`) can view all branches without belonging to any.

---

## 3. Directory Structure

```
Pharmacy_Management_System/
│
├── install.php                  ⭐ ONE-TIME setup — the ONLY migration script
├── index.php                    # Public landing page (settings-driven content)
├── login.php / logout.php       # Auth entry/exit
├── register.php                 # Customer self-registration
├── register_pharmacy.php        # Multi-step pharmacy application form
│
├── dashboard.php                # Main hub (stats, charts, alerts)
├── pos.php                      # Point of Sale terminal
├── inventory.php                # Medicine CRUD & browsing
├── customers.php                # Customer directory
├── suppliers.php                # Supplier management
├── expenses.php                 # Expense tracking & CRUD
├── reports.php                  # Sales, stock, expiry reports
├── prescriptions.php            # Prescription workflow
├── pending_sales.php            # Pharmacist → Cashier queue
├── pharmacies.php               # Pharmacy CRUD (Global Admin only) ⭐
├── platform_revenue.php         # Tax earnings per pharmacy ⭐
├── users.php                    # User management
├── settings.php                 # System configuration
├── audit_logs.php               # Action audit trail
├── backup.php                   # DB export utility
├── support_messages.php         # Support inbox (Global Admin)
│
├── profile.php                  # User profile & password change
├── cart.php                     # Customer cart (session-based)
├── checkout.php                 # Customer checkout
├── orders.php                   # Customer order history
├── explore.php                  # Pharmacy browser (public)
│
├── process_sale.php             # POS sale finalization + platform tax calc
├── finalize_sale.php            # Customer order invoice generation
├── change_language.php          # Updates session + DB language preference
│
├── database/schema.sql          # Master schema (all tables)
├── includes/
│   ├── config/database.php      # DB connection, constants, session
│   ├── functions/auth.php       # Login, RBAC helpers
│   ├── functions/helpers.php    # Utilities
│   ├── functions/lang.php       # EN/FR dictionary
│   └── templates/header.php    # Nav sidebar + topbar
└── assets/ / uploads/
```

---

## 4. Database Schema

### Tables

| Table | Description |
|-------|-------------|
| `pharmacies` | All registered pharmacy branches |
| `users` | All users regardless of role |
| `medicines` | Drug inventory per pharmacy |
| `suppliers` | Supplier records per pharmacy |
| `customers` | Customer profiles per pharmacy |
| `sales` | Sales records (POS + customer orders) |
| `sale_items` | Line items for each sale |
| `prescriptions` | Uploaded prescriptions |
| `expenses` | Pharmacy operational costs |
| `settings` | Config key-values (global or per-pharmacy) |
| `audit_logs` | System-wide action audit trail |
| `support_messages` | Contact form submissions |
| `cart_reservations` | Live cart stock holds (TTL-expiring) |

### Key Columns

**`users`**
| Column | Type | Notes |
|--------|------|-------|
| `pharmacy_id` | INT NULL | NULL = Global Admin or independent customer |
| `role` | ENUM | admin, pharmacist, cashier, customer |
| `language` | VARCHAR(10) | 'en' or 'fr' |

**`sales`**
| Column | Type | Notes |
|--------|------|-------|
| `platform_tax` | DECIMAL(10,2) | Tax owed to Global Admin |
| `pharmacist_id` | INT NULL | Pharmacist who created pending sale |
| `processed_by` | INT NULL | Cashier who confirmed the sale |

**`settings`**
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT | Primary key |
| `setting_key` | VARCHAR(50) | e.g. `tax_rate`, `platform_tax_rate` |
| `pharmacy_id` | INT NULL | NULL = global platform setting |

> The `settings` table uses a UNIQUE constraint on `(setting_key, pharmacy_id)` — NOT as a composite primary key — to allow NULL `pharmacy_id` for global settings.

---

## 5. User Roles & Permissions

### Hierarchy
```
Global Platform Admin (pharmacy_id = NULL)
    └── Branch Admin (pharmacy_id = X)
            ├── Pharmacist
            ├── Cashier
            └── Customer (registered to branch)
```

### Auto Role Upgrade
When a **customer** submits a pharmacy registration via `register_pharmacy.php` and the **Global Admin** approves it on `pharmacies.php`:
1. `pharmacies.status` → `active`
2. `users.role` → `admin`
3. `users.pharmacy_id` → new pharmacy's ID

All three changes happen **atomically in a database transaction**.

### Permission Matrix

| Feature | Global Admin | Branch Admin | Pharmacist | Cashier | Customer |
|---------|:-----------:|:------------:|:----------:|:-------:|:-------:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ❌ |
| Inventory (view) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Inventory (edit) | ✅ | ✅ | ✅ | ❌ | ❌ |
| POS | ❌ | ✅ | ✅* | ✅ | ❌ |
| Pending Sales | ❌ | ✅ | ✅ | ✅ | ❌ |
| Prescriptions | ❌ | ✅ | ✅ | ❌ | ✅ |
| Customers | ❌ | ✅ | ✅ | ✅ | ❌ |
| Expenses | ❌ | ✅ | ❌ | ❌ | ❌ |
| Reports | ✅ | ✅ | ❌ | ❌ | ❌ |
| Users | ✅ | ✅ | ❌ | ❌ | ❌ |
| Add Pharmacy | ✅ | ❌ | ❌ | ❌ | ❌ |
| Approve/Suspend Pharmacy | ✅ | ❌ | ❌ | ❌ | ❌ |
| Delete Pharmacy | ✅ | ❌ | ❌ | ❌ | ❌ |
| Platform Tax Config | ✅ | ❌ | ❌ | ❌ | ❌ |
| Platform Revenue | ✅ | ❌ | ❌ | ❌ | ❌ |
| Settings | ✅ | ✅ | ❌ | ❌ | ❌ |
| Audit Logs | ✅ | ✅ | ❌ | ❌ | ❌ |
| Browse & Order | ❌ | ❌ | ❌ | ❌ | ✅ |

---

## 6. Core Modules (Dynamic Features)

### 6.1 Pharmacy CRUD — `pharmacies.php` *(Global Admin Only)*
- **Add**: Modal form to directly create a pharmacy (immediately active) — saved to `pharmacies`
- **Approve**: Sets `status = 'active'` and upgrades applicant's `users.role` to `admin`
- **Suspend**: Sets `status = 'suspended'` — branch staff can no longer log in
- **Delete**: Removes pharmacy row AND sets all associated staff `pharmacy_id = NULL`, `role = 'customer'`
- **View Documents**: Renders uploaded legal documents in a modal

### 6.2 Dashboard — `dashboard.php`
- Stats fetched live from DB on every page load
- Global Admin sees cross-pharmacy totals; branch users see their branch only
- Expiring medicines are **clickable links** → `inventory.php?search={name}`
- Sales chart built from last 7 days of `sales` records

### 6.3 Point of Sale — `pos.php` / `process_sale.php`
- Cart is JavaScript-managed client-side; saved to DB via form POST
- On submit: `process_sale.php` inserts into `sales` + `sale_items`, decrements `medicines.quantity`, computes `platform_tax`
- Pharmacist path: creates `payment_status = pending` entry for cashier queue

### 6.4 Platform Revenue — `platform_revenue.php`
- Aggregates `SUM(platform_tax)` from `sales` grouped by `pharmacy_id`
- Lifetime, monthly, and today stats all from live DB queries

### 6.5 Settings — `settings.php`
- Each key saved with a SELECT → UPDATE/INSERT pattern to handle `pharmacy_id = NULL` correctly
- Global Admin settings (`pharmacy_id IS NULL`) co-exist with branch settings

### 6.6 Language Switcher — `change_language.php`
- Updates `$_SESSION['language']`
- Writes new language to `users.language` in the DB
- Loaded back from DB on next login via `auth.php`

---

## 7. Bilingual Support (EN/FR)

```php
// Translation lookup — includes/functions/lang.php
function __($key) {
    global $translations;
    $lang = $_SESSION['language'] ?? 'en';
    return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
}
```

Covered areas: Navigation, Dashboard, POS labels, Inventory tables, action buttons, empty states, date labels.

---

## 8. Platform Tax System

```
Global Admin sets platform_tax_rate = 2%
        ↓
Cashier makes a sale (grand_total = 10,000 FCFA)
        ↓
process_sale.php: platform_tax = 10000 × 2/100 = 200 FCFA
        ↓
Saved → sales.platform_tax = 200
        ↓
Global Admin views Platform Revenue → sees 200 FCFA from that pharmacy
```

---

## 9. Pharmacy CRUD Management

### Add (Admin-created)
```
POST pharmacies.php → action=add_pharmacy
→ INSERT INTO pharmacies (name, address, ..., status='active')
```

### Approve (Customer application)
```
GET pharmacies.php?action=approve&id=X
→ UPDATE pharmacies SET status='active'
→ UPDATE users SET role='admin', pharmacy_id=X WHERE id=owner_id
```

### Suspend
```
GET pharmacies.php?action=suspend&id=X
→ UPDATE pharmacies SET status='suspended'
```

### Delete (Permanent)
```
GET pharmacies.php?action=delete&id=X
→ UPDATE users SET pharmacy_id=NULL, role='customer' WHERE pharmacy_id=X
→ DELETE FROM pharmacies WHERE id=X  (cascade: medicines, sales, etc.)
```

---

## 10. Authentication & Security

| Mechanism | Implementation |
|-----------|---------------|
| Passwords | `password_hash()` / `password_verify()` BCRYPT |
| SQL Injection | PDO Prepared Statements on all queries |
| XSS | `htmlspecialchars()` on all dynamic output |
| CSRF | Session-bound token |
| Role Guards | `require_role()` / `has_role()` on every page |
| Tenant Isolation | All queries scoped to `$_SESSION['pharmacy_id']` |

---

## 11. Installation & Deployment

1. Copy project to `C:/xampp/htdocs/Pharmacy_Management_System/`
2. Set DB credentials in `includes/config/database.php`
3. Start Apache + MySQL in XAMPP
4. Visit `http://localhost/Pharmacy_Management_System/install.php`
5. Login at `http://localhost/Pharmacy_Management_System/`

> `install.php` is the **only** script responsible for DB creation, schema, upgrades, and seeding.

---

## 12. Test Accounts

| Username | Password | Role | Branch |
|----------|----------|------|--------|
| `admin` | `Admin@123` | Global Admin | Platform |
| `pharmacist` | `Pharma@123` | Pharmacist | Main PharmaCare |
| `cashier` | `Cashier@123` | Cashier | Main PharmaCare |
| `elite_pharma` | `Elite@123` | Pharmacist | Elite Wellness |
| `clinic_cash` | `Clinic@123` | Cashier | Community Clinic |
| `test_customer` | `Customer@123` | Customer | Public |

See **[TEST_ACCOUNTS.md](TEST_ACCOUNTS.md)** for full test scenario walkthroughs.

---

## 13. Troubleshooting

| Error | Fix |
|-------|-----|
| `Database Connection Error` | Start MySQL in XAMPP; verify credentials in `database.php` |
| `settings: Column 'pharmacy_id' cannot be null` | Visit `fix_settings.php` to rebuild the settings table |
| Language not switching | Run `install.php` to ensure `users.language` column exists |
| Blank pages | Enable `display_errors = On` in `php.ini` |
| POS cart not updating | Check browser console for jQuery/Bootstrap load errors |

---

## 14. Changelog

| Version | Phase | Key Changes |
|---------|-------|-------------|
| 1.0 | 1–10 | Core schema, auth, inventory, POS, basic dashboard |
| 1.5 | 11–20 | Multi-tenancy, RBAC, customer portal, suppliers, expenses |
| 2.0 | 21–23 | Cart/checkout, prescriptions, pending sales workflow |
| 2.2 | 24–25 | Dashboard redesign, notification bell, dark mode |
| 2.5 | 26 | Pharmacist→Cashier POS workflow, audit logging |
| 2.7 | 27 | Platform Tax Engine, EN/FR language switcher |
| 2.8 | 28 | Full UI localization (dashboard, POS, inventory, nav) |
| 2.9 | 29 | Architecture: all migrations centralized in `install.php` |
| 3.0 | 30 | Platform Revenue dashboard, expiring-soon clickable links |
| **3.1** | **31–32** | **Pharmacy CRUD (Add/Delete), auto role upgrade, full documentation** |

---
*© 2026 Taguenang Leslie. All Rights Reserved.*
