# 📄 SYSTEM DEFENSE REPORT: PHARMACARE
**Topic**: Design and Implementation of a Multi-Tenant Pharmacy Management System with Cross-Business E-Commerce Capabilities.

---

## 1. ABSTRACT
In the contemporary healthcare landscape, efficient drug distribution and management are critical. Most existing solutions are either too expensive for small pharmacies or lack the connectivity to allow customers to shop across different providers. **PharmaCare** addresses this by providing a unified, multi-tenant platform where pharmacies can manage their internal operations (Inventory, POS, Sales) while simultaneously reaching customers through a centralized marketplace with a session-based shopping cart.

---

## 2. INTRODUCTION
### 2.1 Problem Statement
Small and medium-sized pharmacies often struggle with:
- Manual inventory tracking leading to expired stock.
- Lack of digital visibility for potential customers.
- Fragmented systems making it difficult for patients to find local availability of rare drugs.

### 2.2 Objectives
The primary objective of this project is to develop a software system that:
- Automates stock management and alerts.
- Provides a secure Point of Sale (POS) environment.
- Enables a multi-tenant environment where individual pharmacies can register and manage their own staff and data.
- Offers a professional e-commerce experience for customers to browse and order medicine.

---

## 3. SYSTEM ARCHITECTURE
### 3.1 Architectural Pattern
The system follows a modular **Client-Server Architecture**:
- **Presentation Layer**: Responsive web interface using HTML5, CSS3 (Bootstrap), and jQuery.
- **Logic Layer**: Server-side processing using PHP 8.2, implementing Role-Based Access Control (RBAC).
- **Data Layer**: Relational database management using MySQL with PDO for secure data transactional integrity.

### 3.2 Database Design (Key Entities)
- **Users**: Central identity storage for Admins, Pharmacists, Cashiers, and Customers.
- **Pharmacies**: Stores business-specific metadata (License No, Type, Address).
- **Medicines**: Tenant-specific inventory items with price, cost, and stock tracking.
- **Sales/Invoices**: Record of transactions, split by pharmacy to ensure correct revenue allocation.

---

## 4. FUNCTIONAL MODULES
### 4.1 Administrative Module
Provides the "System Admin" with tools to approve or suspend pharmacies, monitoring the overall system health and audit logs.

### 4.2 Inventory Management
Allows pharmacists to track stock levels, generic names, and category classifications. Includes a **Reorder Alert** system for items below a certain threshold.

### 4.3 Point of Sale (POS)
A lightweight terminal for cashiers to process in-person sales, update inventory instantly, and generate invoices.

### 4.4 Customer Portal & Shopping Cart
A unique feature that allows users to:
1. Browse medicines from all active pharmacies.
2. Add multiple items to a persistent cart.
3. Perform a **Multi-Invoice Checkout**, where the system automatically generates separate invoices for each business involved in the purchase.
4. **Prescription Upload**: Direct secure submission of medical prescriptions to a pharmacy of choice for professional verification.

### 4.5 Global Clinical & Financial Oversight (Admin)
The System Admin has a high-level console to monitor all orders, expenses, and audit logs across the platform. Data is logically grouped by pharmacy to ensure transparency while maintaining organizational structure.

---

## 5. TECHNICAL IMPLEMENTATION & SECURITY
### 5.1 Security & Data Isolation
- **Multi-Tenant Data Shield**: All database operations for `customers`, `suppliers`, `inventory`, `users`, `expenses`, and `audit_logs` are strictly gated by `pharmacy_id`, preventing any cross-tenant data leakage.
- **POS Tenant Context**: Sales processed via the Point of Sale explicitly verify and attribute inventory and revenue to the correct pharmacy, even when performed by a Platform Admin.
- **Robust File Validation**: Pharmacy registration documents undergo strict backend validation (MIME-type check and size limits) to prevent malicious script injection.
- **Password Hashing**: Uses `PASSWORD_BCRYPT` for industry-standard credential safety.
- **Input Sanitization**: Cross-Site Scripting (XSS) prevention via `htmlspecialchars`.
- **SQL Protection**: Forced use of Prepared Statements to eliminate SQL Injection risks.

### 5.2 Portability & Deployment
A major technical achievement is the **Intelligent One-Click Installation** system. The `install.php` script handles self-initialization, real-time schema upgrades (adding missing columns to older DBs), and automated seeding of complex relationship data, making the project deployable in seconds.

---

## 6. CONCLUSION & FUTURE WORK
PharmaCare successfully demonstrates the feasibility of a multi-tenant healthcare marketplace. It bridges the gap between internal business management and external customer outreach.

### Future Enhancements:
- Integration of Mobile Money payment APIs (MTN/Orange).
- Real-time chat between pharmacists and customers.
- AI-driven demand forecasting based on sales history.

---
**Presented By**: Taguenang Leslie
**Date**: March 2026
**Platform**: PharmaCare v3.0
