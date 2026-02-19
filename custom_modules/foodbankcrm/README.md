# FoodbankCRM for Dolibarr ERP/CRM

A comprehensive food distribution and supply chain management module for [Dolibarr ERP/CRM](https://www.dolibarr.org). FoodbankCRM connects food banks with vendors (suppliers) and subscribers (beneficiaries), providing end-to-end management of food inventory, packaging, orders, payments, and delivery logistics.

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [User Roles](#user-roles)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Schema](#database-schema)
- [Directory Structure](#directory-structure)
- [Payment Integration](#payment-integration)
- [Security](#security)
- [License](#license)

---

## Features

### For Administrators
- **Dashboard** -- Real-time overview with revenue, active subscriptions, pending inventory, and open support tickets
- **Subscriber Management** -- Full CRUD for beneficiary/subscriber records with extended demographics (gender, DOB, family size, employment status, identification)
- **Vendor Management** -- Approve/reject vendor applications, manage vendor profiles with business details (RC number, Tax ID, banking info)
- **Inventory (Donations)** -- Track incoming food supplies from vendors with stock allocation, pricing, and warehouse assignment
- **Warehouse Management** -- Multiple warehouse locations with capacity tracking
- **Package Builder** -- Create food packages from individual items with per-item pricing and vendor preferences
- **Distribution/Orders** -- View and manage all subscriber orders with payment and delivery status
- **Subscription Tiers** -- Create and manage subscription plans (Annual, Donor, Guest) with pricing, duration, and benefits
- **User-Role Linking** -- Manually link Dolibarr users to vendor or subscriber profiles
- **Support Tickets** -- View and respond to vendor helpdesk tickets
- **Reports** -- Analytics and reporting on donations, distributions, and payments
- **Paystack Configuration** -- Admin setup page for payment gateway API keys

### For Vendors (Suppliers)
- **Self-Registration** -- Public registration form with OTP email verification and admin approval workflow
- **Vendor Dashboard** -- Overview of supply history, recent donations, and statistics
- **Inventory Submission** -- Submit new food inventory with product details, quantity, pricing, and warehouse destination
- **Product Catalog** -- Manage a catalog of products the vendor can supply
- **Supply History** -- View all past inventory submissions with status tracking
- **Vendor Profile** -- View and manage business profile information
- **Support Tickets** -- Create and track helpdesk tickets with admin responses

### For Subscribers (Beneficiaries)
- **Self-Registration** -- Public registration with subscription tier selection and OTP email verification
- **Subscriber Dashboard** -- Personalized dashboard with subscription status, order history, and quick actions
- **Product Catalog** -- Browse available food packages with pricing (requires active subscription)
- **Shopping Cart** -- Database-backed cart with quantity management
- **Checkout** -- Place orders with delivery address, choose between online payment or pay-on-delivery
- **Order Tracking** -- View order history with payment and delivery status
- **Subscription Management** -- View subscription details, renew or upgrade subscription tier
- **Profile Management** -- Update personal information and password

---

## Architecture

FoodbankCRM follows standard Dolibarr module conventions while adding custom public-facing pages for self-service registration and e-commerce functionality.

### Core Flow

```
Public Registration ──> OTP Verification ──> Account Created (Inactive)
                                                    |
                                        +-----------+-----------+
                                   [Vendor]                [Subscriber]
                                        |                       |
                                Admin Approval          Subscription Payment
                                        |                       |
                                  Dashboard               Dashboard
                                        |                       |
                              Submit Inventory        Browse -> Cart -> Checkout
                                                                            |
                                                                    +-------+-------+
                                                               [Pay Now]     [Pay on Delivery]
                                                                    |
                                                              Paystack -> Callback -> Order Confirmed
```

### Permission System

The module uses a custom `FoodbankPermissions` class (`class/permissions.class.php`) that determines user roles by checking the existence of linked records:

- **Admin** -- `$user->admin` flag (Dolibarr superadmin)
- **Vendor** -- User has a record in `foodbank_vendors` where `fk_user` matches
- **Beneficiary** -- User has a record in `foodbank_beneficiaries` where `fk_user` matches

### Login Redirect Flow

Two mechanisms ensure users land on the correct dashboard after login:

1. **Trigger** (`core/triggers/interface_99_modFoodbankcrm_Redirect.class.php`) -- Fires on `USER_LOGIN` events
2. **Gateway Page** (`index.php`) -- Smart router that checks role and redirects, also serves as the public welcome page for unauthenticated visitors

---

## User Roles

| Role | Registration | Activation | Dashboard | Key Capabilities |
|------|-------------|------------|-----------|------------------|
| **Admin** | N/A (Dolibarr superadmin) | Always active | `dashboard_admin.php` | Full CRUD, approve vendors, manage tiers, view reports |
| **Vendor** | `register_vendor.php` | OTP + Admin approval | `dashboard_vendor.php` | Submit inventory, manage products, support tickets |
| **Subscriber** | `register.php` | OTP + Subscription payment | `dashboard_beneficiary.php` | Browse catalog, place orders, manage subscription |

---

## Installation

### Prerequisites

- Dolibarr ERP/CRM v15.0 or later
- PHP 7.4 or later
- MySQL 5.7+ or MariaDB 10.3+
- cURL extension (for Paystack payment verification)

### Steps

1. **Copy module files** into Dolibarr's custom modules directory:
   ```
   htdocs/custom/foodbankcrm/
   ```

2. **Log into Dolibarr** as a superadministrator

3. **Enable the module** via Home > Setup > Modules > search for "FoodbankCRM"

4. **Run the database install script** -- The tables should be created during module activation. If tables are missing, run the script manually:
   ```sql
   source htdocs/custom/foodbankcrm/core/script/install.sql
   ```

5. **Configure Paystack** -- Go to the module's Settings page and enter your Paystack API keys (see [Configuration](#configuration))

6. **Create Subscription Tiers** -- Navigate to Foodbank CRM > Subscription Tiers and create at least one tier before subscribers can register

### Docker Setup

If running Dolibarr in Docker, mount the module directory:

```yaml
volumes:
  - ./custom_modules/foodbankcrm:/var/www/html/htdocs/custom/foodbankcrm
```

---

## Configuration

### Paystack Payment Gateway

1. Navigate to **Foodbank CRM > Settings** (or Home > Setup > Modules > FoodbankCRM > Settings)
2. Click **Modify**
3. Enter your Paystack API keys:
   - **Paystack Public Key** -- Starts with `pk_test_` (test) or `pk_live_` (production)
   - **Paystack Secret Key** -- Starts with `sk_test_` (test) or `sk_live_` (production)
4. Click **Save**

Keys are stored securely in Dolibarr's `llx_const` table and accessed via `getDolGlobalString('FOODBANK_PAYSTACK_PUBLIC_KEY')` throughout the codebase.

### Login Page Customization

The module automatically injects registration buttons on the Dolibarr login page via the `MAIN_LOGIN_INSTRUCTIONS` constant. This is configured in the module descriptor and takes effect when the module is activated.

---

## Database Schema

FoodbankCRM uses 17 custom database tables (all prefixed with `llx_foodbank_`):

### Core Entity Tables

| Table | Description |
|-------|-------------|
| `foodbank_beneficiaries` | Subscriber profiles with demographics and subscription details |
| `foodbank_vendors` | Vendor/supplier profiles with business and banking information |
| `foodbank_warehouses` | Storage locations with capacity tracking |

### Inventory & Product Tables

| Table | Description |
|-------|-------------|
| `foodbank_donations` | Inventory items received from vendors (stock tracking with allocation) |
| `foodbank_packages` | Food bundles available for purchase by subscribers |
| `foodbank_package_items` | Individual items within a package (with unit pricing) |
| `foodbank_vendor_products` | Catalog of products a vendor can supply |

### Order & Distribution Tables

| Table | Description |
|-------|-------------|
| `foodbank_distributions` | Orders placed by subscribers (with payment tracking) |
| `foodbank_distribution_lines` | Individual line items in an order |
| `foodbank_cart` | Shopping cart (database-backed, per subscriber) |

### Financial Tables

| Table | Description |
|-------|-------------|
| `foodbank_subscription_tiers` | Subscription plans with pricing and duration |
| `foodbank_payments` | Payment records for both orders and subscriptions |

### System Tables

| Table | Description |
|-------|-------------|
| `foodbank_user_vendor` | Links Dolibarr users to vendor profiles |
| `foodbank_user_beneficiary` | Links Dolibarr users to subscriber profiles |
| `foodbank_support` | Vendor helpdesk/support tickets |
| `foodbank_rate_limit` | Brute-force protection for registration and OTP verification |
| `foodbank_email_verification` | Temporary OTP codes for email verification |

The complete schema is defined in [`core/script/install.sql`](core/script/install.sql).

---

## Directory Structure

```
foodbankcrm/
|-- admin/                          # Dolibarr admin pages
|   |-- setup.php                   #   Paystack configuration page
|   +-- about.php                   #   Module about page
|
|-- class/                          # ORM / Business logic classes
|   |-- beneficiary.class.php       #   Subscriber CRUD
|   |-- vendor.class.php            #   Vendor CRUD
|   |-- warehouse.class.php         #   Warehouse CRUD
|   |-- donation.class.php          #   Inventory/donation CRUD
|   |-- distribution.class.php      #   Order/distribution CRUD
|   |-- distributionline.class.php  #   Order line items + stock allocation
|   |-- package.class.php           #   Food package CRUD
|   |-- packageitem.class.php       #   Package items CRUD
|   |-- vendorproduct.class.php     #   Vendor product catalog CRUD
|   +-- permissions.class.php       #   Role detection (isAdmin/isVendor/isBeneficiary)
|
|-- core/
|   |-- modules/
|   |   +-- modFoodbankcrm.class.php  # Module descriptor (permissions, menus, constants)
|   |
|   |-- pages/                        # All user-facing pages (~78 files)
|   |   |-- dashboard_admin.php       #   Admin dashboard
|   |   |-- dashboard_vendor.php      #   Vendor dashboard
|   |   |-- dashboard_beneficiary.php #   Subscriber dashboard
|   |   |-- register.php              #   Public subscriber registration
|   |   |-- register_vendor.php       #   Public vendor registration
|   |   |-- verify_otp.php            #   OTP verification (shared)
|   |   |-- product_catalog.php       #   Browse packages (subscriber)
|   |   |-- view_cart.php             #   Shopping cart
|   |   |-- checkout.php              #   Order checkout
|   |   |-- process_order_payment.php     #   Paystack payment initiation (orders)
|   |   |-- process_subscription_payment.php  #   Paystack payment (subscriptions)
|   |   |-- payment_callback.php          #   Paystack callback (orders)
|   |   |-- subscription_payment_callback.php #   Paystack callback (subscriptions)
|   |   |-- activate_subscription.php     #   Subscription activation API
|   |   |-- update_payment_status.php     #   Payment verification API
|   |   +-- ...                           #   CRUD pages for all entities
|   |
|   |-- script/
|   |   |-- install.sql               #   Complete database schema (17 tables)
|   |   +-- assign_users_to_groups.php #   Utility: bulk-assign users to groups
|   |
|   |-- triggers/
|   |   |-- interface_99_..._Redirect.class.php       # Auto-redirect on login
|   |   +-- interface_99_..._GroupAssignment.class.php # Auto-assign user groups
|   |
|   +-- hooks/
|       +-- foodbankcrm_hooks.class.php  # Dolibarr hook integration
|
|-- lib/
|   +-- foodbankcrm.lib.php           # Admin page header helper
|
|-- lang(s)/en_US/
|   +-- foodbankcrm.lang              # Translation strings
|
|-- sql/
|   +-- dolibarr_allversions.sql      # Upgrade migration script (reserved)
|
|-- index.php                         # Public gateway / smart router
+-- core/modules/modFoodbankcrm.class.php  # Module descriptor
```

---

## Payment Integration

FoodbankCRM integrates with [Paystack](https://paystack.com/) for online payments. Two payment flows are supported:

### Order Payments
1. Subscriber places order at checkout (selects "Pay Now")
2. `process_order_payment.php` renders Paystack inline payment widget
3. On success, Paystack redirects to `payment_callback.php`
4. Callback verifies payment with Paystack API using secret key
5. Order status updated to "Paid", distribution confirmed

### Subscription Payments
1. Subscriber selects a subscription tier
2. `process_subscription_payment.php` renders Paystack widget
3. On success, redirects to `subscription_payment_callback.php`
4. Callback verifies payment, activates subscription with correct duration
5. `activate_subscription.php` provides an AJAX API endpoint for inline activation

### Amount Handling
- Paystack amounts are in **Kobo** (1 Naira = 100 Kobo)
- The module converts prices: `amount * 100` when sending to Paystack
- Verification checks: `paystack_amount / 100` matches expected price (with 1 Naira tolerance)

---

## Security

The module implements multiple layers of security:

### Authentication & Authorization
- **Role-based access control** via `FoodbankPermissions` class on every protected page
- **Admin pages** check `$user->admin` or `FoodbankPermissions::isAdmin()`
- **Vendor pages** verify vendor profile ownership (`fk_user` match)
- **Subscriber pages** verify subscriber profile and active subscription
- **Ownership checks** on payment and order pages (subscribers can only access their own orders)

### CSRF Protection
- All destructive actions (delete, unlink, toggle) require Dolibarr CSRF tokens
- Token validation: `$_SESSION['newtoken']` checked against `newToken()` on links

### Input Handling
- All user inputs processed through Dolibarr's `GETPOST()` function with type filters
- SQL values escaped via `$db->escape()`
- HTML output escaped via `dol_escape_htmltag()` and `htmlspecialchars()`

### XSS Prevention
- All `$_SERVER['PHP_SELF']` references replaced with `basename(__FILE__)`
- User-supplied content escaped before rendering

### Brute-Force Protection
- **Registration**: Rate limited to 10 attempts per IP per 10 minutes
- **OTP Verification**: Locked after 5 failed attempts for 15 minutes (per email + per IP)
- **OTP Resend**: Limited to 3 resends per 10 minutes per email
- Uses `foodbank_rate_limit` table with automatic cleanup of expired entries

### OTP Security
- Codes generated with `random_int()` (cryptographically secure)
- 5-minute expiry on verification codes
- Session regeneration (`session_regenerate_id(true)`) after successful verification
- Cleanup of OTP records and failed attempt counters on success

### Payment Security
- Paystack API keys stored in Dolibarr's config system (not hardcoded)
- Server-side payment verification via Paystack API before activating any subscription or confirming any order
- Amount verification: paid amount compared against expected tier/order price
- Duplicate payment prevention via reference uniqueness check
- Internal DB error messages logged via `dol_syslog()` instead of exposed to users

---

## License

### Code
GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation
All texts and readmes are licensed under GFDL.
