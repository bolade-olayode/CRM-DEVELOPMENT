-- =============================================================================
-- FoodbankCRM Module - Database Schema
-- Run during module activation to create all required tables.
-- =============================================================================

-- 1. Beneficiaries (Subscribers)
CREATE TABLE IF NOT EXISTS llx_foodbank_beneficiaries (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_user INT,
  ref VARCHAR(64) NOT NULL,
  firstname VARCHAR(120),
  lastname VARCHAR(120),
  email VARCHAR(255),
  phone VARCHAR(50),
  address VARCHAR(255),
  city VARCHAR(100),
  state VARCHAR(100),
  gender VARCHAR(20),
  dob DATE,
  identification_number VARCHAR(100),
  family_size INT DEFAULT 1,
  employment_status VARCHAR(50),
  subscription_type VARCHAR(50),
  subscription_status VARCHAR(30) DEFAULT 'Pending',
  subscription_start_date DATE,
  subscription_end_date DATE,
  subscription_fee DECIMAL(12,2) DEFAULT 0,
  max_orders_per_month INT DEFAULT NULL,
  can_place_orders TINYINT(1) NOT NULL DEFAULT 0,
  payment_method VARCHAR(50),
  last_payment_date DATETIME,
  note TEXT,
  push_token VARCHAR(200) DEFAULT NULL,
  entity INT DEFAULT 1,
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ben_fk_user (fk_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Vendors
CREATE TABLE IF NOT EXISTS llx_foodbank_vendors (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_user INT,
  ref VARCHAR(64) NOT NULL,
  name VARCHAR(255),
  category VARCHAR(100),
  contact_person VARCHAR(255),
  contact_email VARCHAR(255),
  contact_phone VARCHAR(50),
  phone VARCHAR(50),
  email VARCHAR(255),
  address TEXT,
  description TEXT,
  registration_number VARCHAR(100),
  tax_id VARCHAR(100),
  website VARCHAR(255),
  bank_name VARCHAR(100),
  bank_account_number VARCHAR(50),
  payment_terms VARCHAR(255),
  status VARCHAR(50) DEFAULT 'Active',
  push_token VARCHAR(200) DEFAULT NULL,
  entity INT DEFAULT 1,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_vend_fk_user (fk_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Warehouses
CREATE TABLE IF NOT EXISTS llx_foodbank_warehouses (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(64) NOT NULL,
  label VARCHAR(255),
  address VARCHAR(255),
  capacity INT DEFAULT 0,
  note TEXT,
  entity INT DEFAULT 1,
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Donations (Inventory Items)
CREATE TABLE IF NOT EXISTS llx_foodbank_donations (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(64) NOT NULL,
  product_name VARCHAR(255),
  category VARCHAR(100),
  label VARCHAR(255),
  quantity INT DEFAULT 0,
  quantity_allocated INT DEFAULT 0,
  unit VARCHAR(50) DEFAULT 'unit',
  unit_price DECIMAL(12,2),
  total_value DECIMAL(12,2),
  fk_vendor INT,
  fk_warehouse INT,
  fk_beneficiary INT,
  fk_vendor_product INT,
  delivery_method VARCHAR(100),
  status VARCHAR(50) DEFAULT 'Pending',
  date_donation DATETIME DEFAULT CURRENT_TIMESTAMP,
  entity INT DEFAULT 1,
  note TEXT,
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_don_fk_vendor (fk_vendor),
  INDEX idx_don_fk_warehouse (fk_warehouse),
  INDEX idx_don_fk_ben (fk_beneficiary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Packages (Food bundles for sale)
CREATE TABLE IF NOT EXISTS llx_foodbank_packages (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(64) NOT NULL,
  name VARCHAR(255),
  description TEXT,
  status VARCHAR(50) DEFAULT 'Active',
  image_url VARCHAR(500) DEFAULT NULL,
  entity INT DEFAULT 1,
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Package Items (Products inside a package)
CREATE TABLE IF NOT EXISTS llx_foodbank_package_items (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_package INT NOT NULL,
  product_name VARCHAR(255),
  quantity DECIMAL(10,2) DEFAULT 0,
  unit VARCHAR(50) DEFAULT 'unit',
  unit_price DECIMAL(12,2) DEFAULT 0,
  fk_vendor_preferred INT,
  note TEXT,
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pi_fk_package (fk_package),
  FOREIGN KEY (fk_package) REFERENCES llx_foodbank_packages(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Distributions (Orders placed by subscribers)
CREATE TABLE IF NOT EXISTS llx_foodbank_distributions (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(64) NOT NULL,
  fk_beneficiary INT,
  fk_package INT,
  fk_warehouse INT,
  fk_user INT,
  date_distribution DATETIME DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'Prepared',
  total_amount DECIMAL(12,2) DEFAULT 0,
  payment_status VARCHAR(50) DEFAULT 'Pending',
  payment_method VARCHAR(50),
  payment_reference VARCHAR(255),
  payment_gateway VARCHAR(50),
  payment_date DATETIME,
  note TEXT,
  entity INT DEFAULT 1,
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_dist_fk_ben (fk_beneficiary),
  INDEX idx_dist_fk_pkg (fk_package)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Distribution Lines (Individual items in an order)
CREATE TABLE IF NOT EXISTS llx_foodbank_distribution_lines (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_distribution INT NOT NULL,
  fk_donation INT,
  product_name VARCHAR(255),
  quantity DECIMAL(10,2) DEFAULT 0,
  unit VARCHAR(50) DEFAULT 'unit',
  note TEXT,
  INDEX idx_dl_fk_dist (fk_distribution),
  FOREIGN KEY (fk_distribution) REFERENCES llx_foodbank_distributions(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Subscription Tiers
CREATE TABLE IF NOT EXISTS llx_foodbank_subscription_tiers (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  tier_name VARCHAR(255),
  tier_type VARCHAR(50),
  duration_months INT DEFAULT 12,
  duration_days INT DEFAULT 365,
  price DECIMAL(12,2) DEFAULT 0,
  description TEXT,
  benefits TEXT,
  max_orders_per_month INT DEFAULT NULL,
  can_place_orders TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) DEFAULT 1,
  status VARCHAR(50) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Payments
CREATE TABLE IF NOT EXISTS llx_foodbank_payments (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_subscriber INT,
  fk_order INT,
  payment_type VARCHAR(50),
  amount DECIMAL(12,2) DEFAULT 0,
  payment_method VARCHAR(50),
  payment_status VARCHAR(50) DEFAULT 'Pending',
  payment_reference VARCHAR(255),
  payment_date DATETIME,
  date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pay_fk_sub (fk_subscriber),
  INDEX idx_pay_fk_order (fk_order),
  INDEX idx_pay_ref (payment_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Shopping Cart
CREATE TABLE IF NOT EXISTS llx_foodbank_cart (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_subscriber INT NOT NULL,
  fk_package INT,
  fk_donation INT,
  quantity DECIMAL(10,2) DEFAULT 1,
  unit_price DECIMAL(12,2) DEFAULT 0,
  date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cart_fk_sub (fk_subscriber)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Vendor Products (Catalog of products a vendor can supply)
CREATE TABLE IF NOT EXISTS llx_foodbank_vendor_products (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_vendor INT NOT NULL,
  product_name VARCHAR(255),
  unit VARCHAR(50) DEFAULT 'unit',
  typical_quantity DECIMAL(10,2),
  note TEXT,
  status VARCHAR(50) DEFAULT 'Active',
  INDEX idx_vp_fk_vendor (fk_vendor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. User-Vendor Links (Maps Dolibarr users to vendor profiles)
CREATE TABLE IF NOT EXISTS llx_foodbank_user_vendor (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_user INT NOT NULL,
  fk_vendor INT NOT NULL,
  created_by INT,
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_uv_fk_user (fk_user),
  INDEX idx_uv_fk_vendor (fk_vendor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. User-Beneficiary Links (Maps Dolibarr users to subscriber profiles)
CREATE TABLE IF NOT EXISTS llx_foodbank_user_beneficiary (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_user INT NOT NULL,
  fk_beneficiary INT NOT NULL,
  created_by INT,
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ub_fk_user (fk_user),
  INDEX idx_ub_fk_ben (fk_beneficiary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Support Tickets (Vendor helpdesk)
CREATE TABLE IF NOT EXISTS llx_foodbank_support (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_vendor INT NOT NULL,
  ref VARCHAR(30),
  subject VARCHAR(255) NOT NULL,
  category VARCHAR(50),
  priority VARCHAR(20) DEFAULT 'Normal',
  message TEXT,
  admin_reply TEXT,
  status VARCHAR(20) DEFAULT 'Open',
  date_created DATETIME,
  date_updated DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Rate Limiting (Brute-force protection for registration & OTP)
CREATE TABLE IF NOT EXISTS llx_foodbank_rate_limit (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(255),
  action_type VARCHAR(50),
  attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rl_ip_action (ip_address, action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Email Verification (OTP codes)
-- 17b. Pending Orders (order intent saved before Paystack payment opens,
--      consumed by the webhook if the app loses connection after payment)
CREATE TABLE IF NOT EXISTS llx_foodbank_pending_orders (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_beneficiary INT NOT NULL,
  payment_reference VARCHAR(255) NOT NULL,
  delivery_address TEXT NOT NULL,
  notes TEXT,
  status ENUM('pending','completed','cancelled') DEFAULT 'pending',
  datec DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pending_ref (payment_reference),
  INDEX idx_pending_ben (fk_beneficiary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS llx_foodbank_email_verification (
  email VARCHAR(255) PRIMARY KEY,
  code VARCHAR(10),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. Set Dolibarr's native landing-page redirect to the FoodbankCRM role router.
--     MAIN_LANDING_PAGE is read by main.inc.php immediately after every successful
--     login, before any module-enable check, so it works even when the module is
--     toggled off or after the SQL reset that wipes llx_const FOODBANK rows.
INSERT INTO llx_const (name, value, type, visible, note, entity)
VALUES (
  'MAIN_LANDING_PAGE',
  '/custom/foodbankcrm/core/pages/redirect_dashboard.php',
  'chaine', 0,
  'FoodbankCRM: redirect each login to the role-based dashboard router',
  1
)
ON DUPLICATE KEY UPDATE
  value = '/custom/foodbankcrm/core/pages/redirect_dashboard.php';
