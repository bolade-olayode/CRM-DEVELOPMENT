<?php
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modFoodbankcrm extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;
        $this->db = $db;

        $this->numero       = 110001;
        $this->rights_class = 'foodbankcrm';
        $this->family       = 'crm';
        $this->module_position = 500000;
        $this->picto        = 'generic';
        $this->name         = 'foodbankcrm';
        $this->description  = 'Foodbank CRM custom module';
        $this->version      = '1.4';
        $this->editor_name  = 'Olayode Boladde';
        $this->editor_url   = '';

        $this->const        = array();
        $this->dirs         = array('/foodbankcrm');
        $this->config_page_url = array('setup.php@foodbankcrm');
        $this->langfiles    = array('foodbankcrm@foodbankcrm');
        $this->phpmin       = array(7,4);

        $this->depends      = array();
        $this->requiredby   = array();
        $this->conflictwith = array();
        $this->module_parts = array();

        // =================================================================
        // 1. INJECT LOGIN PAGE BUTTONS (Register / Vendor Application)
        // =================================================================
        $this->const = array();
        $r = 0;

        $html_buttons = '<div style="margin-top:20px; border-top:1px solid #eee; padding-top:15px; text-align:center;">';
        $html_buttons .= '<a href="'.DOL_URL_ROOT.'/custom/foodbankcrm/core/pages/register.php" style="display:block; margin-bottom:10px; text-decoration:none; background:#667eea; color:white; padding:10px; border-radius:5px; font-weight:bold;">👤 Register as Beneficiary</a>';
        $html_buttons .= '<a href="'.DOL_URL_ROOT.'/custom/foodbankcrm/core/pages/register_vendor.php" style="display:block; text-decoration:none; background:#f8f9fa; color:#333; border:1px solid #ddd; padding:10px; border-radius:5px; font-weight:bold;">🏢 Become a Vendor</a>';
        $html_buttons .= '</div>';

        $this->const[$r] = array(
            'name' => 'MAIN_LOGIN_INSTRUCTIONS',
            'consttype' => 'chaine',
            'value' => $html_buttons,
            'note' => 'Added by FoodbankCRM',
            'visible' => 1
        );
        $r++;

        $this->const[$r] = array(
            'name' => 'FOODBANK_PAYSTACK_PUBLIC_KEY',
            'consttype' => 'chaine',
            'value' => '',
            'note' => 'Paystack public API key (pk_test_... or pk_live_...)',
            'visible' => 0
        );
        $r++;

        $this->const[$r] = array(
            'name' => 'FOODBANK_PAYSTACK_SECRET_KEY',
            'consttype' => 'chaine',
            'value' => '',
            'note' => 'Paystack secret API key (sk_test_... or sk_live_...)',
            'visible' => 0
        );
        $r++;


        // =================================================================
        // 2. PERMISSIONS
        // =================================================================
        $this->rights = array();
        $r = 0;

        // Admin permissions
        $this->rights[$r][0] = 100001;
        $this->rights[$r][1] = 'Read Foodbank CRM';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = 100002;
        $this->rights[$r][1] = 'Write Foodbank CRM';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->rights[$r][0] = 100003;
        $this->rights[$r][1] = 'Delete Foodbank CRM';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'delete';
        $r++;

        // Vendor permissions
        $this->rights[$r][0] = 100011;
        $this->rights[$r][1] = 'Vendor Dashboard Access';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'vendor';
        $this->rights[$r][5] = 'dashboard';
        $r++;

        $this->rights[$r][0] = 100012;
        $this->rights[$r][1] = 'Vendor Create Donations';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'vendor';
        $this->rights[$r][5] = 'create_donation';
        $r++;

        $this->rights[$r][0] = 100013;
        $this->rights[$r][1] = 'Vendor View Own Donations';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'vendor';
        $this->rights[$r][5] = 'view_own';
        $r++;

        // Beneficiary permissions
        $this->rights[$r][0] = 100021;
        $this->rights[$r][1] = 'Beneficiary Dashboard Access';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'beneficiary';
        $this->rights[$r][5] = 'dashboard';
        $r++;

        $this->rights[$r][0] = 100022;
        $this->rights[$r][1] = 'Beneficiary View Own Orders';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'beneficiary';
        $this->rights[$r][5] = 'view_own';
        $r++;


        // =================================================================
        // 3. MENUS
        // =================================================================
        $this->menu = array();
        $r = 0;

        // --- Top Menu ---
        $this->menu[$r] = array(
            'fk_menu'   => '',
            'type'      => 'top',
            'titre'     => 'Foodbank CRM',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => '',
            'url'       => '/custom/foodbankcrm/core/pages/dashboard_admin.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1000,
            'enabled'   => '1',
            'perms'     => '$user->rights->foodbankcrm->read || $user->rights->foodbankcrm->vendor->dashboard || $user->rights->foodbankcrm->beneficiary->dashboard',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // --- ADMIN SIDEBAR ITEMS ---

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Admin Overview',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_overview',
            'url'       => '/custom/foodbankcrm/core/pages/dashboard_admin.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1000,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Subscribers',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_beneficiaries',
            'url'       => '/custom/foodbankcrm/core/pages/beneficiaries.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1100,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Vendors',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_vendors',
            'url'       => '/custom/foodbankcrm/core/pages/vendors.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1110,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Vendor Support',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_support',
            'url'       => '/custom/foodbankcrm/core/pages/vendor_support.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1120,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Inventory Logs',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_donations',
            'url'       => '/custom/foodbankcrm/core/pages/donations.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1200,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Warehouses',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_warehouses',
            'url'       => '/custom/foodbankcrm/core/pages/warehouses.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1210,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Packages',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_packages',
            'url'       => '/custom/foodbankcrm/core/pages/packages.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1220,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Distributions',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_distributions',
            'url'       => '/custom/foodbankcrm/core/pages/distributions.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1230,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Subscription Tiers',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_tiers',
            'url'       => '/custom/foodbankcrm/core/pages/subscription_tiers.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1300,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'User Management',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_users',
            'url'       => '/custom/foodbankcrm/core/pages/user_management.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1400,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Settings',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_setup',
            'url'       => '/admin/foodbankcrm.php?save_lastsearch_values=1',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1410,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;


        // --- VENDOR SIDEBAR ITEMS ---

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'My Supply History',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_my_donations',
            'url'       => '/custom/foodbankcrm/core/pages/my_donations.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 2001,
            'enabled'   => '1',
            'perms'     => '$user->rights->foodbankcrm->vendor->view_own && !$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Add Inventory',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_create_donation',
            'url'       => '/custom/foodbankcrm/core/pages/create_donation.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 2002,
            'enabled'   => '1',
            'perms'     => '$user->rights->foodbankcrm->vendor->create_donation && !$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;


        // --- BENEFICIARY SIDEBAR ITEMS ---

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'My Orders',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_my_orders',
            'url'       => '/custom/foodbankcrm/core/pages/my_orders.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 3001,
            'enabled'   => '1',
            'perms'     => '$user->rights->foodbankcrm->beneficiary->view_own && !$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Available Packages',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_available_packages',
            'url'       => '/custom/foodbankcrm/core/pages/available_packages.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 3002,
            'enabled'   => '1',
            'perms'     => '$user->rights->foodbankcrm->beneficiary->dashboard && !$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

    } // <--- Constructor Ends Here (Correctly!)

    public function init($options = '')
    {
        $sql = array();
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}