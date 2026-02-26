<?php
/**
 * Hook to inject redirect JavaScript on every Dolibarr page
 */

class ActionsFoodbankcrm
{
    public $db;
    public $conf;
    public $langs;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    // Hook reserved for future UI injections (e.g. custom footer elements).
    // Post-login redirects are handled exclusively by the USER_LOGIN trigger.
}