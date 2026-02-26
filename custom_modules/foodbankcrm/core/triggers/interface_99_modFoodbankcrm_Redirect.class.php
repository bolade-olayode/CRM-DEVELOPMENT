<?php
/**
 * Trigger to redirect users to custom dashboards after login
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceRedirect extends DolibarrTriggers
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "foodbankcrm";
        $this->description = "Redirect users to custom dashboards";
        $this->version = '1.0';
        $this->picto = 'foodbankcrm@foodbankcrm';
    }

    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        // Only trigger on successful login
        if ($action == 'USER_LOGIN') {
            require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
            
            $redirect_url = '';

            // 1. Check Admin - use DB query (same pattern as vendor/beneficiary, more reliable at trigger time)
            $sql_admin = "SELECT admin FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".(int)$user->id." AND admin = 1";
            $res_admin = $this->db->query($sql_admin);
            if ($res_admin && $this->db->num_rows($res_admin) > 0) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_admin.php';
            }
            // 2. Check Vendor
            elseif (FoodbankPermissions::isVendor($user, $this->db)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_vendor.php';
            } 
            // 3. Check Beneficiary
            elseif (FoodbankPermissions::isBeneficiary($user, $this->db)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_beneficiary.php';
            }
            
            // 4. FORCE REDIRECT
            if ($redirect_url) {
                header("Location: " . DOL_URL_ROOT . $redirect_url);
                exit;
            }
        }
        
        return 0;
    }
}