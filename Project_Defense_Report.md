# 📄 SYSTEM DEFENSE REPORT: PHARMACARE

**Topic**: Design and Implementation of a Fully Dynamic, Multi-Tenant Pharmacy Management System with Bilingual Support, E-Commerce Capabilities, and a Platform Revenue Engine.

**Presented By**: Taguenang Leslie  
**Date**: March 2026  
**Version**: PharmaCare v3.2

---

## 1. ABSTRACT

**PharmaCare** is a fully dynamic, database-driven, multi-tenant web application that manages pharmacy operations at both the branch and network level. Every user action — from inventory updates, sales, and expense entries, to language preferences, system settings, and pharmacy approvals — is immediately persisted to a relational MySQL database.

The platform introduces four distinctive engineering contributions:

1. A **Platform Revenue Engine** enabling the system owner to collect a configurable tax margin from all sales across all branches, tracked in real time.
2. **Full Bilingual Support (English/French)** with per-user preferences stored in the database and resolved dynamically using a custom translation function.
3. A **Pharmacy Lifecycle Management** system allowing the Global Administrator to add, approve, suspend, or permanently delete pharmacies, with automatic staff role reassignment on approval or deletion.
4. A **Unified Cross-Pharmacy Search Engine** (v3.2) providing customers with a network-wide view of drug availability and pricing, integrated with an atomic stock reservation system to prevent race conditions during checkout.

---

## 2. INTRODUCTION

### 2.1 Problem Statement

Small and medium-sized pharmacies in Cameroon and similar markets frequently face:

- Manual inventory tracking leading to expired or out-of-stock drugs
- No digital presence limiting customer reach to physical walk-ins only
- Fragmented systems making cross-pharmacy drug availability impossible to check
- No mechanism for a SaaS operator to passively earn from providing the platform
- Language barriers in enterprise software that only supports a single language

### 2.2 Objectives

1. Automate inventory management, stock alerts, and expiry monitoring
2. Provide a secure, multi-role Point of Sale terminal
3. Implement full multi-tenancy with strict data isolation between branches
4. Offer a customer-facing e-commerce experience for cross-pharmacy ordering
5. Allow the platform owner to monetize via built-in tax collection
6. Support English and French UI languages switchable per user
7. Enable the Global Admin to manage the full pharmacy lifecycle (add, approve, suspend, delete)
8. Provide a unified, real-time search interface for customers to discover medicines across the entire network.

---

## 3. SYSTEM ARCHITECTURE

### 3.1 Layers

| Layer        | Technology                                         |
| ------------ | -------------------------------------------------- |
| Presentation | HTML5, Bootstrap 5, Vanilla CSS/JS, Font Awesome 6 |
| Logic        | PHP 8+, RBAC, PDO                                  |
| Data         | MySQL via PDO Prepared Statements                  |
| Charting     | Chart.js (CDN)                                     |

### 3.2 Multi-Tenancy Model

Every core table (`medicines`, `sales`, `customers`, `suppliers`, `expenses`, `users`, `settings`, `audit_logs`) references `pharmacy_id`. All staff-facing queries are automatically scoped to the session's `pharmacy_id`. The Global Administrator (`pharmacy_id = NULL`) has read access across all branches.

### 3.3 Key Architectural Decisions

| Decision                                                                            | Rationale                                                                                  |
| ----------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `settings` table uses UNIQUE key (not composite PK) on `(setting_key, pharmacy_id)` | Allows `pharmacy_id = NULL` for global settings — composite PKs strictly forbid NULL       |
| `sales.platform_tax` column                                                         | Immutably records the exact fee per transaction for audit purposes                         |
| `users.language` column                                                             | Persists per-user UI language preference across sessions/devices                           |
| `install.php` as sole migration script                                              | Centralizes all DB creation, schema upgrades, and seeding — no auto-migration on page load |
| `process_sale.php` handles platform tax at commit time                              | Guarantees the global rate at the moment of sale is the one charged, not a cached value    |
| `cart_reservations` live stock holds (v3.2)                                         | Prevents "phantom stock" by holding items in the DB for 30 mins before checkout            |

---

## 4. FUNCTIONAL MODULES

### 4.1 Global Administration

The **Global System Administrator** (`pharmacy_id = NULL`) has exclusive access to:

**Pharmacy Lifecycle Management** (`pharmacies.php`):

- **Add Pharmacy**: Modal form lets the admin directly create a pharmacy (immediately active). Saved to `pharmacies` table with `status = 'active'`.
- **Approve Application**: Pending pharmacy applications submitted via `register_pharmacy.php` are reviewed here. On approval:
  1. `pharmacies.status → 'active'`
  2. Applicant's `users.role → 'admin'`
  3. Applicant's `users.pharmacy_id → new pharmacy ID`
     — All three changes occur in a single **database transaction**.
- **Suspend**: Sets `status = 'suspended'`. Branch staff lose system access.
- **Delete Permanently**: Removes the pharmacy. All associated staff are automatically set to `role = 'customer'` and `pharmacy_id = NULL` before the row is deleted, preventing orphaned accounts.

**Platform Revenue** (`platform_revenue.php`):

- Aggregates `SUM(platform_tax)` from the `sales` table grouped per `pharmacy_id`
- Displays today's, monthly, and lifetime earnings with a per-pharmacy breakdown table

**Platform Tax Configuration** (`settings.php`):

- Only visible to the Global Admin
- Saved to `settings` with `pharmacy_id = NULL`
- Referenced in `process_sale.php` to calculate `platform_tax` on every sale

### 4.2 Branch Administration

Branch Admins can manage their own pharmacy only:

- Staff creation and role assignment within their branch
- Branch-specific settings (currency, tax rate, system name)
- Financial reports, expense tracking, audit logs

### 4.3 Inventory Management

- Full CRUD (Add/Edit/Delete) for medicines saved to `medicines` table
- Low Stock Alerts: widget + notification bell when `quantity ≤ reorder_level`
- Expiry Alerts: colour-coded table on dashboard. Medicine names are **hyperlinks** to `inventory.php?search={name}`
- Global Admin sees all pharmacies grouped; branch staff see their branch only

### 4.4 Point of Sale (POS)

- JavaScript cart (no page reload); submitted via form to `process_sale.php`
- `process_sale.php`: inserts `sales` + `sale_items`, decrements `medicines.quantity`, computes `platform_tax`
- **Pharmacist flow**: creates `payment_status = pending` entry
- **Cashier flow**: confirms from Pending Sales queue (`pending_sales.php`)
- Printable receipt via `receipt.php`

### 4.5 Customer E-Commerce Portal (v3.2 Upgrade)

- **Unified Search Hub**: Discover drugs across the entire pharmacy network from one interface.
- **Real-time Stock Hold**: When adding an item to the cart, the system creates a `cart_reservations` entry and **atomically deducts** stock from the database for 30 minutes.
- **Smart Checkout**: Automatically handles multi-branch orders, generating separate invoices per pharmacy involved.
- **Prescription Portal**: Upload digital prescriptions with pharmacist review tracking.

### 4.6 Bilingual Support (EN/FR)

```php
// includes/functions/lang.php
function __($key) {
    global $translations;
    $lang = $_SESSION['language'] ?? 'en';
    return $translations[$lang][$key]
        ?? $translations['en'][$key]
        ?? $key;
}
```

- Language stored in `users.language` column → loaded into session on login
- Changed via globe icon in header → `change_language.php` updates both session AND database
- Covers: Navigation, Dashboard, POS, Inventory tables, all action buttons

---

## 5. TECHNICAL IMPLEMENTATION & SECURITY

### 5.1 Security Architecture

| Protection       | Method                                                        |
| ---------------- | ------------------------------------------------------------- |
| Passwords        | `PASSWORD_BCRYPT` via `password_hash()` / `password_verify()` |
| SQL Injection    | PDO Prepared Statements — no string concatenation in queries  |
| XSS              | `htmlspecialchars()` on all dynamic output                    |
| CSRF             | Session-bound token checked on sensitive POSTs                |
| Role Guards      | `require_role()` / `has_role()` enforced on every page load   |
| Tenant Isolation | All queries scoped to `$_SESSION['pharmacy_id']`              |
| Concurrency      | Database transactions (`FOR UPDATE`) for stock reservations  |
| Session Security | Destroyed completely on logout; no persistent cookies         |

### 5.2 Data Persistence Architecture

All data flows through the MySQL database via PDO:

```
User Action → PHP Handler → PDO Prepared Statement → MySQL → Persisted
```

No feature relies on static files, in-memory state (beyond the session), or hardcoded values for runtime data.

### 5.3 Installation Architecture

`install.php` is the **only** script that modifies the database schema:

- Creates database if missing
- Executes `database/schema.sql`
- Runs `$columns_to_check` array to safely apply all schema upgrades (Phase 26-33) without breaking existing data
- Seeds pharmacies, users, suppliers, medicines, customers
- No auto-migration on page load; `database.php` is purely a connection file

---

## 6. USER INTERFACE & DESIGN

- **Theme**: Deep Pink (`#FF1493`) primary, with glassmorphism card effects
- **Unified Search**: A card-based results grid with pharmacy source badges and price labels.
- **Layout**: Fixed left sidebar + fluid main content (Bootstrap 5 grid)
- **Dark Mode**: Toggled and persisted via `localStorage`
- **Responsive**: Mobile-first Bootstrap grid
- **Charts**: Chart.js line graph with real DB data (last 7 days of sales)
- **Notifications**: Bell icon with live counts for: Low Stock, Near Expiry, Pending Approvals, Support Messages

---

## 7. TESTING

### Test Accounts (from `install.php` seed)

| Username        | Password       | Role         | Branch           |
| --------------- | -------------- | ------------ | ---------------- |
| `admin`         | `Admin@123`    | Global Admin | Platform         |
| `pharmacist`    | `Pharma@123`   | Pharmacist   | Main PharmaCare  |
| `cashier`       | `Cashier@123`  | Cashier      | Main PharmaCare  |
| `elite_pharma`  | `Elite@123`    | Pharmacist   | Elite Wellness   |
| `clinic_cash`   | `Clinic@123`   | Cashier      | Community Clinic |
| `test_customer` | `Customer@123` | Customer     | Public           |

### Verified Test Scenarios

- ✅ **Unified Cross-Pharmacy Search**: Discover drugs from multiple branches on one page.
- ✅ **Synchronized Cart Stock**: Stock is deducted and held instantly upon "Add to Cart".
- ✅ Pharmacy application → approval → customer auto-upgraded to admin
- ✅ Admin creates pharmacy directly — immediately active
- ✅ Delete pharmacy → all branch staff demoted to customer
- ✅ Platform tax calculated and stored per sale
- ✅ Platform Revenue shows correct per-pharmacy breakdown
- ✅ Language toggle updates DB and persists after logout/login
- ✅ Global Admin can save settings without `pharmacy_id` constraint error
- ✅ Expiring medicine links route correctly to inventory search

---

## 8. CHALLENGES & SOLUTIONS

| Challenge                                                | Solution                                                                                     |
| -------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| MySQL composite PK blocks NULL `pharmacy_id` in settings | Rebuilt `settings` table with auto-increment PK + UNIQUE key on `(setting_key, pharmacy_id)` |
| Auto-migration queries running on every page load        | Removed from `database.php`, centralized all in `install.php`                                |
| Language preference lost on session expiry               | Stored in `users.language` column, reloaded from DB on login                                 |
| Pharmacy deletion leaving orphaned staff accounts        | DELETE handler first sets all staff to `role='customer'`, `pharmacy_id=NULL` before deleting |
| Pharmacist and Cashier POS role divergence               | `has_role('pharmacist')` check routes to pending vs confirmed sale path                      |
| Concurrency in cross-pharmacy checkout                   | Implemented real-time reservations in `cart_reservations` integrated with `cart.php`.        |

---

## 9. CONCLUSION & FUTURE WORK

**PharmaCare v3.2** delivers a production-grade, fully dynamic multi-tenant SaaS platform for pharmacy management. Every feature persists state to the database — no static data, no hardcoded runtime values. The introduction of network-wide drug discovery (Unified Search) and automated stock holds marks a significant leap in system robustness.

### Planned Enhancements

| Feature                                  | Priority |
| ---------------------------------------- | -------- |
| MTN MoMo / Orange Money API integration  | High     |
| SMS/Email order notifications            | High     |
| AI demand forecasting from sales history | Medium   |
| Real-time pharmacist–customer messaging  | Medium   |
| PWA offline support for POS              | Low      |

---

## 10. REFERENCES

- PHP Documentation: https://www.php.net/docs.php
- Bootstrap 5: https://getbootstrap.com/docs/5.3/
- Chart.js: https://www.chartjs.org/docs/
- MySQL Reference: https://dev.mysql.com/doc/
- OWASP Security Cheatsheet: https://cheatsheetseries.owasp.org/

---

_© 2026 Taguenang Leslie. All Rights Reserved._
