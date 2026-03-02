<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2025 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    foodbankcrm/admin/about.php
 * \ingroup foodbankcrm
 * \brief   About page of module FoodbankCRM.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/foodbankcrm.lib.php';

// Translations
$langs->loadLangs(array("errors", "admin", "foodbankcrm@foodbankcrm"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "FoodbankCRMAbout";

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-foodbankcrm page-admin_about');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = foodbankcrmAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans($page_name), 0, 'foodbankcrm@foodbankcrm');

dol_include_once('/foodbankcrm/core/modules/modFoodbankcrm.class.php');
$tmpmodule = new modFoodbankcrm($db);

print '<div style="max-width:720px; font-family: \'Segoe UI\', sans-serif; line-height:1.7;">';

print '<div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">';
print '<div style="font-size:52px;">🥦</div>';
print '<div>';
print '<h2 style="margin:0 0 4px; font-size:22px; color:#0f766e;">FoodbankCRM</h2>';
print '<span style="background:#d1fae5; color:#065f46; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700;">v'.$tmpmodule->version.'</span>';
print '</div></div>';

print '<p style="color:#334155; font-size:15px; margin-bottom:20px;">A custom Dolibarr module designed to manage foodbank operations — covering subscriber registration, vendor management, package catalogues, cart & order processing, subscription billing, and admin oversight — all within a unified CRM platform.</p>';

print '<table class="noborder" style="width:100%;">';
print '<tr class="liste_titre"><th colspan="2">Module Information</th></tr>';
$rows = [
	['Author',       htmlspecialchars($tmpmodule->editor_name)],
	['Version',      htmlspecialchars($tmpmodule->version)],
	['Module ID',    (int)$tmpmodule->numero],
	['PHP Required', implode('.', $tmpmodule->phpmin).' or higher'],
	['Dolibarr',     '15.0 or higher'],
	['License',      'GNU GPL v3'],
];
foreach ($rows as [$label, $value]) {
	print '<tr class="oddeven"><td style="width:200px; font-weight:600;">'.$label.'</td><td>'.$value.'</td></tr>';
}
print '</table>';

print '<h3 style="margin:28px 0 12px; color:#0f766e;">Key Features</h3>';
$features = [
	'🧑‍🤝‍🧑 Beneficiary / Subscriber registration and profile management',
	'🏪 Vendor registration with admin approval workflow',
	'📦 Product & package catalogue with cart system',
	'📋 Order lifecycle: Pending → Prepared → Bundled → In Transit → Delivered',
	'💳 Paystack payment integration for subscriptions and orders',
	'🔑 Role-based access control (Admin / Vendor / Beneficiary)',
	'📊 Admin dashboard with live statistics and order management',
	'🔔 Dolibarr hook & trigger integration',
];
print '<ul style="color:#334155; padding-left:20px; margin:0;">';
foreach ($features as $f) {
	print '<li style="margin-bottom:8px;">'.$f.'</li>';
}
print '</ul>';

print '</div>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
