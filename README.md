# 🏥 PharmaCare — Multi-Tenant Pharmacy Management System

> **Author:** Taguenang Leslie &nbsp;|&nbsp; **Version:** 3.0 &nbsp;|&nbsp; **© 2026**

PharmaCare is a comprehensive, web-based pharmacy management solution designed for pharmacy networks. It leverages a **multi-tenant architecture**, allowing multiple pharmacy branches to operate on a single platform with full data isolation, while the Global Administrator manages registrations, collects platform revenue, and oversees the entire network.

---

## 🚀 Key Features

### 👤 User Roles
| Role | Access Level |
|------|-------------|
| **Global Admin** | Full platform control, pharmacy approvals, platform tax & revenue tracking |
| **Branch Admin** | Manages their own pharmacy, staff, settings, reports |
| **Pharmacist** | Inventory, prescriptions, pending sales to cashier |
| **Cashier** | POS terminal, pending sales processing, customer management |
| **Customer** | Browse drugs across pharmacies, cart & checkout, prescriptions |

### 📦 Inventory & Supply Chain
- Real-time stock tracking with **Low Stock Alerts** and **Reorder Level** thresholds
- **Expiry Date Monitoring** with colour-coded urgency (dashboard clickable links → inventory entry)
- Supplier management with payment terms tracking
- Barcode field support for quick lookup

### 🛒 Point of Sale (POS)
- Searchable product grid with instant cart updates
- Pharmacy-scoped tax calculation per sale
- Pharmacist creates **Pending Sales** → Cashier confirms and receipts
- Payment methods: Cash, Card, Mobile Money

### 🌐 Customer Portal
- Browse active pharmacies and their drug inventories
- Session-based shopping cart with multi-pharmacy support
- Smart checkout: auto-generates separate invoices per pharmacy
- Prescription upload for pharmacist review

### 💰 Platform Tax & Revenue Engine *(v3.0)*
- Global Admin sets a **Platform Tax Rate (%)** in system settings
- Automatically deducted from every sale system-wide
- **Platform Revenue** dashboard shows earnings per pharmacy and lifetime totals

### 🌍 Bilingual Support — English / French *(v3.0)*
- Per-user language preference (stored in DB, switchable from header)
- Full translation of navigation, dashboard, POS, inventory UI
- Translation dictionary in `includes/functions/lang.php`

### 📊 Reporting & Analytics
- 7-day sales trend chart on dashboard
- Sales reports with date filtering
- Stock and expiry reports
- Financial summaries per pharmacy

### 🔒 Security
- bcrypt password hashing
- PDO Prepared Statements (no SQL injection)
- `htmlspecialchars()` XSS prevention on all output
- CSRF session token
- Strict role-based page guards (`require_role()`)
- Multi-tenant data isolation scoped by `pharmacy_id`

---

## 🛠️ Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8+, PDO |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 5, Vanilla CSS/JS |
| Charts | Chart.js |
| Icons | Font Awesome 6 |

---

## ⚙️ Installation

> **All database setup lives exclusively in `install.php`.**

1. Place the project folder in your XAMPP `htdocs`:
   ```
   C:/xampp/htdocs/Pharmacy_Management_System/
   ```

2. Set your database credentials in `includes/config/database.php`:
   ```php
   define('DB_NAME', 'pharmacy_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. Start Apache + MySQL in XAMPP, then visit:
   ```
   http://localhost/Pharmacy_Management_System/install.php
   ```

4. The installer will:
   - Create the database automatically
   - Run the full schema
   - Apply all column and table upgrades
   - Seed 3 sample pharmacies, 8 users, inventory, and customers

5. Access the system:
   ```
   http://localhost/Pharmacy_Management_System/
   ```

---

## 🔑 Quick Login

| Username | Password | Role |
|----------|----------|------|
| `admin` | `Admin@123` | Global Admin |
| `pharmacist` | `Pharma@123` | Pharmacist |
| `cashier` | `Cashier@123` | Cashier |
| `test_customer` | `Customer@123` | Customer |

> Full list of test accounts: **[TEST_ACCOUNTS.md](TEST_ACCOUNTS.md)**

---

## 📂 Key Files

```
install.php              ← ONE-TIME setup/seeding (master script)
dashboard.php            ← Main hub for admin & staff
pos.php                  ← Point of Sale terminal
inventory.php            ← Drug management and browsing
platform_revenue.php     ← Global Admin revenue tracker
includes/functions/
  auth.php               ← Login, session, role guards
  helpers.php            ← Utilities: format_currency, sanitize, log_activity
  lang.php               ← EN/FR translation dictionary
database/schema.sql      ← Master database schema
```

> See **[DOCUMENTATION.md](DOCUMENTATION.md)** for the full technical reference.

---

## 📝 License & Copyright
© 2026 **Taguenang Leslie**. All rights reserved.  
Developed for educational and professional demonstration purposes.
