<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
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
 * \file    detailproduit/admin/setup.php
 * \ingroup detailproduit
 * \brief   Detailproduit setup page.
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
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user, $db;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/detailproduit.lib.php';
dol_include_once('/detailproduit/class/commandedetdetails.class.php');

// Translations
$langs->loadLangs(array("admin", "detailproduit@detailproduit"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('detailproduitsetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$error = 0;

// Actions
if ($action == 'test_database') {
    // Test de la base de données
    $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."commandedet_details";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        setEventMessages($langs->trans('Database table exists with ').$obj->nb.' records', null, 'mesgs');
    } else {
        setEventMessages($langs->trans('Database table does not exist or error: ').$db->lasterror(), null, 'errors');
    }
}

if ($action == 'purge_details') {
    if (GETPOST('confirm', 'alpha') == 'yes') {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet_details";
        $resql = $db->query($sql);
        if ($resql) {
            setEventMessages($langs->trans('All details have been deleted'), null, 'mesgs');
        } else {
            setEventMessages($langs->trans('Error deleting details: ').$db->lasterror(), null, 'errors');
        }
    }
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "DetailproduitSetup";

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-detailproduit page-admin');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'
	.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = detailproduitAdminPrepareHead();

print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "detailproduit@detailproduit");

// Setup page goes here
print '<div class="info">';
print img_picto('', 'info').' '.$langs->trans("Module allows managing dimension details for order lines");
print '</div>';

print '<br>';

// Module status
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td class="center">'.$langs->trans("Value").'</td>';
print '<td class="center">'.$langs->trans("Action").'</td>';
print '</tr>';

// Module status
print '<tr class="oddeven">';
print '<td>'.$langs->trans("Module status").'</td>';
print '<td class="center">';
if (isModEnabled('detailproduit')) {
    print '<span class="badge badge-status4 badge-status">'.$langs->trans("Enabled").'</span>';
} else {
    print '<span class="badge badge-status8 badge-status">'.$langs->trans("Disabled").'</span>';
}
print '</td>';
print '<td class="center">-</td>';
print '</tr>';

// Database status
print '<tr class="oddeven">';
print '<td>'.$langs->trans("Database table").'</td>';
print '<td class="center">';
$sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."commandedet_details'";
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    print '<span class="badge badge-status4 badge-status">'.$langs->trans("Available").'</span>';
} else {
    print '<span class="badge badge-status8 badge-status">'.$langs->trans("NotAvailable").'</span>';
}
print '</td>';
print '<td class="center">';
print '<a class="button smallpaddingimp" href="'.$_SERVER["PHP_SELF"].'?action=test_database&token='.newToken().'">'.$langs->trans("Test").'</a>';
print '</td>';
print '</tr>';

// Number of details records
print '<tr class="oddeven">';
print '<td>'.$langs->trans("Number of details records").'</td>';
print '<td class="center">';
$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."commandedet_details";
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    print '<strong>'.$obj->nb.'</strong>';
} else {
    print '<span class="error">'.$langs->trans("Error").'</span>';
}
print '</td>';
print '<td class="center">';
print '<a class="button smallpaddingimp button-delete" href="'.$_SERVER["PHP_SELF"].'?action=purge_details&confirm=yes&token='.newToken().'" onclick="return confirm(\'Are you sure you want to delete all details records?\')">'.$langs->trans("Purge").'</a>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Permissions summary
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Permission").'</td>';
print '<td class="center">'.$langs->trans("Current user").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Read details").'</td>';
print '<td class="center">';
if ($user->hasRight('detailproduit', 'details', 'read')) {
    print '<span class="badge badge-status4 badge-status">'.$langs->trans("Yes").'</span>';
} else {
    print '<span class="badge badge-status8 badge-status">'.$langs->trans("No").'</span>';
}
print '</td>';
print '<td>'.$langs->trans("PermissionDetailsRead").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Write details").'</td>';
print '<td class="center">';
if ($user->hasRight('detailproduit', 'details', 'write')) {
    print '<span class="badge badge-status4 badge-status">'.$langs->trans("Yes").'</span>';
} else {
    print '<span class="badge badge-status8 badge-status">'.$langs->trans("No").'</span>';
}
print '</td>';
print '<td>'.$langs->trans("PermissionDetailsWrite").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Delete details").'</td>';
print '<td class="center">';
if ($user->hasRight('detailproduit', 'details', 'delete')) {
    print '<span class="badge badge-status4 badge-status">'.$langs->trans("Yes").'</span>';
} else {
    print '<span class="badge badge-status8 badge-status">'.$langs->trans("No").'</span>';
}
print '</td>';
print '<td>'.$langs->trans("PermissionDetailsDelete").'</td>';
print '</tr>';

print '</table>';
print '</div>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
