# FoodbankCRM -- Pre-Launch Testing Guide

Complete testing checklist for all dashboards, flows, and integrations before production deployment.

**Testing Order:** Follow the sections in order -- each section builds on data created in the previous one.

---

## 0. Environment Setup

### 0.1 Pre-Testing Checklist

- [ ] Dolibarr is running and accessible
- [ ] FoodbankCRM module is enabled (Home > Setup > Modules)
- [ ] All 17 database tables exist (run `core/script/install.sql` if needed)
- [ ] SMTP/email is configured in Dolibarr (Home > Setup > Emails) -- OTP codes will be sent
- [ ] Paystack test keys are configured (Foodbank CRM > Settings)
- [ ] At least one Dolibarr superadmin account exists

### 0.2 Test Accounts to Create

You will create these during testing:

| Role | Username | Email | Purpose |
|------|----------|-------|---------|
| Admin | (your existing superadmin) | -- | Already exists |
| Subscriber 1 | `testsubscriber1` | real email you control | Primary subscriber testing |
| Subscriber 2 | `testsubscriber2` | another email | Ownership/isolation testing |
| Vendor 1 | `testvendor1` | real email you control | Primary vendor testing |
| Vendor 2 | `testvendor2` | another email | Vendor approval testing |

### 0.3 Browser Setup

- Open browser DevTools (F12) > Console tab -- watch for JS errors throughout testing
- Keep a second browser (or incognito window) open for testing different roles simultaneously
- Test on both desktop and mobile viewport (responsive check)

---

## 1. Public Gateway & Landing Page

### 1.1 Welcome Page (`index.php`)

- [ ] Visit `https://yourdomain.com/custom/foodbankcrm/index.php` while logged out
- [ ] Verify the welcome page renders with three buttons: Login, Register as User, Become a Vendor
- [ ] Click "Login to Account" -- should redirect to Dolibarr login page with `backtopage` parameter
- [ ] Click "Register as User" -- should go to `register.php`
- [ ] Click "Become a Vendor Partner" -- should go to `register_vendor.php`

### 1.2 Login Page Buttons

- [ ] Visit Dolibarr login page (`/index.php`)
- [ ] Verify the registration buttons appear below the login form (injected via `MAIN_LOGIN_INSTRUCTIONS`)
- [ ] Both links should work and go to the correct registration pages

---

## 2. Subscriber Registration Flow

### 2.1 Prerequisites

- [ ] At least one subscription tier exists -- if not, log in as admin and create one first (Foodbank CRM > Subscription Tiers > Create)

### 2.2 Registration Page (`register.php`)

- [ ] Page loads without errors
- [ ] All form fields render: First Name, Last Name, Email, Phone, Username, Password, Subscription Plan
- [ ] Subscription tier dropdown is populated with active tiers
- [ ] **Validation -- empty fields:** Submit with empty required fields -- should show error
- [ ] **Validation -- invalid username:** Enter `test user!@#` -- should show "invalid characters" error
- [ ] **Validation -- duplicate username:** Register, then try same username again -- should show "already taken"
- [ ] **Validation -- duplicate email:** Try same email twice -- should show "already registered"
- [ ] **Password strength meter:** Type passwords of varying strength -- meter should update (Weak/Medium/Strong)
- [ ] **Successful registration:** Fill all fields correctly and submit
- [ ] Should redirect to `verify_otp.php?email=...`
- [ ] Check your email -- OTP code should arrive

### 2.3 OTP Verification (`verify_otp.php`)

- [ ] Page shows "We sent a 6-digit code to [your email]"
- [ ] **Wrong code:** Enter `000000` -- should show "Invalid verification code"
- [ ] **Correct code:** Enter the code from your email -- should verify and redirect
- [ ] **Redirect after verification:** Should go to `dashboard_beneficiary.php` (subscriber dashboard)
- [ ] **Code expiry:** Wait 5+ minutes, try to use an old code -- should show "Code expired"
- [ ] **Resend:** Click "Request a new one" -- new code should arrive, old code should stop working
- [ ] **Brute-force protection:** Enter wrong codes 5 times rapidly -- should show lockout message after 5 attempts

### 2.4 Rate Limiting

- [ ] Try registering 10+ times rapidly from same IP -- should show "Too many attempts" after 10

---

## 3. Vendor Registration Flow

### 3.1 Registration Page (`register_vendor.php`)

- [ ] Page loads with 4 sections: Business Info, Contact Details, Banking Info, Account Security
- [ ] **Category dropdown:** Should list Grains, Fresh Produce, Proteins, Packaged, Logistics
- [ ] Fill in all fields and submit
- [ ] Should redirect to `verify_otp.php`
- [ ] Complete OTP verification
- [ ] After verification, should redirect to `dashboard_vendor.php`

### 3.2 Vendor Pending State

- [ ] Vendor dashboard should show "Pending Approval" status
- [ ] Vendor should have limited access while pending (verify their dashboard shows pending notice)

---

## 4. Admin Dashboard Testing

Log in as the Dolibarr superadmin for all tests in this section.

### 4.1 Dashboard Overview (`dashboard_admin.php`)

- [ ] Dashboard loads without errors
- [ ] **Stats cards:** Revenue, Active Subscriptions, Pending Inventory, Open Tickets -- all render with numbers
- [ ] **Recent inventory table:** Shows recent donations (may be empty initially)
- [ ] Left sidebar shows all admin menu items: Admin Overview, Subscribers, Vendors, Vendor Support, Inventory Logs, Warehouses, Packages, Distributions, Subscription Tiers, User Management, Settings

### 4.2 Subscriber Management

#### List Page (`beneficiaries.php`)
- [ ] Page loads, shows table of subscribers
- [ ] The subscriber you created in Section 2 should appear
- [ ] Search/filter works if available

#### View Page (`view_beneficiary.php`)
- [ ] Click on a subscriber -- detail page loads
- [ ] All fields display correctly (name, email, phone, subscription info)

#### Edit Page (`edit_beneficiary.php`)
- [ ] Click Edit -- form loads with pre-populated data
- [ ] Change a field (e.g., phone number) and save
- [ ] Verify the change persists on the view page
- [ ] **Subscription assignment:** Try assigning a subscription tier and setting status to "Active"

#### Create Page (`create_beneficiary.php`)
- [ ] Create a new subscriber manually via admin form
- [ ] All fields save correctly
- [ ] New subscriber appears in the list

#### Delete Page (`delete_beneficiary.php`)
- [ ] Delete the manually-created test subscriber
- [ ] Confirm deletion prompt appears
- [ ] Subscriber is removed from list after confirmation

### 4.3 Vendor Management

#### List Page (`vendors.php`)
- [ ] Shows registered vendors
- [ ] The vendor from Section 3 should appear with "Pending" status

#### Edit/Approve (`edit_vendor.php`)
- [ ] Open the pending vendor
- [ ] **Approve vendor:** Change status from "Pending" to "Active"
- [ ] Save -- vendor should now be approved
- [ ] Log in as the vendor in another browser -- dashboard should no longer show "Pending"

#### Create Vendor Manually (`create_vendor.php`)
- [ ] Create a vendor via admin form
- [ ] All fields (business name, RC number, tax ID, banking, category) save correctly

#### Delete Vendor (`delete_vendor.php`)
- [ ] Delete the manually-created test vendor
- [ ] Verify deletion works

### 4.4 Warehouse Management

#### Full CRUD Cycle
- [ ] **Create:** Create warehouse "Main Warehouse" with address and capacity 1000
- [ ] **List:** Verify it appears on `warehouses.php`
- [ ] **Edit:** Change capacity to 2000, save, verify
- [ ] **Delete:** Delete it (create a second one first to test with later)

Create at least **1 warehouse** and leave it for later testing.

### 4.5 Inventory (Donations) Management

#### Create Donation (`create_donation.php`)
- [ ] **Vendor dropdown:** Shows approved vendors
- [ ] **Warehouse dropdown:** Shows warehouses you created
- [ ] Create a donation: Product Name = "Rice", Quantity = 100, Unit = "bags", Status = "Received"
- [ ] Set a unit price (e.g., 5000)
- [ ] Save successfully

#### List Page (`donations.php`)
- [ ] Donation appears in list with correct vendor name and status
- [ ] Filter by vendor works (if available)

#### Edit Donation (`edit_donation.php`)
- [ ] Edit the donation, change quantity to 200
- [ ] Verify change saves

Create at least **3-5 donations** with different products for package testing.

### 4.6 Package Management

#### Create Package (`create_package.php`)
- [ ] Create package "Basic Food Bundle"
- [ ] Status defaults to "Active"

#### Edit Package / Add Items (`edit_package.php`)
- [ ] Open the package for editing
- [ ] **Add item:** Product Name = "Rice", Quantity = 10, Unit = "bags", Unit Price = 5000
- [ ] **Add item:** Product Name = "Beans", Quantity = 5, Unit = "bags", Unit Price = 3000
- [ ] Both items appear in the items table
- [ ] **Package total** should calculate as: (10 x 5000) + (5 x 3000) = 65,000
- [ ] **Delete item:** Click delete on one item -- should be removed (CSRF token required)
- [ ] Re-add the item

#### List Page (`packages.php`)
- [ ] Package appears with item count and usage count

Create at least **2 packages** with items and prices for subscriber testing.

### 4.7 Subscription Tier Management

#### Create Tier (`create_subscription_tier.php`)
- [ ] Create tier: Name = "Annual Basic", Type = "Annual", Duration = 12 months, Price = 50000
- [ ] Create tier: Name = "Guest Trial", Type = "Guest", Duration = 1 month, Price = 5000

#### List/Toggle (`subscription_tiers.php`)
- [ ] Both tiers appear in list
- [ ] **Toggle:** Click Deactivate on one -- status changes to inactive
- [ ] **Toggle back:** Click Activate -- status returns to active
- [ ] Both actions require CSRF token (verify no "Security check failed" errors)

#### Edit Tier (`edit_subscription_tier.php`)
- [ ] Edit the Guest tier, change price to 7500
- [ ] Verify change saves

#### Delete Tier (`delete_subscription_tier.php`)
- [ ] Try deleting a tier that has subscribers -- should warn "X subscribers are on this tier"
- [ ] Delete a tier with no subscribers -- should succeed

### 4.8 Distribution/Order Management

#### List Page (`distributions.php`)
- [ ] Shows all orders (may be empty until subscriber places one)
- [ ] Status filter/tabs work

#### Create Distribution Manually (`create_distribution.php`)
- [ ] Beneficiary dropdown populated
- [ ] Warehouse dropdown populated
- [ ] Package dropdown shows active packages
- [ ] Create a distribution and save

### 4.9 User Management (`user_management.php`)

#### Link User to Vendor
- [ ] Select a Dolibarr user from dropdown
- [ ] Select a vendor from dropdown
- [ ] Click "Link" -- creates a user-vendor mapping
- [ ] Link appears in the vendor links table

#### Link User to Beneficiary
- [ ] Select a user and a beneficiary
- [ ] Click "Link"
- [ ] Link appears in the beneficiary links table

#### Unlink
- [ ] Click "Unlink" on a link -- should prompt confirmation
- [ ] Confirm -- link is removed
- [ ] CSRF token required (verify no security errors)

### 4.10 Settings Page (`admin/setup.php`)

- [ ] Page loads showing Paystack Public Key and Secret Key
- [ ] Click "Modify" -- fields become editable
- [ ] Enter test keys, click Save
- [ ] Values persist after page reload
- [ ] Keys show correctly in read-only mode

### 4.11 Vendor Support (`vendor_support.php`)

- [ ] Page loads as admin (shows all tickets)
- [ ] If tickets exist from vendor testing, they display in the list
- [ ] Admin can reply to a ticket

---

## 5. Subscriber Dashboard Testing

Log in as `testsubscriber1` for all tests in this section.

### 5.1 Subscription Status

- [ ] If subscription is "Pending" -- subscriber should be redirected to subscription payment/renewal page
- [ ] If subscription is "Active" -- dashboard should load normally

### 5.2 Subscription Payment Flow

(Only needed if subscriber has "Pending" status)

- [ ] `renew_subscription.php` loads with available tiers and pricing
- [ ] Select a tier, click Pay
- [ ] `process_subscription_payment.php` renders Paystack payment widget
- [ ] **Paystack test payment:** Use Paystack test card:
  - Card: `4084 0840 8408 4081`
  - Expiry: Any future date
  - CVV: `408`
  - OTP: `123456`
- [ ] After payment, redirects to callback page
- [ ] Subscription status changes to "Active"
- [ ] Payment record created in `foodbank_payments` table
- [ ] Dashboard now loads normally

### 5.3 Dashboard Overview (`dashboard_beneficiary.php`)

- [ ] Dashboard loads without errors or raw HTML/PHP output
- [ ] Shows subscription status (Active), expiry date
- [ ] Shows order count
- [ ] Quick action links work (Browse Packages, My Orders, My Profile)
- [ ] Dolibarr top bar/sidebar is hidden (custom fullscreen UI)

### 5.4 Product Catalog (`product_catalog.php`)

- [ ] Lists all active packages with prices
- [ ] Each package shows item details and total price
- [ ] "Add to Cart" button visible on each package
- [ ] Click "Add to Cart" on a package -- success message appears
- [ ] Add the same package again -- quantity should increment (not duplicate)
- [ ] Add a different package -- both should appear in cart

### 5.5 Shopping Cart (`view_cart.php`)

- [ ] Cart page shows all added items
- [ ] Quantities and prices display correctly
- [ ] **Grand total** calculates correctly
- [ ] **Remove item:** Click remove -- item disappears (CSRF token check, no errors)
- [ ] **Empty cart:** Remove all items -- cart shows "empty" state
- [ ] Re-add items for checkout testing

### 5.6 Checkout Flow (`checkout.php`)

- [ ] Checkout page loads with cart summary
- [ ] **Delivery address** field is required
- [ ] **Payment method** options: "Pay Now" and "Pay on Delivery"

#### Pay on Delivery
- [ ] Select "Pay on Delivery", fill address, submit
- [ ] Order created with status "Pending", payment status "Pay on Delivery"
- [ ] Redirects to `order_confirmation.php` -- shows order details
- [ ] Cart is cleared after checkout
- [ ] Order appears in "My Orders"

#### Pay Now (Paystack)
- [ ] Add items to cart again
- [ ] Select "Pay Now", fill address, submit
- [ ] Redirects to `process_order_payment.php` -- Paystack widget appears
- [ ] Complete Paystack test payment (use test card above)
- [ ] Redirects to callback -- order confirmed with "Paid" status
- [ ] Order appears in "My Orders" with payment confirmed

### 5.7 My Orders (`my_orders.php`)

- [ ] Lists all orders placed by this subscriber
- [ ] Shows order ref, date, total, payment status, delivery status
- [ ] Click on an order -- `view_order.php` shows full details with line items
- [ ] **Ownership check:** Copy an order URL, log in as `testsubscriber2` -- should get access denied

### 5.8 Order Success Page (`order_success.php`)

- [ ] After a successful order, this page shows a confirmation with order details
- [ ] **Ownership check:** Try accessing with a different subscriber's order_id -- should fail

### 5.9 My Profile (`my_profile.php`)

- [ ] Profile page loads with current details
- [ ] Edit phone number, save -- change persists
- [ ] Subscription details display correctly

### 5.10 Password Change (`subscriber_password.php`)

- [ ] Password change form loads
- [ ] Change password, log out, log back in with new password -- works
- [ ] Change back to original for continued testing

### 5.11 Subscription Renewal (`renew_subscription.php`)

- [ ] Page shows current subscription status and available tiers
- [ ] When subscription is still active, should still allow early renewal

### 5.12 Subscription Expiry Check

- [ ] (Admin) Set a subscriber's `subscription_end_date` to yesterday via database or edit page
- [ ] (Subscriber) Visit any page -- `check_subscription_status.php` should expire the subscription
- [ ] Subscriber should be redirected to renewal page
- [ ] Product catalog should not be accessible with expired subscription

---

## 6. Vendor Dashboard Testing

Log in as the approved `testvendor1` for all tests in this section.

### 6.1 Dashboard Overview (`dashboard_vendor.php`)

- [ ] Dashboard loads without errors
- [ ] Shows vendor business name and status ("Active" after admin approval)
- [ ] Stats show: total donations, recent activity
- [ ] Recent donations table displays
- [ ] Dolibarr sidebar shows vendor-specific menu items only (not admin items)

### 6.2 Add Inventory (`create_donation.php` as vendor)

- [ ] Page loads with vendor auto-selected (vendor cannot choose a different vendor)
- [ ] Warehouse dropdown populated
- [ ] Fill in: Product = "Tomatoes", Quantity = 50, Unit = "crates", Price = 8000
- [ ] Submit -- donation created with "Pending" status
- [ ] Donation appears in "My Supply History"

### 6.3 My Supply History (`my_donations.php`)

- [ ] Lists only this vendor's donations (not other vendors')
- [ ] Shows product name, quantity, warehouse, status, date
- [ ] Submitted donation from 6.2 appears

### 6.4 Vendor Products (`vendor_products.php`)

- [ ] Page loads with vendor's product catalog
- [ ] **Add product:** Create "Fresh Tomatoes" with unit "crates"
- [ ] Product appears in list
- [ ] **Edit product:** Change typical quantity, save
- [ ] **Delete product:** Delete a product not used in donations -- succeeds
- [ ] **Delete product in use:** Try deleting a product used in a donation -- should show error

### 6.5 Vendor Profile (`vendor_profile.php`)

- [ ] Shows business name, contact info, banking details, category, status
- [ ] Edit capability (if available)

### 6.6 Vendor Support (`vendor_support.php` as vendor)

- [ ] **Create ticket:** Fill subject, category, priority, message -- submit
- [ ] Ticket appears in list with "Open" status and reference number
- [ ] **Admin reply:** Log in as admin, find the ticket, reply
- [ ] **Vendor view:** Log back in as vendor -- admin reply visible on the ticket

### 6.7 Password Change (`vendor_password.php`)

- [ ] Form loads, change password, verify login with new password

---

## 7. Cross-Role Security Testing

These tests verify that users cannot access pages belonging to other roles.

### 7.1 As Subscriber, Try Admin Pages

Log in as `testsubscriber1` and visit each URL. **All should return "Access Forbidden":**

- [ ] `/core/pages/dashboard_admin.php`
- [ ] `/core/pages/beneficiaries.php`
- [ ] `/core/pages/vendors.php`
- [ ] `/core/pages/donations.php`
- [ ] `/core/pages/warehouses.php`
- [ ] `/core/pages/packages.php`
- [ ] `/core/pages/distributions.php`
- [ ] `/core/pages/subscription_tiers.php`
- [ ] `/core/pages/user_management.php`
- [ ] `/core/pages/reports.php`
- [ ] `/core/pages/create_beneficiary.php`
- [ ] `/core/pages/edit_vendor.php?id=1`
- [ ] `/core/pages/delete_donation.php?id=1`

### 7.2 As Vendor, Try Admin Pages

Log in as `testvendor1` and visit each URL above. **All should return "Access Forbidden."**

### 7.3 As Vendor, Try Subscriber Pages

- [ ] `/core/pages/dashboard_beneficiary.php` -- Should deny or redirect
- [ ] `/core/pages/product_catalog.php` -- Should deny
- [ ] `/core/pages/checkout.php` -- Should deny
- [ ] `/core/pages/my_orders.php` -- Should deny

### 7.4 As Subscriber, Try Vendor Pages

- [ ] `/core/pages/dashboard_vendor.php` -- Should deny or redirect
- [ ] `/core/pages/my_donations.php` -- Should deny
- [ ] `/core/pages/vendor_products.php` -- Should deny

### 7.5 Ownership Isolation

- [ ] Log in as `testsubscriber2`
- [ ] Try to access `testsubscriber1`'s order: `/core/pages/order_success.php?order_id=X` -- should fail
- [ ] Try to pay for `testsubscriber1`'s order: `/core/pages/process_order_payment.php?order_id=X` -- should fail

### 7.6 Unauthenticated Access

Log out completely and try:

- [ ] `/core/pages/dashboard_admin.php` -- Should redirect to login
- [ ] `/core/pages/dashboard_vendor.php` -- Should redirect to login
- [ ] `/core/pages/dashboard_beneficiary.php` -- Should redirect to login
- [ ] `/core/pages/register.php` -- Should load (public page)
- [ ] `/core/pages/register_vendor.php` -- Should load (public page)
- [ ] `/core/pages/verify_otp.php` -- Should load (public page)

---

## 8. Payment Integration Testing

### 8.1 Paystack Test Mode

Ensure Paystack test keys are configured (`pk_test_...` / `sk_test_...`).

**Paystack test cards:**

| Card Number | Type | Expected Result |
|-------------|------|----------------|
| `4084 0840 8408 4081` | Visa | Success |
| `5060 6666 6666 6666 666` | Verve | Success |
| `4084 0840 8408 4082` | Visa | Declined |

For all test cards: Expiry = any future date, CVV = `408`, OTP = `123456`

### 8.2 Order Payment Flow

- [ ] Place an order with "Pay Now"
- [ ] Paystack widget renders with correct amount (Naira)
- [ ] Amount matches package total (check: displayed amount = sum of items)
- [ ] Complete payment with success test card
- [ ] Callback processes correctly -- order marked as "Paid"
- [ ] Payment record exists in `foodbank_payments` table

### 8.3 Subscription Payment Flow

- [ ] Start subscription payment
- [ ] Paystack widget renders with correct tier price
- [ ] Complete payment
- [ ] Subscription activated with correct start/end dates
- [ ] Duration matches tier (e.g., 12 months for Annual)
- [ ] Payment record created

### 8.4 Payment Failure

- [ ] Use the decline test card (`4084 0840 8408 4082`)
- [ ] Payment should fail gracefully
- [ ] User should see an error message, not a crash
- [ ] No order/subscription should be activated on failure
- [ ] User can retry payment

### 8.5 Payment Verification

- [ ] (Admin) Check `foodbank_payments` table -- all payments have correct:
  - `fk_subscriber` -- correct subscriber ID
  - `amount` -- matches expected price
  - `payment_status` -- "Success" for completed, "Pending" for incomplete
  - `payment_reference` -- Paystack reference present
  - `payment_date` -- timestamp present

---

## 9. Edge Cases & Error Handling

### 9.1 Empty States

- [ ] Admin dashboard with no data -- stats show 0, tables show "No records"
- [ ] Subscriber cart when empty -- shows empty cart message with link to catalog
- [ ] My Orders with no orders -- shows appropriate message
- [ ] Packages with no items -- shows 0 items, price = 0

### 9.2 Concurrent Actions

- [ ] Two subscribers add the same package to cart simultaneously -- both should work
- [ ] Subscriber adds to cart while admin edits the package -- no crash

### 9.3 Browser Navigation

- [ ] Complete checkout, press browser Back button -- should not re-submit order
- [ ] Complete Paystack payment, press Back -- should not double-charge

### 9.4 Session Handling

- [ ] Log in, close browser tab (not window), open new tab -- session should persist
- [ ] Log in as subscriber, open admin URL in new tab -- should get access denied (not switch roles)
- [ ] Complete OTP verification -- session should be regenerated (check session ID changes)

### 9.5 Input Validation

- [ ] Registration: Try SQL injection in username: `admin' OR '1'='1` -- should be rejected (invalid chars)
- [ ] Registration: Try XSS in name field: `<script>alert(1)</script>` -- should be escaped in display
- [ ] Donation: Try negative quantity -- check behavior
- [ ] Subscription tier: Try price = 0 -- check behavior

---

## 10. Responsive & UI Testing

### 10.1 Mobile Viewport (< 768px)

- [ ] Welcome page (`index.php`) -- buttons stack vertically, readable
- [ ] Registration forms -- fields stack, no horizontal overflow
- [ ] Subscriber dashboard -- readable, no overlapping elements
- [ ] Product catalog -- cards/grid adapts to mobile
- [ ] Checkout page -- form usable on mobile
- [ ] Paystack widget -- renders correctly on mobile

### 10.2 Tablet Viewport (768px - 1024px)

- [ ] Admin dashboard -- sidebar and content area both visible
- [ ] Tables -- readable, may need horizontal scroll for wide tables
- [ ] Forms -- grid layouts adapt (2-column to 1-column)

### 10.3 Cross-Browser

Test in at least 2 browsers:
- [ ] Chrome
- [ ] Firefox or Safari
- [ ] Check: password show/hide toggle works
- [ ] Check: password strength meter updates
- [ ] Check: Paystack widget renders

---

## 11. Pre-Deployment Checklist (Namecheap)

### 11.1 Server Requirements

- [ ] PHP 7.4+ installed on hosting
- [ ] MySQL 5.7+ or MariaDB 10.3+ available
- [ ] cURL PHP extension enabled (required for Paystack verification)
- [ ] mod_rewrite enabled (Apache) or equivalent Nginx config
- [ ] SSL certificate installed and HTTPS enforced (required for Paystack)
- [ ] PHP `mail()` function working OR SMTP configured for OTP emails

### 11.2 Dolibarr Installation

- [ ] Dolibarr installed on hosting (download from dolibarr.org)
- [ ] `conf.php` configured with correct database credentials
- [ ] Custom module directory enabled:
  ```php
  $dolibarr_main_url_root_alt = '/custom';
  $dolibarr_main_document_root_alt = '/path/to/htdocs/custom';
  ```
- [ ] FoodbankCRM module files uploaded to `htdocs/custom/foodbankcrm/`
- [ ] File permissions: directories `755`, files `644`

### 11.3 Go-Live Switch

- [ ] **Paystack live keys:** Replace `pk_test_` / `sk_test_` with `pk_live_` / `sk_live_` in Settings
- [ ] **Verify Paystack webhook URL** (if applicable) points to production domain
- [ ] **Test one real payment** with a small amount to verify live keys work
- [ ] **Email delivery:** Send a test OTP registration to verify emails arrive in production
- [ ] **Domain DNS:** Namecheap DNS records point to hosting server
- [ ] **SSL:** Verify `https://yourdomain.com` works with valid certificate
- [ ] **Error display OFF:** In `conf.php` or `php.ini`:
  ```ini
  display_errors = Off
  log_errors = On
  error_log = /path/to/php-error.log
  ```
- [ ] **Remove test data:** Delete test subscribers, vendors, orders, and payments created during testing
- [ ] **Create production subscription tiers** with real pricing
- [ ] **Create production warehouses** with real locations
- [ ] **Backup database** before going live

### 11.4 DNS & Domain (Namecheap)

- [ ] Set A record pointing to your server IP
- [ ] Or set nameservers to your hosting provider's nameservers
- [ ] Wait for DNS propagation (up to 48 hours, usually 1-2 hours)
- [ ] Verify domain resolves: `ping yourdomain.com`
- [ ] Set up `www` CNAME or redirect
- [ ] Force HTTPS redirect (via `.htaccess` or hosting panel)

### 11.5 Post-Launch Monitoring

- [ ] Monitor PHP error log for the first 48 hours
- [ ] Check Paystack dashboard for successful transactions
- [ ] Verify OTP emails are being delivered (check spam folders)
- [ ] Test the full registration flow from a real user's perspective
- [ ] Confirm admin can approve vendors and manage all entities

---

## 12. Test Results Tracker

Use this table to track your testing progress:

| Section | Status | Tester | Date | Notes |
|---------|--------|--------|------|-------|
| 0. Environment Setup | | | | |
| 1. Public Gateway | | | | |
| 2. Subscriber Registration | | | | |
| 3. Vendor Registration | | | | |
| 4. Admin Dashboard | | | | |
| 5. Subscriber Dashboard | | | | |
| 6. Vendor Dashboard | | | | |
| 7. Security Testing | | | | |
| 8. Payment Testing | | | | |
| 9. Edge Cases | | | | |
| 10. Responsive/UI | | | | |
| 11. Pre-Deployment | | | | |

---

## Quick Reference: Test Card Numbers

| Card | Number | Result |
|------|--------|--------|
| Visa (Success) | `4084 0840 8408 4081` | Approved |
| Verve (Success) | `5060 6666 6666 6666 666` | Approved |
| Visa (Decline) | `4084 0840 8408 4082` | Declined |
| **For all cards:** | Expiry: any future | CVV: `408`, OTP: `123456` |
