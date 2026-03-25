# 🏥 PharmaCare — Multi-Tenant Pharmacy Management System

> **Author:** Taguenang Leslie &nbsp;|&nbsp; **Version:** 3.2 &nbsp;|&nbsp; **© 2026**

PharmaCare is a comprehensive, web-based pharmacy management solution built for pharmacy networks. It uses a **multi-tenant architecture** — multiple pharmacy branches operate on one platform with complete data isolation — while the Global System Administrator manages the entire network, collects passive platform revenue, and has full CRUD control over all pharmacies.

---

## 🚀 Key Features

### 👤 User Roles & Permissions

| Role | Access |
|------|--------|
| **Global Admin** | Full platform control — Add/Approve/Suspend/**Delete** pharmacies, Platform Revenue, Tax Config, Global oversight |
| **Branch Admin** | Manage their pharmacy — Staff, Settings, Reports, Inventory |
| **Pharmacist** | Inventory, Prescriptions, Pending Sales → Cashier |
| **Cashier** | POS terminal, Confirm Pending Sales, Customers |
| **Customer** | **Unified search across all pharmacies**, Cart, Checkout, Orders, Prescriptions |

> **Auto-Upgrade on Approval**: When a customer submits a pharmacy registration and the Admin approves it, the customer's account is automatically promoted to **Branch Admin** of the new pharmacy.

### 🔍 Unified Drug Search *(Customer — v3.2)*
- **Cross-Pharmacy Discovery**: Search for drugs across the entire network from a single page.
- **Source Transparency**: Each result clearly shows which pharmacy the drug belongs to with a source badge.
- **Live Price Comparison**: Compare prices for the same drug across different branches in one view.
- **Synchronized Stock Holds**: Clicking "Add to Cart" instantly reserves the stock in the database for 30 minutes.

### 🏥 Pharmacy Management *(Global Admin)*
- **Add Pharmacy** directly (immediately active, no approval needed)
- **Approve / Suspend** customer-submitted pharmacy applications
- **Delete Pharmacy** permanently — all branch staff are automatically demoted to customer accounts
- View legal documents (license, pharmacist credentials, business registration)

### 📦 Inventory & Supply Chain
- Real-time stock tracking with Low Stock Alerts
- Expiry Date monitoring — dashboard entries are **clickable links** to the inventory record
- Supplier management with payment term tracking

### 🛒 Point of Sale (POS)
- Searchable product grid with live cart (no page reload)
- Pharmacist → Cashier pending sale workflow
- Payment: Cash, Card, Mobile Money
- Printable receipt generation

### 💰 Platform Tax & Revenue Engine
- Global Admin sets a platform-wide tax rate (%)
- Auto-deducted from every sale system-wide — stored immutably per transaction
- **Platform Revenue page** shows earnings per pharmacy + lifetime total

### 🌍 Bilingual Support — English / French
- Per-user language preference saved in database
- Full translation: Navigation, Dashboard, POS, Inventory
- Switch via 🌐 globe icon in the top navigation bar

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

> **`install.php` is the only setup script. All database changes live here.**

1. Place the project in your XAMPP `htdocs`:
   ```
   C:/xampp/htdocs/Pharmacy_Management_System/
   ```

2. Configure `includes/config/database.php`:
   ```php
   define('DB_NAME', 'pharmacy_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. Start Apache + MySQL, then navigate to:
   ```
   http://localhost/Pharmacy_Management_System/install.php
   ```
   This creates the database, runs the full schema, applies all upgrades, and seeds sample data.

4. Launch the app:
   ```
   http://localhost/Pharmacy_Management_System/
   ```

---

## 🔑 Quick Login

| Username | Password | Role |
|----------|----------|------|
| `admin` | `Admin@123` | Global Admin |
| `pharmacist` | `Pharma@123` | Pharmacist (Main PharmaCare) |
| `cashier` | `Cashier@123` | Cashier (Main PharmaCare) |
| `test_customer` | `Customer@123` | Customer |

> Full list + test scenarios: **[TEST_ACCOUNTS.md](TEST_ACCOUNTS.md)**

---

## 📂 Key Files

```
install.php              ← ONE-TIME database setup & seeding
dashboard.php            ← Central hub for staff & admin
pos.php                  ← Point of Sale terminal
inventory.php            ← Unified search (Customer) & Stock management (Staff)
cart.php                 ← Synchronized stock reservation & cart logic
pharmacies.php           ← Pharmacy CRUD (Global Admin only)
platform_revenue.php     ← Tax earnings dashboard (Global Admin)
settings.php             ← System configuration
includes/functions/
  auth.php               ← Login, session, RBAC guards
  helpers.php            ← format_currency, sanitize, log_activity
  lang.php               ← EN/FR translation dictionary
database/schema.sql      ← Master database schema
```

> Full technical reference: **[DOCUMENTATION.md](DOCUMENTATION.md)**

---

## 📝 License & Copyright
© 2026 **Taguenang Leslie**. All rights reserved.  
Developed for educational and professional demonstration purposes.
