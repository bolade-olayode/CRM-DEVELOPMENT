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
 * \file    foodbankcrm/admin/setup.php
 * \ingroup foodbankcrm
 * \brief   FoodbankCRM setup page.
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

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/foodbankcrm.lib.php';

// Translations
$langs->loadLangs(array("admin", "foodbankcrm@foodbankcrm"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('foodbankcrmsetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');



$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);


// Enter here all parameters in your setup page

// --- Paystack Payment Configuration ---
$formSetup->newItem('PaystackSettings')->setAsTitle();

$item = $formSetup->newItem('FOODBANK_PAYSTACK_PUBLIC_KEY');
$item->nameText = 'Paystack Public Key';
$item->helpText = 'Your Paystack public API key (starts with pk_test_ or pk_live_). Found in your Paystack Dashboard under Settings > API Keys.';
$item->cssClass = 'minwidth500';
$item->fieldInputType = 'password';

$item = $formSetup->newItem('FOODBANK_PAYSTACK_SECRET_KEY');
$item->nameText = 'Paystack Secret Key';
$item->helpText = 'Your Paystack secret API key (starts with sk_test_ or sk_live_). Keep this confidential. Found in your Paystack Dashboard under Settings > API Keys.';
$item->cssClass = 'minwidth500';
$item->fieldInputType = 'password';

// --- Email & Notification Configuration ---
$formSetup->newItem('EmailSettings')->setAsTitle();

$item = $formSetup->newItem('FOODBANK_ADMIN_NOTIFICATION_EMAIL');
$item->nameText = 'Admin Notification Email';
$item->helpText = 'Email address that receives alerts when a new subscriber or vendor registers. Leave blank to use the system FROM address configured in Admin → Setup → Email.';
$item->cssClass = 'minwidth500';


$setupnotempty += count($formSetup->items);




/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';




/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "FoodbankCRMSetup";

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-foodbankcrm page-admin');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = foodbankcrmAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "foodbankcrm@foodbankcrm");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("FoodbankCRMSetupPage").'</span><br><br>';


if ($action == 'edit') {
	print $formSetup->generateOutput(true);
	print '<br>';
} elseif (!empty($formSetup->items)) {
	// Custom masked view — never show raw key values on screen
	$pub_key = getDolGlobalString('FOODBANK_PAYSTACK_PUBLIC_KEY');
	$sec_key = getDolGlobalString('FOODBANK_PAYSTACK_SECRET_KEY');

	// Mask helper: show prefix + bullets, flag if unconfigured
	$maskKey = function($key) {
		if (empty(trim($key))) {
			return '<span style="color:#dc3545;font-weight:600;">&#10006; Not configured</span>';
		}
		$prefix = htmlspecialchars(substr($key, 0, 12));
		return '<code style="background:#f1f5f9;padding:3px 8px;border-radius:4px;font-size:13px;letter-spacing:1px;">'
			. $prefix . str_repeat('•', 20)
			. '</code> <span style="color:#64748b;font-size:12px;">(hidden for security)</span>';
	};

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th style="width:250px;">Setting</th>';
	print '<th>Value</th>';
	print '</tr>';
	print '<tr class="oddeven">';
	print '<td><strong>Paystack Public Key</strong><br><small style="color:#94a3b8;">pk_test_... or pk_live_...</small></td>';
	print '<td>'.$maskKey($pub_key).'</td>';
	print '</tr>';
	print '<tr class="oddeven">';
	print '<td><strong>Paystack Secret Key</strong><br><small style="color:#94a3b8;">sk_test_... or sk_live_... — keep confidential</small></td>';
	print '<td>'.$maskKey($sec_key).'</td>';
	print '</tr>';
	// Admin notification email (not sensitive — shown as plain text)
	$admin_notif_email = getDolGlobalString('FOODBANK_ADMIN_NOTIFICATION_EMAIL');
	print '<tr class="oddeven">';
	print '<td><strong>Admin Notification Email</strong><br><small style="color:#94a3b8;">Receives new registration alerts</small></td>';
	print '<td>';
	if (empty(trim($admin_notif_email))) {
		print '<span style="color:#64748b;font-size:13px;">Not set &mdash; using system FROM address</span>';
	} else {
		print '<code style="background:#f1f5f9;padding:3px 8px;border-radius:4px;font-size:13px;">'.htmlspecialchars($admin_notif_email).'</code>';
	}
	print '</td>';
	print '</tr>';
	print '</table>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.basename(__FILE__).'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	print '</div>';
} else {
	print '<br>'.$langs->trans("NothingToSetup");
}



// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
