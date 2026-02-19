# FoodbankCRM Development Log

Comprehensive record of all development, security audits, bug fixes, and improvements made to the FoodbankCRM Dolibarr module.

---

## Version History

| Version | Date | Summary |
|---------|------|---------|
| 1.0 | 2025 | Initial module -- CRUD for beneficiaries, vendors, donations |
| 1.1 | 2025 | Added dashboards for admin, vendor, subscriber roles |
| 1.2 | 2025 | Subscriber dashboard fixes, payment integration (Paystack) |
| 1.3 | 2025 | Self-registration pages for vendors and subscribers with OTP verification |
| 1.4 | 2026-02 | Full security audit, bug fixes, and codebase cleanup (details below) |

---

## v1.4 -- Security Audit & Codebase Overhaul (February 2026)

A comprehensive security audit was performed across all ~78 PHP files in the module. The audit was organized into phases by priority, with every identified issue tracked and resolved.

### Phase 1: Critical Security Fixes

#### 1.1 Dangerous File Removal
**Files deleted:**
- `test_paystack_key.php` -- Exposed Paystack secret key in plain HTML for debugging. Allowed any visitor to see the live API key.
- `test_cart.php` (if present) -- Debug/test file with no access control.

**Impact:** Eliminated direct exposure of payment credentials.

#### 1.2 Hardcoded Paystack Keys Centralized
**Problem:** Paystack API keys (`sk_test_...`, `pk_test_...`) were hardcoded as string literals in 6 different PHP files. Changing keys required editing multiple files, and the secret key was visible in source code.

**Solution:**
- Added `FOODBANK_PAYSTACK_PUBLIC_KEY` and `FOODBANK_PAYSTACK_SECRET_KEY` constants to the module descriptor (`modFoodbankcrm.class.php`)
- Rebuilt `admin/setup.php` with a clean Paystack configuration UI using Dolibarr's `FormSetup` class
- Updated all 6 files to use `getDolGlobalString()` with empty-check fallback:

**Files modified:**
| File | Key Type |
|------|----------|
| `activate_subscription.php` | Secret key |
| `payment_callback.php` | Secret key |
| `update_payment_status.php` | Secret key |
| `subscription_payment_callback.php` | Secret key |
| `process_order_payment.php` | Public key |
| `process_subscription_payment.php` | Public key |

#### 1.3 Payment Endpoint Security (`update_payment_status.php`)
**Problem:** No authentication check -- any anonymous user could call this endpoint to mark orders as paid.

**Fix:** Added full security:
- Authentication check (`$user->id` required)
- Beneficiary permission check via `FoodbankPermissions::isBeneficiary()`
- Ownership verification (order must belong to the logged-in subscriber)
- Server-side Paystack verification with amount matching

#### 1.4 Subscription Activation Security (`activate_subscription.php`)
**Problem:** No authentication, no ownership check, no amount verification. Anyone could activate any subscription by sending a crafted POST request.

**Fix:** Complete rewrite with 6 security layers:
1. Authentication check
2. Required parameter validation
3. Ownership check (subscriber must belong to logged-in user)
4. Paystack server-side verification
5. Tier lookup and amount matching (with 1 Naira tolerance)
6. Duplicate payment prevention

---

### Phase 2: Access Control

#### 2.1 Admin Page Protection (27 pages)
**Problem:** 27 admin-facing pages had no permission checks. Any logged-in user (including basic vendors/subscribers) could access admin functions like managing vendors, editing donations, deleting records, etc.

**Fix:** Added `FoodbankPermissions::isAdmin($user)` check with `accessforbidden()` to every admin page:

**Files fixed:**
- `beneficiaries.php`, `create_beneficiary.php`, `edit_beneficiary.php`, `view_beneficiary.php`, `delete_beneficiary.php`
- `vendors.php`, `create_vendor.php`, `edit_vendor.php`, `view_vendor.php`, `delete_vendor.php`
- `donations.php`, `create_donation.php`, `edit_donation.php`, `view_donation.php`, `delete_donation.php`
- `warehouses.php`, `create_warehouse.php`, `edit_warehouse.php`, `delete_warehouse.php`
- `distributions.php`, `create_distribution.php`, `edit_distribution.php`, `view_distribution.php`, `delete_distribution.php`
- `packages.php`, `create_package.php`, `edit_package.php`, `view_package.php`, `delete_package.php`
- `subscription_tiers.php`, `create_subscription_tier.php`, `edit_subscription_tier.php`, `delete_subscription_tier.php`
- `admin_orders.php`, `admin_subscription_tiers.php`
- `user_management.php`, `reports.php`, `dashboard_admin.php`

#### 2.2 Ownership Checks on Payment Pages
**Problem:** `process_order_payment.php` and `order_success.php` fetched orders without verifying they belonged to the logged-in user. A subscriber could view/pay for another subscriber's orders by changing the `order_id` parameter.

**Fix:**
- Added `FoodbankPermissions::isBeneficiary()` check
- Fetched subscriber ID from `foodbank_beneficiaries` via `fk_user`
- Added `AND fk_beneficiary = $subscriber_id` to all order queries

**Files modified:**
- `process_order_payment.php`
- `order_success.php`
- `order_confirmation.php` (already had ownership check -- verified)

---

### Phase 3: CSRF Protection

#### 3.1 Destructive GET Actions
**Problem:** 7 destructive operations (delete, unlink, toggle) used GET requests without CSRF token validation. An attacker could craft a link that, when clicked by an admin, would delete records or unlink users.

**Fix:** Added Dolibarr CSRF token validation (`newToken()` / `$_SESSION['newtoken']`) to all destructive GET handlers, and appended `&token='.newToken().'` to all corresponding links.

**Files and actions fixed:**

| File | Action | What It Does |
|------|--------|-------------|
| `edit_package.php` | `del_item` | Deletes a package item |
| `edit_distribution.php` | `del_line` | Deletes a distribution line (restores stock) |
| `view_cart.php` | `remove` | Removes item from subscriber's cart |
| `subscription_tiers.php` | `delete` | Deletes a subscription tier |
| `subscription_tiers.php` | `toggle` | Activates/deactivates a tier |
| `user_management.php` | `unlink_vendor` | Removes user-vendor link |
| `user_management.php` | `unlink_beneficiary` | Removes user-beneficiary link |

---

### Phase 4: XSS Prevention

#### 4.1 `$_SERVER['PHP_SELF']` Replacement
**Problem:** 44 occurrences of `$_SERVER['PHP_SELF']` across 35+ files. This superglobal is vulnerable to reflected XSS when used in HTML output (e.g., `<form action="<?php echo $_SERVER['PHP_SELF']; ?>">`).

**Fix:** Replaced all instances with `basename(__FILE__)` which returns only the filename (e.g., `beneficiaries.php`), preventing path injection.

**Files modified:** All PHP files in `core/pages/` and `admin/` that contained `$_SERVER['PHP_SELF']` (35+ files).

**Safe usages preserved:**
- `check_subscription_status.php` -- Uses `basename($_SERVER['PHP_SELF'])` (already safe)
- `redirect_dashboard.php` -- Uses `basename($_SERVER['PHP_SELF'])` (already safe)

---

### Phase 5: OTP & Registration Security

#### 5.1 OTP Verification Hardening (`verify_otp.php`)
**Problem:** No brute-force protection on OTP verification. An attacker could script 999,999 attempts to guess a 6-digit code. No rate limiting on resend. No session regeneration after login.

**Fix:** Complete rewrite with:
- **Brute-force lockout**: 5 failed attempts per email/IP locks verification for 15 minutes
- **Resend rate limiting**: Maximum 3 resends per email per 10 minutes
- **Session security**: `session_regenerate_id(true)` after successful OTP to prevent session fixation
- **Cleanup**: Failed attempt records and OTP codes deleted on successful verification
- **Failed attempt tracking**: Both email and IP tracked separately for defense in depth

#### 5.2 Cryptographic OTP Generation
**Problem:** Both `register.php` and `register_vendor.php` used `rand(100000, 999999)` to generate OTP codes. PHP's `rand()` is not cryptographically secure and produces predictable sequences.

**Fix:** Replaced with `random_int(100000, 999999)` (CSPRNG) in both files.

**Files modified:**
- `register.php`
- `register_vendor.php`

#### 5.3 Error Message Sanitization (`register_vendor.php`)
**Problem:** Raw database error messages (`$db->lasterror()`) and internal user creation errors (`$newuser->error`) were displayed directly to the user. This leaks implementation details (table names, column names, SQL syntax).

**Fix:** Replaced with:
- `dol_syslog("Vendor Registration DB Error: " . $db->lasterror(), LOG_ERR)` for logging
- Generic user-facing message: "We encountered a technical error. Please contact support."

---

### Phase 6: Functional Bug Fixes

#### 6.1 Dual Cart System
**Problem:** The cart system used `$_SESSION['cart']` as the data source but also wrote to the `foodbank_cart` database table. On page reload, session data could be lost while DB records persisted, causing ghost items and inconsistent state.

**Fix:** Rewrote `view_cart.php` and `checkout.php` to use the database (`foodbank_cart` table) as the single source of truth. Session-based cart logic removed. Cart now reads from `SELECT ... FROM foodbank_cart WHERE fk_subscriber = ...`.

#### 6.2 Price Calculation
**Problem:** Package prices were stored as a static `unit_price` in the cart, but the actual price should be the sum of `quantity * unit_price` for all items in the package.

**Fix:** Cart and checkout queries now JOIN `foodbank_package_items` and calculate `SUM(pi.quantity * pi.unit_price)` as the dynamic price.

#### 6.3 Dashboard Admin Query Fix
**Problem:** Several dashboard statistics queries referenced incorrect column names or missing tables.

**Fix:** Corrected SQL queries for revenue totals, active subscription counts, and pending inventory counts.

---

### Phase 7: Code Cleanup

#### 7.1 Module Descriptor Cleanup
The `modFoodbankcrm.class.php` file was already clean with properly defined permissions, menus, and constants. No MyObject boilerplate remained in the module descriptor.

#### 7.2 Admin Setup Page Cleanup (`admin/setup.php`)
**Problem:** ~370 lines of unused Dolibarr module generator boilerplate for numbering modules, document template generators, and `MyObject` references.

**Fix:** Removed:
- `$type = 'myobject'` and unused variables (`$value`, `$label`, `$scandir`, `$modulepart`, `$dirmodels`)
- 6 unused action handlers (`updateMask`, `specimen`, `setmod`, `set`, `del`, `setdoc`, `unsetdoc`)
- Entire `$myTmpObjects` array and foreach loop (numbering model UI + document template UI)
- Commented-out `myclass.class.php` require

**Result:** File reduced from 537 lines to 167 lines. Clean Paystack configuration page only.

#### 7.3 Install SQL Schema Sync (`core/script/install.sql`)
**Problem:** The install script defined only 5 tables with incomplete columns. The actual codebase uses 17 tables with many more columns than were defined.

**Fix:** Complete rewrite of `install.sql` with all 17 tables and every column referenced in the codebase. Each column name was cross-referenced against class files, INSERT/UPDATE/SELECT statements, and registration pages.

**Tables added (12 new):**
- `foodbank_packages`
- `foodbank_package_items`
- `foodbank_distribution_lines`
- `foodbank_cart`
- `foodbank_subscription_tiers`
- `foodbank_payments`
- `foodbank_vendor_products`
- `foodbank_user_vendor`
- `foodbank_user_beneficiary`
- `foodbank_support`
- `foodbank_rate_limit`
- `foodbank_email_verification`

**Tables updated (4 existing):**
- `foodbank_beneficiaries` -- Added 12 columns (fk_user, subscription fields, demographics)
- `foodbank_vendors` -- Added 13 columns (fk_user, business details, banking info)
- `foodbank_donations` -- Added 7 columns (product_name, allocations, pricing, vendor_product)
- `foodbank_distributions` -- Added 7 columns (fk_package, status, payment fields)

---

## Summary of All Files Modified

### Security Fixes
| Category | Files Modified | Count |
|----------|---------------|-------|
| Hardcoded Paystack keys | activate_subscription, payment_callback, update_payment_status, subscription_payment_callback, process_order_payment, process_subscription_payment | 6 |
| Access control (admin check) | All admin CRUD pages | 27 |
| CSRF tokens | edit_package, edit_distribution, view_cart, subscription_tiers, user_management | 5 |
| `$_SERVER['PHP_SELF']` XSS | All pages in core/pages/ and admin/ | 35+ |
| OTP security | verify_otp, register, register_vendor | 3 |
| Ownership checks | process_order_payment, order_success, update_payment_status, activate_subscription | 4 |
| Error message sanitization | register_vendor | 1 |

### Functional Fixes
| Fix | Files Modified |
|-----|---------------|
| Dual cart (session vs DB) | view_cart, checkout, add_to_cart |
| Price calculation | checkout, view_cart |
| Dashboard queries | dashboard_admin |

### Cleanup
| Change | Files Modified |
|--------|---------------|
| Admin setup boilerplate removal | admin/setup.php |
| Module descriptor Paystack constants | modFoodbankcrm.class.php |
| Install SQL schema sync | core/script/install.sql |
| Dangerous file deletion | test_paystack_key.php |

---

## Architecture Decisions

### Why `FoodbankPermissions` instead of Dolibarr's built-in rights?
Dolibarr's permission system (`$user->rights->modulename->permission`) requires permissions to be explicitly assigned to user groups. FoodbankCRM's self-registration flow creates users dynamically, and checking for the existence of a vendor/beneficiary record linked to the user is more reliable than depending on group membership being correctly configured.

### Why database-backed cart instead of session?
Sessions are volatile -- they expire, can be cleared by browser restarts, and don't persist across devices. A database-backed cart (`foodbank_cart` table) ensures cart contents are always available and consistent, and allows for admin visibility into subscriber shopping behavior.

### Why `basename(__FILE__)` instead of `$_SERVER['PHP_SELF']`?
`$_SERVER['PHP_SELF']` reflects the URL path as provided by the user's browser, making it vulnerable to reflected XSS (e.g., `/page.php/"><script>alert(1)</script>`). `basename(__FILE__)` returns only the filename from the filesystem, which cannot be manipulated by user input.

### Why centralized Paystack keys?
Hardcoding API keys in individual files creates maintenance burden (changing keys requires editing 6 files) and security risk (keys visible in source code, easy to miss during rotation). Dolibarr's `llx_const` table provides a single, admin-configurable storage location with no code changes needed for key rotation.

---

## Known Limitations & Future Work

- **Email delivery**: OTP emails rely on Dolibarr's `CMailFile` class. A working SMTP configuration is required in Dolibarr's email settings.
- **Paystack only**: Currently only Paystack is supported for online payments. The architecture could be extended to support additional gateways.
- **No password reset flow**: Subscribers and vendors must contact admin for password resets. A self-service forgot-password flow could be added.
- **Support ticket system**: Basic implementation (single reply per ticket). Could be extended to support threaded conversations and attachments.
- **No automated subscription expiry notifications**: The `check_subscription_status.php` include expires subscriptions on page load, but doesn't proactively notify subscribers before expiry.
- **Translation**: Only English (`en_US`) translations are provided. The lang file contains some placeholder strings from the module generator that could be cleaned up.
