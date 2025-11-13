<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Page d'administration pour la gestion des données orphelines
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
    $i--; $j--;
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/detailproduit/lib/detailproduit.lib.php');
dol_include_once('/detailproduit/class/commandedetdetails.class.php');

// Translations
$langs->loadLangs(array("admin", "detailproduit"));

// Security check
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

$error = 0;
$setupnotempty = 0;

// Include object class
$details_obj = new CommandeDetDetails($db);

if ($action == 'check') {
    $report = $details_obj->checkDataIntegrity();
    setEventMessages("Vérification terminée - " . (count($report['orphaned_details']) + count($report['orphaned_extrafields'])) . " problème(s) détecté(s)", null, $report['integrity_ok'] ? 'mesgs' : 'warnings');
}

if ($action == 'cleanup') {
    $stats = $details_obj->cleanupOrphanedData();
    $message = "Nettoyage terminé - " . $stats['orphaned_details_deleted'] . " détails et " . $stats['orphaned_extrafields_cleaned'] . " extrafields nettoyés";
    if (count($stats['errors']) > 0) {
        setEventMessages($message . " (avec erreurs)", $stats['errors'], 'warnings');
    } else {
        setEventMessages($message, null, 'mesgs');
    }
}

/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("DetailproduitSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("DetailproduitSetup"), $linkback, 'title_setup');

$head = detailproduitAdminPrepareHead();

print dol_get_fiche_head($head, 'cleanup', $langs->trans("DetailproduitSetup"), -1, "detailproduit");

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="20">'.$langs->trans("Status").'</td>';
print '<td align="center" width="100">'.$langs->trans("Action").'</td>';
print '</tr>';

// Vérification de l'intégrité
$report = $details_obj->checkDataIntegrity();
print '<tr class="oddeven">';
print '<td>';
print '<strong>Intégrité des données</strong><br>';
print '<span class="opacitymedium">Vérifier la cohérence entre les détails et les lignes de commande</span>';
print '</td>';
print '<td align="center">';
if ($report['integrity_ok']) {
    print img_picto('', 'tick.png').' <span class="ok">OK</span>';
} else {
    $nb_problems = count($report['orphaned_details']) + count($report['orphaned_extrafields']);
    print img_picto('', 'warning.png').' <span class="warning">'.$nb_problems.' problème(s)</span>';
}
print '</td>';
print '<td align="center">';
print '<a href="'.$_SERVER["PHP_SELF"].'?action=check" class="button">';
print $langs->trans("Check");
print '</a>';
print '</td>';
print '</tr>';

// Statistiques générales
print '<tr class="oddeven">';
print '<td>';
print '<strong>Statistiques</strong><br>';
print '<span class="opacitymedium">';
print 'Total détails: '.$report['total_details'].'<br>';
print 'Extrafields actifs: '.$report['total_extrafields_with_detail'].'<br>';
print 'Détails orphelins: '.count($report['orphaned_details']).'<br>';
print 'Extrafields orphelins: '.count($report['orphaned_extrafields']);
print '</span>';
print '</td>';
print '<td align="center">';
print img_picto('', 'info.png');
print '</td>';
print '<td align="center">-</td>';
print '</tr>';

// Nettoyage automatique
print '<tr class="oddeven">';
print '<td>';
print '<strong>Nettoyage automatique</strong><br>';
print '<span class="opacitymedium">Supprimer les données orphelines (détails sans ligne de commande correspondante)</span>';
print '</td>';
print '<td align="center">';
if ($report['integrity_ok']) {
    print img_picto('', 'tick.png').' <span class="ok">Aucun nettoyage nécessaire</span>';
} else {
    print img_picto('', 'warning.png').' <span class="warning">Nettoyage recommandé</span>';
}
print '</td>';
print '<td align="center">';
if (!$report['integrity_ok']) {
    print '<a href="'.$_SERVER["PHP_SELF"].'?action=cleanup" class="button" onclick="return confirm(\'Êtes-vous sûr de vouloir nettoyer les données orphelines ?\');">';
    print 'Nettoyer';
    print '</a>';
} else {
    print '<span class="opacitymedium">-</span>';
}
print '</td>';
print '</tr>';

// Trigger automatique
$trigger_file = dol_buildpath('/detailproduit/core/triggers/interface_99_modDetailproduit_Detailproduittrigger.class.php');
$trigger_exists = file_exists($trigger_file);

print '<tr class="oddeven">';
print '<td>';
print '<strong>Trigger de nettoyage automatique</strong><br>';
print '<span class="opacitymedium">Suppression automatique des détails lors de la suppression d\'une ligne de commande</span>';
print '</td>';
print '<td align="center">';
if ($trigger_exists) {
    print img_picto('', 'tick.png').' <span class="ok">Installé</span>';
} else {
    print img_picto('', 'error.png').' <span class="error">Non trouvé</span>';
}
print '</td>';
print '<td align="center">';
if ($trigger_exists) {
    print '<span class="ok">Actif</span>';
} else {
    print '<span class="error">Manquant</span>';
}
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

if (!$report['integrity_ok']) {
    print '<br>';
    
    print '<div class="info">';
    print '<div style="font-weight: bold; margin-bottom: 10px;">🔍 Détails des problèmes détectés :</div>';
    
    if (count($report['orphaned_details']) > 0) {
        print '<div style="margin-bottom: 15px;">';
        print '<strong>📋 Détails orphelins ('.count($report['orphaned_details']).'):</strong><br>';
        print '<div style="margin-left: 20px; font-family: monospace; font-size: 12px;">';
        foreach (array_slice($report['orphaned_details'], 0, 10) as $orphan) {
            print '• Détail ID '.$orphan['detail_id'].' → Ligne commandedet manquante ID '.$orphan['missing_commandedet_id'].'<br>';
        }
        if (count($report['orphaned_details']) > 10) {
            print '• ... et '.(count($report['orphaned_details']) - 10).' autre(s)<br>';
        }
        print '</div>';
        print '</div>';
    }
    
    if (count($report['orphaned_extrafields']) > 0) {
        print '<div style="margin-bottom: 15px;">';
        print '<strong>🏷️ Extrafields orphelins ('.count($report['orphaned_extrafields']).'):</strong><br>';
        print '<div style="margin-left: 20px; font-family: monospace; font-size: 12px;">';
        foreach (array_slice($report['orphaned_extrafields'], 0, 10) as $fk_object) {
            print '• Extrafield pour ligne commandedet manquante ID '.$fk_object.'<br>';
        }
        if (count($report['orphaned_extrafields']) > 10) {
            print '• ... et '.(count($report['orphaned_extrafields']) - 10).' autre(s)<br>';
        }
        print '</div>';
        print '</div>';
    }
    
    print '<div style="margin-top: 15px; padding: 10px; background: #ffeaa7; border-left: 4px solid #fdcb6e;">';
    print '<strong>💡 Recommandation :</strong> Ces données orphelines sont créées quand des lignes de commande sont supprimées sans que les détails associés soient nettoyés. ';
    print 'Le trigger automatique prévient ce problème pour les nouvelles suppressions. ';
    print 'Utilisez le bouton "Nettoyer" pour supprimer les données orphelines existantes.';
    print '</div>';
    
    print '</div>';
}

print '<br>';

print '<div class="tabsAction">';
print '<div class="inline-block divButAction">';
print '<a href="'.dol_buildpath('/detailproduit/cleanup_orphaned_data.php', 1).'?action=report" class="butAction" target="_blank">';
print 'Rapport détaillé complet';
print '</a>';
print '</div>';
print '</div>';

print dol_get_fiche_end();

// Page end
llxFooter();
$db->close();
