<?php
/**
 * Beneficiary Page Header - Include this at the top of all beneficiary pages
 */

// Reset redirect flag
if (isset($_SESSION['foodbank_auto_redirected'])) {
    $_SESSION['foodbank_auto_redirected'] = false;
}

// Hide left menu with inline CSS
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; }
.fiche { max-width: 1400px; margin: 0 auto; }
</style>';

// Hide unauthorized menu items with JavaScript
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // List of menu items to hide (keep only beneficiary-related items)
    const menusToHide = [
        "Beneficiaries", "Vendors", "Donations", "Distributions",
        "Packages", "Warehouses", "Subscription Tiers", "User Management",
        "Third parties", "Products", "Services", "Banks", "Cash", 
        "Accounting", "HRM", "Projects", "Commercial", "Billing", "Payment",
        "MRP", "Documents", "Agenda", "Tickets", "Tools", "ExternalSite", "Websites"
    ];
    
    // Hide top menu items
    document.querySelectorAll(".tmenu").forEach(function(menu) {
        const menuText = menu.textContent.trim();
        if (menusToHide.some(hide => menuText.includes(hide))) {
            menu.style.display = "none";
        }
    });
});
</script>';
?>
```

---

## **FIX 3: Update product_catalog.php to Use Header**
```
📁 FILE PATH: 
/var/www/html/custom/foodbankcrm/core/pages/product_catalog.php

📝 INSTRUCTIONS:
1. Open this file
2. Find the line that says: llxHeader(...);
   (Around line 10-20)
3. RIGHT AFTER llxHeader, add this line:

require_once __DIR__ . '/beneficiary_header.php';