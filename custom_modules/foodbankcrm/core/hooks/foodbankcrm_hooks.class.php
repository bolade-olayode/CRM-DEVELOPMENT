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
     * Send HTTP security headers for every page that triggers this hook.
     *
     * These headers are a protective layer that tells the user's browser how to
     * behave with our pages — blocking clickjacking, MIME sniffing, etc.
     *
     * The .htaccess file covers our custom module pages (core/pages/).
     * This method covers Dolibarr's core pages (home, admin, etc.) where the
     * hook manager is called, so nothing slips through.
     *
     * Uses headers_sent() check so it is safe to call even if output has started.
     */
    private function sendSecurityHeaders()
    {
        if (headers_sent()) {
            return; // Too late to add headers — output already flushed
        }

        // Prevent browsers from guessing file types (e.g., treating a text file as JS)
        header('X-Content-Type-Options: nosniff');

        // Prevent the site from being embedded in iframes on other domains (anti-clickjacking)
        header('X-Frame-Options: SAMEORIGIN');

        // Only share our domain name (not full URL) when linking to external sites
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Disable browser features we never use
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // Control which external resources the browser is allowed to load
        // Paystack domains are whitelisted so payments continue to work
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://js.paystack.co; connect-src 'self' https://api.paystack.co; frame-src https://checkout.paystack.com; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; font-src 'self' data:");
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

        // Always send security headers — this fires for any Dolibarr page that
        // invokes the hook manager, regardless of which page it is.
        $this->sendSecurityHeaders();

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
