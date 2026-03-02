<?php
/**
 * Superseded by interface_99_all_FoodbankCRMRedirect.class.php
 *
 * This file is kept to avoid breaking anything that references it, but the
 * active redirect logic now lives in the "all" trigger above, which runs
 * regardless of whether isModEnabled('foodbankcrm') is true.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceRedirect extends DolibarrTriggers
{
    public function __construct($db)
    {
        $this->db          = $db;
        $this->name        = 'Redirect';
        $this->family      = 'foodbankcrm';
        $this->description = 'Superseded — see interface_99_all_FoodbankCRMRedirect.class.php';
        $this->version     = '1.0';
        $this->picto       = 'foodbankcrm@foodbankcrm';
    }

    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        // No-op: redirect logic moved to interface_99_all_FoodbankCRMRedirect.class.php
        return 0;
    }
}
