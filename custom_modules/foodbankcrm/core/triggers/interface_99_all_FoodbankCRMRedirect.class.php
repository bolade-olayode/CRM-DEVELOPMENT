<?php
/**
 * Login redirect trigger for Foodbank CRM.
 *
 * Named with "all" as the module segment so Dolibarr bypasses the
 * isModEnabled() check that would silently skip the trigger when the
 * module's MAIN_MODULE_* constant is missing from llx_const (which
 * happens after a DB reset).
 *
 * Dolibarr derives the class name as:  Interface + ucfirst($reg[3])
 *   interface_99_all_FoodbankCRMRedirect.class.php
 *                   ^^^  ^^^^^^^^^^^^^^^^^^^
 *                   reg2      reg3
 * → class InterfaceFoodbankCRMRedirect
 *
 * NOTE: In a USER_LOGIN trigger $user is the GLOBAL session object
 * (not yet populated). $object is the User that just authenticated.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceFoodbankCRMRedirect extends DolibarrTriggers
{
    public function __construct($db)
    {
        $this->db          = $db;
        $this->name        = 'FoodbankCRMRedirect';
        $this->family      = 'foodbankcrm';
        $this->description = 'Redirect Foodbank CRM users to their dashboard after login';
        $this->version     = '1.0';
        $this->picto       = 'foodbankcrm@foodbankcrm';
    }

    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        if ($action !== 'USER_LOGIN') {
            return 0;
        }

        if (empty($object) || empty($object->id)) {
            return 0;
        }

        require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

        $redirect_url = '';

        // Admin check
        $res = $this->db->query(
            "SELECT admin FROM ".MAIN_DB_PREFIX."user
             WHERE rowid = ".(int)$object->id." AND admin = 1"
        );
        if ($res && $this->db->num_rows($res) > 0) {
            $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_admin.php';
        }
        // Vendor check
        elseif (FoodbankPermissions::isVendor($object, $this->db)) {
            $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_vendor.php';
        }
        // Beneficiary check
        elseif (FoodbankPermissions::isBeneficiary($object, $this->db)) {
            $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_beneficiary.php';
        }

        if ($redirect_url) {
            header('Location: '.DOL_URL_ROOT.$redirect_url);
            exit;
        }

        return 0;
    }
}
