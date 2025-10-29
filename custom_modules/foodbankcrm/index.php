<?php
// Simple proof-of-life page
require_once dirname(__DIR__) . '/../main.inc.php'; // from /custom/foodbankcrm to /var/www/html/main.inc.php
llxHeader();
print '<h2>Foodbank CRM</h2><p>Module is installed and the menu works.</p>';
print '<ul>
<li><a href="pages/beneficiaries.php">Beneficiaries</a></li>
<li><a href="pages/vendors.php">Vendors</a></li>
<li><a href="pages/donations.php">Donations</a></li>
<li><a href="pages/distributions.php">Distributions</a></li>
</ul>';
llxFooter();
