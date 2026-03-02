<?php
/**
 * Hook to redirect Foodbank CRM users away from Dolibarr's default home page.
 *
 * This is a safety net for the USER_LOGIN trigger: if the trigger's header()
 * redirect fails for any reason (e.g. output already flushed), the user lands
 * on Dolibarr's index.php.  This hook intercepts that page and immediately
 * sends a JS redirect to the correct role dashboard before any content renders.
 */

class ActionsFoodbankcrm
{
    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Intercept Dolibarr's main home page (htdocs/index.php) and redirect
     * logged-in Foodbank CRM users to their role-appropriate dashboard.
     *
     * Called by $hookmanager->executeHooks('doActions', ...) on the home page.
     * At that point llxHeader() has already run so we cannot use header().
     * A JS window.location.replace() is instant and the user never sees the
     * Dolibarr home page content.
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $db;

        // Only act on Dolibarr's own index.php – not on our custom pages.
        $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
        if (basename($self) !== 'index.php') {
            return 0;
        }
        if (strpos($self, 'foodbankcrm') !== false || strpos($self, 'custom') !== false) {
            return 0;
        }

        if (empty($user) || empty($user->id)) {
            return 0;
        }

        require_once DOL_DOCUMENT_ROOT . '/custom/foodbankcrm/class/permissions.class.php';

        $redirect_url = '';

        if (FoodbankPermissions::isAdmin($user)) {
            $redirect_url = DOL_URL_ROOT . '/custom/foodbankcrm/core/pages/dashboard_admin.php';
        } elseif (FoodbankPermissions::isVendor($user, $db)) {
            $redirect_url = DOL_URL_ROOT . '/custom/foodbankcrm/core/pages/dashboard_vendor.php';
        } elseif (FoodbankPermissions::isBeneficiary($user, $db)) {
            $redirect_url = DOL_URL_ROOT . '/custom/foodbankcrm/core/pages/dashboard_beneficiary.php';
        }

        if ($redirect_url) {
            // Try a proper HTTP redirect first (works if headers not yet sent).
            if (!headers_sent()) {
                session_write_close();
                header('Location: ' . $redirect_url);
                exit;
            }
            // Fallback: JavaScript redirect (instant, before page content paints).
            print '<script>window.location.replace(' . json_encode($redirect_url) . ');</script>';
        }

        return 0;
    }
}
