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
        $this->picto = 'generic';
        $this->name         = 'foodbankcrm';
        $this->description  = 'Foodbank CRM custom module';
        $this->version      = '1.2';
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
        // ---- Permissions ----
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

        // ---- Menus ----
        $this->menu = array();
        $r = 0;

        // Top menu
        $this->menu[$r] = array(
            'fk_menu'   => '',
            'type'      => 'top',
            'titre'     => 'Foodbank CRM',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => '',
            'url'       => '/custom/foodbankcrm/index.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1000,
            'enabled'   => '1',
            'perms'     => '$user->rights->foodbankcrm->read || $user->rights->foodbankcrm->vendor->dashboard || $user->rights->foodbankcrm->beneficiary->dashboard',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // ===== ADMIN-ONLY LEFT MENU ITEMS =====
        
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Beneficiaries',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_beneficiaries',
            'url'       => '/custom/foodbankcrm/core/pages/beneficiaries.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1001,
            'enabled'   => '1',
            'perms'     => '$user->admin', // ADMIN ONLY
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
            'position'  => 1002,
            'enabled'   => '1',
            'perms'     => '$user->admin', // ADMIN ONLY
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Donations',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_donations',
            'url'       => '/custom/foodbankcrm/core/pages/donations.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1003,
            'enabled'   => '1',
            'perms'     => '$user->admin', // ADMIN ONLY
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
            'position'  => 1004,
            'enabled'   => '1',
            'perms'     => '$user->admin', // ADMIN ONLY
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
            'position'  => 1005,
            'enabled'   => '1',
            'perms'     => '$user->admin', // ADMIN ONLY
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
            'position'  => 1006,
            'enabled'   => '1',
            'perms'     => '$user->admin', // ADMIN ONLY
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
            'position'  => 1007,
            'enabled'   => '1',
            'perms'     => '$user->admin', // ADMIN ONLY
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
            'position'  => 1008,
            'enabled'   => '1',
            'perms'     => '$user->admin', // ADMIN ONLY
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Product Catalog',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_products',
            'url'       => '/custom/foodbankcrm/core/pages/product_catalog.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1009,
            'enabled'   => '1',
            'perms'     => '$user->admin', // ADMIN ONLY
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // ===== VENDOR MENU ITEMS =====
        
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'My Donations',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_my_donations',
            'url'       => '/custom/foodbankcrm/core/pages/my_donations.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 2001,
            'enabled'   => '1',
            'perms'     => '$user->rights->foodbankcrm->vendor->view_own', // VENDOR ONLY
            'target'    => '',
            'user'      => 2
        );
        $r++;

        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm',
            'type'      => 'left',
            'titre'     => 'Submit Donation',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => 'foodbankcrm_create_donation',
            'url'       => '/custom/foodbankcrm/core/pages/create_donation.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 2002,
            'enabled'   => '1',
            'perms'     => '$user->rights->foodbankcrm->vendor->create_donation', // VENDOR ONLY
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // ===== BENEFICIARY MENU ITEMS =====
        
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
            'perms'     => '$user->rights->foodbankcrm->beneficiary->view_own', // BENEFICIARY ONLY
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
            'perms'     => '$user->rights->foodbankcrm->beneficiary->dashboard', // BENEFICIARY ONLY
            'target'    => '',
            'user'      => 2
        );
        $r++;
    }

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