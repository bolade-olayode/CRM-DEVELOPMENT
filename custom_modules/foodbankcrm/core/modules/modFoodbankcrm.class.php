<?php
// Minimal, safe descriptor for Dolibarr 15–19
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modFoodbankcrm extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;
        $this->db = $db;

        $this->numero       = 110001;                 // unique > 100000
        $this->rights_class = 'foodbankcrm';
        $this->family       = 'crm';                  // or 'other'
        $this->module_position = 500000;
        $this->name         = 'foodbankcrm';
        $this->description  = 'Foodbank CRM custom module';
        $this->version      = 'development';
        $this->editor_name  = 'YourName';
        $this->editor_url   = '';

        $this->const        = array();
        $this->dirs         = array('/foodbankcrm');  // creates documents/foodbankcrm
        $this->config_page_url = array('setup.php@foodbankcrm');
        $this->langfiles    = array('foodbankcrm@foodbankcrm');
        $this->phpmin       = array(7,4);             // Dolibarr 19 runs on PHP 8.x, 7.4 min okay

        $this->depends      = array();                // no hard deps
        $this->requiredby   = array();
        $this->conflictwith = array();

        // Permissions
        $this->rights = array();
        $r=0;
        $this->rights[$r][0] = 110001;
        $this->rights[$r][1] = 'Read Foodbank CRM';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';
        $r++;

        // Simple top menu to prove it works
        $this->menu = array();
        $r=0;
        $this->menu[$r]=array(
            'fk_menu'   => 'fk_mainmenu=foodbankcrm,fk_leftmenu=',
            'type'      => 'top',
            'titre'     => 'Foodbank CRM',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => '',
            'url'       => '/custom/foodbankcrm/index.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1000,
            'enabled'   => '1',
            'perms'     => 'isset($user->rights->foodbankcrm->read) && $user->rights->foodbankcrm->read',
            'target'    => '',
            'user'      => 2
        );
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
