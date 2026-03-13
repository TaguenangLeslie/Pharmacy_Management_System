# 🏥 PharmaCare - Multi-Tenant Pharmacy Management System

PharmaCare is a robust, web-based management solution designed for pharmacy networks and independent drugstores. It leverages a multi-tenant architecture, allowing multiple pharmacies to operate on a single platform while maintaining data isolation and individual business branding.

---

## 🚀 Key Features

### 👤 User Roles & Management
- **Platform Admin**: Manages pharmacy registrations, approves new businesses, and oversees system-wide audits.
- **Pharmacist**: Manages inventory, monitors drug expiry, and handles prescriptions.
- **Cashier**: Efficient Point of Sale (POS) operation for quick checkouts.
- **Customer**: Browses available drugs across all registered pharmacies, manages a multi-item shopping cart, and tracks order history.

### 📦 Inventory & Supply Chain
- **Real-time Stock Tracking**: Automatic alerts for low-stock and near-expiry items.
- **Supplier Management**: Integrated contact and payment term tracking.
- **Barcode Support**: Quick item entry and lookup.

### 🛒 Shopping Cart & Ordering
- **Cross-Pharmacy Cart**: Customers can select drugs from different pharmacies in a single session.
- **Smart Checkout**: Automatically splits orders into separate invoices per pharmacy for accurate financial reporting.
- **Order Tracking**: Status updates from 'Pending' to 'Completed'.

### 📊 Reporting & Analytics
- **Financial Reports**: Sales summaries, expense tracking, and profit analysis.
- **Activity Logs**: Full audit trail of all system interactions for security and compliance.
- **Backups**: One-click database export for data safety.

---

## 🛠️ Technology Stack
- **Backend**: PHP 8.2 (Vanilla with PDO for security)
- **Database**: MySQL / MariaDB
- **Frontend**: Bootstrap 5, Font Awesome, jQuery
- **Aesthetics**: Premium Pink Gradient Theme with responsive glassmorphism effects.

---

## ⚙️ Installation Instructions

PharmaCare is designed for maximum portability. Follow these steps to set up the project on any machine (XAMPP/WAMP/MAMP recommended).

1. **Clone/Copy Project**: Place the project folder in your server's root (e.g., `htdocs` for XAMPP).
2. **Database Config**: Open `includes/config/database.php` and update your database credentials (`DB_USER`, `DB_PASS`).
3. **One-Click Setup**: Open your browser and navigate to:
   ```
   http://localhost/Pharmacy_Management_System/install.php
   ```
   This will automatically:
   - Create the `pharmacy_db` database.
   - Initialize all tables and relations.
   - Seed the system with a default Pharmacy and Admin account.
4. **Login**: Use the following credentials:
   - **Username**: `admin`
   - **Password**: `Admin@123`
5. **Explore Test Roles**: For a full list of test accounts including Pharmacists, Cashiers, and Customers, see **[TEST_ACCOUNTS.md](TEST_ACCOUNTS.md)**.

---

## 📂 Project Structure

```text
├── assets/             # CSS, JS, and Images
├── database/           # Master SQL schema
├── includes/           # Core logic
│   ├── config/         # Database and global constants
│   ├── functions/      # Auth, Helpers, Language logic
│   └── templates/      # Header, Footer, Sidebar
├── uploads/            # Avatars and Prescription images
├── install.php         # Master installation script
├── dashboard.php       # Main administrative hub
├── inventory.php       # Drug management and browsing
├── cart.php            # Session-based shopping handler
└── pos.php             # Point of Sale terminal
```

---

## 🔒 Security & Portability
- **CSRF Protection**: All sensitive actions are guarded by session tokens.
- **SQL Injection Prevention**: Prepared statements used throughout the system.
- **Environment Agnostic**: The system uses dynamic `BASE_URL` detection, ensuring styles and assets never break regardless of the installation path.

---

## 📝 License
This project is developed for educational and professional demonstration purposes. Feel free to modify and expand upon it.
