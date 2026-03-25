# 📄 SYSTEM DEFENSE REPORT: PHARMACARE
**Topic**: Design and Implementation of a Multi-Tenant Pharmacy Management System with Bilingual Support, E-Commerce Capabilities, and a Platform Revenue Engine.

**Presented By**: Taguenang Leslie  
**Date**: March 2026  
**Version**: PharmaCare v3.0

---

## 1. ABSTRACT

In the contemporary healthcare landscape, efficient drug distribution and management are of critical importance. Most existing pharmacy software solutions are either prohibitively expensive for small businesses, operate in isolation without network connectivity, or lack features essential for multi-branch operations.

**PharmaCare** addresses these gaps by providing a unified, multi-tenant web platform where independent pharmacies can manage their internal operations — inventory, point of sale, prescriptions, and finance — while simultaneously reaching customers through a centralized marketplace. The system introduces an innovative **Platform Revenue Engine** allowing the system owner to passively collect a configurable tax margin from all sales, and a complete **bilingual (English/French) interface** to serve diverse user populations.

---

## 2. INTRODUCTION

### 2.1 Problem Statement

Small and medium-sized pharmacies in Cameroon and similar markets frequently face:

- **Manual inventory tracking** leading to expired or out-of-stock drugs.
- **No digital storefront** limiting customer reach to walk-ins only.
- **Fragmented systems** making cross-pharmacy availability checking impossible for patients.
- **No centralized revenue tracking** for SaaS operators managing multiple clients.
- **Language barriers** in systems that only support a single language.

### 2.2 Objectives

This project was developed to:

1. Automate inventory management, stock alerts, and expiry tracking.
2. Provide a secure, multi-role Point of Sale terminal.
3. Implement a multi-tenant environment where independent pharmacies register, are approved, and operate in full data isolation.
4. Offer a professional e-commerce experience allowing customers to browse and order drugs across all pharmacies.
5. Enable the platform owner to monetize the service through a built-in tax collection mechanism.
6. Deliver a bilingual (English/French) user interface switchable per user.

### 2.3 Scope

The system covers the following user-facing areas:

| Area | Covered |
|------|---------|
| Pharmacy Registration & Approval | ✅ |
| Staff Management (Pharmacist, Cashier) | ✅ |
| Drug Inventory CRUD | ✅ |
| Point of Sale Terminal | ✅ |
| Prescription Management | ✅ |
| Customer E-Commerce Portal | ✅ |
| Financial Reporting | ✅ |
| Platform Tax & Revenue Dashboard | ✅ |
| Bilingual EN/FR UI | ✅ |
| Database Backup | ✅ |
| Audit Logging | ✅ |

---

## 3. SYSTEM ARCHITECTURE

### 3.1 Architectural Pattern

The system follows a modular **Client-Server Architecture** with clean layer separation:

- **Presentation Layer**: Responsive HTML5/CSS3 interface using Bootstrap 5, Font Awesome 6, and Vanilla JavaScript. Features glassmorphism effects, gradient cards, and a pink premium theme.
- **Logic Layer**: Server-side processing using PHP 8+ with Role-Based Access Control (RBAC). All business rules enforced here.
- **Data Layer**: MySQL relational database accessed exclusively via PDO Prepared Statements.

```
Browser (HTML/CSS/JS)
        │  HTTP Request
        ▼
  Apache (XAMPP)
        │
        ▼
  PHP 8+ Runtime
  ┌─────────────────────────────────┐
  │  includes/config/database.php  │ ← DB connection, constants, session
  │  includes/functions/auth.php   │ ← RBAC, login, session guards
  │  includes/functions/helpers.php│ ← Utilities, formatting, logging
  │  includes/functions/lang.php   │ ← EN/FR translation dictionary
  │  includes/templates/header.php │ ← Navigation sidebar + topbar
  └─────────────────────────────────┘
        │
        ▼
   MySQL Database (pharmacy_db)
```

### 3.2 Multi-Tenancy Design

Each pharmacy is a **tenant** identified by a unique `pharmacy_id`. Every data table (`medicines`, `sales`, `customers`, `suppliers`, `expenses`, `users`) carries this foreign key. All queries are automatically scoped to the session's `pharmacy_id`, guaranteeing complete data isolation between branches.

The **Global Administrator** holds `pharmacy_id = NULL`, granting unrestricted read-access across all tenants for oversight, without belonging to any single branch.

### 3.3 Database Entity Relationship Summary

```
pharmacies ──< users
pharmacies ──< medicines ──< sale_items
pharmacies ──< sales ──< sale_items
pharmacies ──< suppliers
pharmacies ──< customers
pharmacies ──< expenses
pharmacies ──< settings
pharmacies ──< audit_logs
users ──< sales
users ──< audit_logs
```

**Key Schema Decisions:**

| Decision | Rationale |
|----------|-----------|
| `users.pharmacy_id` allows NULL | Supports Global Admin and platform-level customers |
| `settings` uses composite UNIQUE key on `(setting_key, pharmacy_id)` | Allows NULL `pharmacy_id` for global settings while preventing duplicate per-pharmacy keys |
| `sales.platform_tax` column | Stores the exact platform fee per transaction for immutable audit trail |
| `users.language` column | Persists per-user language preference across sessions |

---

## 4. FUNCTIONAL MODULES

### 4.1 Global Administration Module

The **Global System Administrator** (no pharmacy affiliation) has exclusive access to:

- **Pharmacy Management** (`pharmacies.php`): Review registration documents (license, owner ID, pharmacist credentials) and Approve / Suspend pharmacies.
- **Platform Revenue** (`platform_revenue.php`): View today's, monthly, and lifetime tax earnings broken down per pharmacy with gross sales volume.
- **Platform Tax Configuration** (`settings.php`): Set the system-wide tax percentage applied to every sale across all branches.
- **Support Inbox** (`support_messages.php`): Handle public contact form submissions.
- **Global Orders** (`manage_orders.php`): Monitor all customer orders network-wide.
- **Audit Logs** (`audit_logs.php`): Full action log across all users and branches.

### 4.2 Branch Administration Module

Branch Admins manage their own pharmacy and cannot access other branches:

- User creation and role assignment within their branch
- Branch-specific settings (currency, tax rate, system name)
- Financial reports filtered to their data

### 4.3 Inventory Management (`inventory.php`)

- Full CRUD (Add / Edit / Delete) for medicines
- Fields: Name, Generic Name, Category, Supplier, Price, Cost Price, Quantity, Unit, Reorder Level, Expiry Date, Barcode
- **Low Stock Alert**: Dashboard widget + notification bell when quantity ≤ reorder level
- **Expiry Alert**: Dashboard widget with colour-coded urgency — medicine names are **clickable links** navigating to their inventory record
- Global Admin sees all pharmacies' inventory, grouped by branch

### 4.4 Point of Sale Terminal (`pos.php`)

- Searchable product grid populated from active inventory
- JavaScript-powered live cart (no page reload)
- Real-time subtotal, tax display, and grand total
- **Pharmacist workflow**: Creates a *Pending Sale* forwarded to the Cashier queue
- **Cashier workflow**: Confirms pending entries or makes direct sales
- Payment methods: Cash / Card / Mobile Money
- Generates a printable receipt (`receipt.php`)

### 4.5 Platform Tax & Revenue Engine *(v3.0)*

A distinctive feature that enables SaaS monetization:

1. Global Admin sets `platform_tax_rate` (e.g., 2%) in Settings — visible only to them.
2. `process_sale.php` fetches this rate and computes `platform_tax = grand_total × rate / 100`.
3. The value is saved in the `sales.platform_tax` column alongside the sale.
4. **Platform Revenue page** aggregates these values per pharmacy and platform-wide, giving the Admin a real-time earnings dashboard.

### 4.6 Customer Portal & E-Commerce

- Customers browse medicines by selecting a registered pharmacy (`inventory.php?pharma=X`)
- Cart supports multiple medicines; persisted across pages in the PHP session
- Smart checkout: generates separate invoices per pharmacy (`finalize_sale.php`)
- Order history viewable in `orders.php`
- Prescription upload and status tracking

### 4.7 Prescription Management (`prescriptions.php`)

- Pharmacists and Branch Admins can view, accept, or reject prescriptions
- Customers submit prescriptions linked to their account
- Status workflow: **Pending → Filled → Cancelled**

### 4.8 Financial Reporting (`reports.php` / `expenses.php`)

- Sales reports with date-range filtering
- Expense tracking by category (Rent, Utilities, Salary, Supplies, Marketing, Other)
- Visual sales trend chart (last 7 days via Chart.js)
- Exportable data

### 4.9 Bilingual Support — EN / FR *(v3.0)*

Every user can select their preferred language independently:

- Language saved in `users.language` column in the database
- Loaded into `$_SESSION['language']` on login
- Toggle via globe icon 🌐 in the top navigation bar
- The `__($key)` function resolves the correct string from `lang.php` at runtime
- Fully covers: Navigation, Dashboard, Stats Widgets, POS Labels, Inventory Tables, Action Buttons

---

## 5. TECHNICAL IMPLEMENTATION & SECURITY

### 5.1 Security Architecture

| Mechanism | Implementation |
|-----------|---------------|
| Password Security | PHP `password_hash()` / `password_verify()` with `PASSWORD_BCRYPT` |
| SQL Injection | PDO Prepared Statements enforced throughout all database queries |
| XSS Prevention | `htmlspecialchars()` applied to all dynamic output |
| CSRF Protection | Session-bound token generated per-session |
| Role Enforcement | `require_role()` and `has_role()` guards on every page |
| Multi-Tenant Isolation | All queries automatically scoped by `$_SESSION['pharmacy_id']` |
| File Upload Validation | MIME-type checking and size limits on document uploads |
| Session Hardening | Session destroyed completely on logout; regenerated on login |

### 5.2 Database Architecture

The `install.php` script is the **single source of truth** for all database operations:

- Creates the MySQL database if it doesn't exist
- Executes the full schema from `database/schema.sql`
- Applies all schema upgrades via a `$columns_to_check` array (checks before altering to prevent duplicate column errors)
- Creates new tables if missing
- Seeds pharmacies, users, suppliers, medicines, and customers
- Elevates the platform admin to Global status (`pharmacy_id = NULL`)

No other PHP file contains schema modifications, ensuring clean architecture.

### 5.3 Portability

- Dynamic `BASE_URL` detection in `database.php` ensures assets and links work regardless of installation path (XAMPP subfolder, root, or renamed folder)
- All file paths use `__DIR__`-based references, not hardcoded absolute paths

---

## 6. USER INTERFACE & DESIGN

- **Theme**: Premium Pink Gradient (`#FF1493` Deep Pink as primary, `#FFB6C1` Light Pink as secondary)
- **Layout**: Fixed sidebar navigation + fluid main content area
- **Dark Mode**: Toggle preserved across pages via `localStorage`
- **Responsive**: Bootstrap 5 grid adapts for tablet and mobile
- **Micro-animations**: Hover lift effects, fade transitions, skeleton loading
- **Glassmorphism**: Translucent card overlays on gradient backgrounds for depth

---

## 7. TESTING

### 7.1 Test Accounts

| Username | Role | Branch |
|----------|------|--------|
| `admin` / `Admin@123` | Global Admin | Platform |
| `pharmacist` / `Pharma@123` | Pharmacist | Main PharmaCare |
| `cashier` / `Cashier@123` | Cashier | Main PharmaCare |
| `elite_pharma` / `Elite@123` | Pharmacist | Elite Wellness |
| `clinic_cash` / `Clinic@123` | Cashier | Community Clinic |
| `test_customer` / `Customer@123` | Customer | Public |

### 7.2 Test Scenarios Verified

- ✅ Role isolation: cashier cannot access inventory edit
- ✅ Multi-tenant isolation: pharmacist at Branch A cannot see Branch B data
- ✅ Platform tax calculated and stored on every sale
- ✅ Language switching persists after logout/login
- ✅ Global Admin settings save without `pharmacy_id` constraint error
- ✅ Expiring medicine links navigate to the correct inventory row
- ✅ Platform Revenue aggregates correctly per-pharmacy

---

## 8. CHALLENGES & SOLUTIONS

| Challenge | Solution |
|-----------|----------|
| MySQL `pharmacy_id` as Primary Key prevents NULL global settings | Restructured settings table with an auto-increment `id` and a UNIQUE composite key |
| Language strings hardcoded after multiple dev phases | Systematic sweep replacing all static text with `__()` translation calls |
| Auto-migration logic bloating database config | Centralized all schema upgrades exclusively in `install.php` |
| Cart reservations causing stale stock holds | `cart_reservations` table with `expires_at` timestamp for TTL cleanup |
| Pharmacist and Cashier POS role divergence | Separate submit logic based on `has_role('pharmacist')` — routes to pending vs direct completion |

---

## 9. CONCLUSION & FUTURE WORK

**PharmaCare v3.0** successfully demonstrates the feasibility of a commercially viable, multi-tenant healthcare SaaS platform. It bridges the gap between internal pharmacy operations and external customer service while providing the platform owner with a passive revenue mechanism and real-time financial visibility.

### Planned Future Enhancements

| Enhancement | Priority |
|-------------|----------|
| Mobile Money API integration (MTN MoMo / Orange Money) | High |
| SMS/WhatsApp notification for low stock and order updates | High |
| AI-driven demand forecasting from sales history | Medium |
| Real-time pharmacist–customer chat | Medium |
| Progressive Web App (PWA) offline support | Low |
| Multi-currency support | Low |

---

## 10. REFERENCES

- PHP Documentation: https://www.php.net/docs.php
- Bootstrap 5: https://getbootstrap.com/docs/5.3/
- Chart.js: https://www.chartjs.org/docs/
- MySQL 8 Reference: https://dev.mysql.com/doc/
- OWASP Security Cheatsheet: https://cheatsheetseries.owasp.org/

---

*© 2026 Taguenang Leslie. All Rights Reserved.*
