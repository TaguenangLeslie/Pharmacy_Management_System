# 🔐 PharmaCare — Test Account Credentials

> [!IMPORTANT]
> All passwords are case-sensitive. Run `install.php` first to ensure these accounts exist in your database.

---

## 🛠️ Platform Administration

| Role | Username | Email | Password | Access |
|:-----|:---------|:------|:---------|:-------|
| **Global Admin** | `admin` | `admin@pharmacare.com` | `Admin@123` | Full platform control, no pharmacy affiliation |

**Global Admin can:**
- Approve / Suspend / **Delete** pharmacies
- Directly **Add** a new pharmacy (immediately active)
- View Platform Revenue (per-pharmacy tax breakdown)
- Configure platform-wide tax rate
- Manage all users system-wide
- View audit logs, support inbox, database backup

---

## 🏥 Pharmacy 1 — Main PharmaCare (Retail)

| Role | Username | Email | Password |
|:-----|:---------|:------|:---------|
| **Branch Admin** | *(none seeded — assign via admin → Users)* | — | — |
| **Pharmacist** | `pharmacist` | `pharma@example.com` | `Pharma@123` |
| **Cashier** | `cashier` | `cashier@example.com` | `Cashier@123` |

---

## 🏢 Pharmacy 2 — Elite Wellness (Wholesale)

| Role | Username | Email | Password |
|:-----|:---------|:------|:---------|
| **Pharmacist** | `elite_pharma` | `elite_ph@example.com` | `Elite@123` |
| **Cashier** | `elite_cash` | `elite_cs@example.com` | `Elite@123` |

---

## 🚑 Pharmacy 3 — Community Clinic (Clinic)

| Role | Username | Email | Password |
|:-----|:---------|:------|:---------|
| **Pharmacist** | `clinic_pharma` | `clinic_ph@example.com` | `Clinic@123` |
| **Cashier** | `clinic_cash` | `clinic_cs@example.com` | `Clinic@123` |

---

## 🛒 Customer Portal

| Role | Username | Email | Password |
|:-----|:---------|:------|:---------|
| **Test Customer** | `test_customer` | `customer@example.com` | `Customer@123` |

---

## 📝 Key Test Scenarios (v3.2)

### 1. Unified Cross-Pharmacy Search (v3.2)
1. Log in as `test_customer`
2. Go to **Inventory**
3. Type "Paracetamol" in the search bar and click **Filter**
4. Observe medicines from **Main PharmaCare**, **Elite Wellness**, and **Community Clinic** in one grid.
5. Click **Add** — Observe the item enters the cart and a reservation is made implicitly in the background.

### 2. Pharmacy Registration → Auto Admin Upgrade
1. Log in as `test_customer`
2. Visit **Register Pharmacy** (link in navigation)
3. Fill out the multi-step form and submit
4. Log in as `admin` → go to **Pharmacy Management**
5. Click **Approve** — the customer's account **instantly upgrades to Branch Admin**

### 3. Admin Creates Pharmacy Directly
1. Log in as `admin` → **Pharmacy Management**
2. Click **Add Pharmacy** (top-right button)
3. Fill form and submit — pharmacy is **immediately active**
4. Assign a Branch Admin via **Users** page

### 4. Delete a Pharmacy
1. Log in as `admin` → **Pharmacy Management**
2. Click **Delete** on any pharmacy and confirm
3. All staff from that branch are demoted to `customer` role automatically

### 5. Platform Tax Flow
1. Log in as `admin` → **Settings** → set Platform Tax Rate (e.g. `2`)
2. Log in as any cashier → make a sale via POS
3. Log back in as `admin` → **Platform Revenue** — see the collected tax per pharmacy

### 6. Language Toggle
1. Click the 🌐 globe icon in the header
2. Switch between **English** and **French**
3. All UI labels change instantly and the preference saves to your database profile

### 7. POS — Pharmacist Pending Sale
1. Log in as `pharmacist` → **POS**
2. Add items to cart and click **Send to Cashier**
3. Log in as `cashier` → **Pending Sales** — confirm the order
