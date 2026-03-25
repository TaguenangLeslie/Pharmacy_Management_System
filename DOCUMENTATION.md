# PharmaCare — Complete System Documentation
> **Author:** Taguenang Leslie | **Version:** 3.2 | **Date:** March 2026  
> **Stack:** PHP 8+, MySQL, Bootstrap 5, Chart.js

---

## Table of Contents
1. [Project Overview](#1-project-overview)
2. [System Architecture](#2-system-architecture)
3. [Directory Structure](#3-directory-structure)
4. [Database Schema](#4-database-schema)
5. [User Roles & Permissions](#5-user-roles--permissions)
6. [Core Modules (Dynamic Features)](#6-core-modules-dynamic-features)
   - [6.6 Unified Cross-Pharmacy Search (v3.2)](#66-unified-cross-pharmacy-search-v32)
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

**PharmaCare** is a fully dynamic, multi-tenant Pharmacy Management System. Every user action — sales, search queries, stock reservations, settings, and pharmacy approvals — is **persisted to the MySQL database in real time**. 

### What "Fully Dynamic" Means in This System

| Feature | Database Table | Persisted |
|---------|---------------|-----------|
| User language preference | `users.language` | ✅ |
| Platform tax rate | `settings` (pharmacy_id IS NULL) | ✅ |
| Platform tax per sale | `sales.platform_tax` | ✅ |
| Pharmacy status (approve/suspend/delete) | `pharmacies.status` | ✅ |
| User role auto-upgrade on approval | `users.role`, `users.pharmacy_id` | ✅ |
| Inventory updates (unified search) | `medicines` | ✅ |
| **Live Stock Reservations** | `cart_reservations` | ✅ |
| System settings | `settings` | ✅ |
| Cart → Sale → Stock decrement | `sales`, `sale_items`, `medicines.quantity` | ✅ |

---

## 2. System Architecture

```
Browser (HTML5 / Bootstrap 5 / JS)
         │
         │  HTTP / AJAX
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
Every data table carries a `pharmacy_id` foreign key. Staff-facing pages are scoped to `$_SESSION['pharmacy_id']`. The Global Admin and Customers can interact with data across all branches (Customers via the **Unified Search**).

---

## 3. Directory Structure

```
Pharmacy_Management_System/
│
├── install.php                  ⭐ ONE-TIME setup — the ONLY migration script
├── index.php                    # Public landing page (settings-driven content)
├── login.php / logout.php       # Auth entry/exit
├── register_pharmacy.php        # Multi-step pharmacy application form
│
├── dashboard.php                # Main hub (stats, charts, alerts)
├── pos.php                      # Point of Sale terminal
├── inventory.php                # Unified search (Customer) & Stock management (Staff) ⭐
├── cart.php                     # Stock reservation & Cart logic (v3.2 Sync) ⭐
├── checkout.php                 # Multi-branch order finalization
├── pharmacies.php               # Pharmacy CRUD (Global Admin only)
├── platform_revenue.php         # Tax earnings per pharmacy
├── settings.php                 # System configuration (Global & Local)
├── database/schema.sql          # Master schema (all tables)
└── includes/
    ├── functions/lang.php       # EN/FR dictionary
    └── templates/header.php    # Nav sidebar + topbar
```

---

## 4. Database Schema

### Table: `cart_reservations` (v3.2 Integration)
Used for real-time stock holds when a customer adds an item to their cart.
- `session_id`: Unique session identifier.
- `medicine_id`: FK to `medicines`.
- `pharmacy_id`: FK to `pharmacies`.
- `quantity`: Amount held.
- `expires_at`: TTL timestamp (default 30 mins). Stock is returned to `medicines` if the reservation expires.

---

## 5. User Roles & Permissions

See Phase 32 documentation for base roles. 

**v3.2 Update**: Customers now have a **Unified Search** role privilege, allowing them to query the `medicines` and `pharmacies` tables simultaneously to find the best price and location for a drug.

---

## 6. Core Modules (Dynamic Features)

### 6.6 Unified Cross-Pharmacy Search (v3.2)
Unlike traditional systems that require selecting a branch first, PharmaCare v3.2 allows customers to:
1. **Query All Active Branches**: Type a drug name to see it across all pharmacies.
2. **Synchronized Cart Addition**: Adding an item from the results list triggers a `cart.php?action=add` request which:
   - Verifies stock at the specific pharmacy.
   - Atomically deducts quantity from `medicines`.
   - Creates a TTL reservation in `cart_reservations`.
3. **Price Transparency**: See source branch name and price on each result card.

---

## 10. Authentication & Security

| Mechanism | Implementation |
|-----------|---------------|
| Tenant Isolation | Staff queries scoped to pharmacy ID; Customers see cross-branch data via unified view only. |
| **Concurrency Control** | `FOR UPDATE` locks used during stock reservation to prevent double-selling drugs. |

---

## 14. Changelog

| Version | Phase | Key Changes |
|---------|-------|-------------|
| 3.0 | 30 | Platform Revenue, Clickable expiry links |
| 3.1 | 31-32 | Pharmacy CRUD (Add/Delete), Auto-role upgrade |
| **3.2** | **33** | **Unified Cross-Pharmacy Search, Synchronized Stock Reservations in Cart** |

---
*© 2026 Taguenang Leslie. All Rights Reserved.*
