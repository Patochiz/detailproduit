<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Script de gestion des données orphelines du module detailproduit
 */

// Mode debug
$debug_mode = true;

function debug_log($message) {
    global $debug_mode;
    if ($debug_mode) {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    }
}

// Tentative d'inclusion de main.inc.php (multiple paths)
$res = 0;
$main_paths = array(
    __DIR__ . "/../../../main.inc.php",           // Standard: custom/module/
    __DIR__ . "/../../../../main.inc.php",        // Si un niveau de plus
    __DIR__ . "/../../main.inc.php",              // Si structure différente
);

foreach ($main_paths as $path) {
    if (file_exists($path)) {
        $res = @include_once $path;
        if ($res) {
            debug_log("main.inc.php inclus depuis: " . $path);
            break;
        }
    }
}

if (!$res) {
    die("❌ Impossible d'inclure main.inc.php\n");
}

// Vérifications
if (!isset($db)) {
    die("❌ Variable \$db non définie\n");
}

if (!isset($user)) {
    die("❌ Variable \$user non définie\n");
}

if (!isModEnabled('detailproduit')) {
    die("❌ Module detailproduit non activé\n");
}

// Inclusion de la classe
require_once __DIR__.'/class/commandedetdetails.class.php';

echo "=== GESTION DES DONNÉES ORPHELINES - MODULE DETAILPRODUIT ===\n\n";

// Récupérer l'action depuis la ligne de commande ou paramètre GET
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($argv[1])) {
    $action = $argv[1];
}

if (empty($action)) {
    echo "Usage:\n";
    echo "  php " . basename(__FILE__) . " [action]\n\n";
    echo "Actions disponibles:\n";
    echo "  check    - Vérifier l'intégrité des données (sans modification)\n";
    echo "  cleanup  - Nettoyer les données orphelines (avec suppression)\n";
    echo "  report   - Rapport détaillé des données\n\n";
    echo "Exemples:\n";
    echo "  php " . basename(__FILE__) . " check\n";
    echo "  php " . basename(__FILE__) . " cleanup\n";
    echo "  Ou via web: " . basename(__FILE__) . "?action=check\n\n";
    exit;
}

// Instancier la classe
$details_obj = new CommandeDetDetails($db);

switch ($action) {
    case 'check':
        echo "🔍 VÉRIFICATION DE L'INTÉGRITÉ DES DONNÉES\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $report = $details_obj->checkDataIntegrity();
        
        echo "📊 STATISTIQUES GÉNÉRALES:\n";
        echo "  - Total détails en base: " . $report['total_details'] . "\n";
        echo "  - Total extrafields avec détail: " . $report['total_extrafields_with_detail'] . "\n\n";
        
        echo "🔍 DONNÉES ORPHELINES:\n";
        echo "  - Détails orphelins: " . count($report['orphaned_details']) . "\n";
        echo "  - Extrafields orphelins: " . count($report['orphaned_extrafields']) . "\n\n";
        
        if ($report['integrity_ok']) {
            echo "✅ INTÉGRITÉ OK: Aucune donnée orpheline détectée!\n";
        } else {
            echo "⚠️ PROBLÈMES DÉTECTÉS:\n\n";
            
            if (count($report['orphaned_details']) > 0) {
                echo "📋 Détails orphelins:\n";
                foreach ($report['orphaned_details'] as $orphan) {
                    echo "  - Détail ID " . $orphan['detail_id'] . " → Ligne commandedet manquante ID " . $orphan['missing_commandedet_id'] . "\n";
                }
                echo "\n";
            }
            
            if (count($report['orphaned_extrafields']) > 0) {
                echo "🏷️ Extrafields orphelins:\n";
                foreach ($report['orphaned_extrafields'] as $fk_object) {
                    echo "  - Extrafield pour ligne commandedet manquante ID " . $fk_object . "\n";
                }
                echo "\n";
            }
            
            echo "💡 Pour nettoyer automatiquement: php " . basename(__FILE__) . " cleanup\n";
        }
        break;
        
    case 'cleanup':
        echo "🧹 NETTOYAGE DES DONNÉES ORPHELINES\n";
        echo str_repeat("=", 50) . "\n\n";
        
        // D'abord vérifier
        $report = $details_obj->checkDataIntegrity();
        
        echo "📊 AVANT NETTOYAGE:\n";
        echo "  - Détails orphelins: " . count($report['orphaned_details']) . "\n";
        echo "  - Extrafields orphelins: " . count($report['orphaned_extrafields']) . "\n\n";
        
        if ($report['integrity_ok']) {
            echo "✅ Aucun nettoyage nécessaire - Intégrité OK!\n";
        } else {
            echo "🧹 Lancement du nettoyage...\n\n";
            
            $stats = $details_obj->cleanupOrphanedData();
            
            echo "📊 RÉSULTATS DU NETTOYAGE:\n";
            echo "  - Détails orphelins trouvés: " . $stats['orphaned_details_found'] . "\n";
            echo "  - Détails orphelins supprimés: " . $stats['orphaned_details_deleted'] . "\n";
            echo "  - Extrafields orphelins trouvés: " . $stats['orphaned_extrafields_found'] . "\n";
            echo "  - Extrafields orphelins nettoyés: " . $stats['orphaned_extrafields_cleaned'] . "\n\n";
            
            if (count($stats['errors']) > 0) {
                echo "❌ ERREURS:\n";
                foreach ($stats['errors'] as $error) {
                    echo "  - " . $error . "\n";
                }
                echo "\n";
            } else {
                echo "✅ Nettoyage terminé avec succès!\n\n";
            }
            
            // Vérification après nettoyage
            echo "🔍 Vérification post-nettoyage...\n";
            $report_after = $details_obj->checkDataIntegrity();
            
            if ($report_after['integrity_ok']) {
                echo "✅ Intégrité restaurée!\n";
            } else {
                echo "⚠️ Problèmes restants - Relancer le nettoyage si nécessaire\n";
            }
        }
        break;
        
    case 'report':
        echo "📋 RAPPORT DÉTAILLÉ DES DONNÉES\n";
        echo str_repeat("=", 50) . "\n\n";
        
        // Rapport général
        $report = $details_obj->checkDataIntegrity();
        
        echo "📊 STATISTIQUES GÉNÉRALES:\n";
        echo "  - Total détails en base: " . $report['total_details'] . "\n";
        echo "  - Total extrafields avec détail: " . $report['total_extrafields_with_detail'] . "\n";
        echo "  - Intégrité: " . ($report['integrity_ok'] ? "✅ OK" : "⚠️ Problèmes") . "\n\n";
        
        // Statistiques par commande
        echo "📋 DÉTAILS PAR COMMANDE (avec données):\n";
        $sql = "SELECT c.ref, COUNT(d.rowid) as nb_details, SUM(d.pieces) as total_pieces";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commande c";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "commandedet cd ON cd.fk_commande = c.rowid";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "commandedet_details d ON d.fk_commandedet = cd.rowid";
        $sql .= " GROUP BY c.rowid, c.ref";
        $sql .= " ORDER BY nb_details DESC";
        $sql .= " LIMIT 10";
        
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                echo "  - Commande " . $obj->ref . ": " . $obj->nb_details . " détails, " . $obj->total_pieces . " pièces\n";
            }
            $db->free($resql);
        }
        echo "\n";
        
        // Statistiques des unités
        echo "📏 RÉPARTITION PAR UNITÉ:\n";
        $sql = "SELECT unit, COUNT(*) as nb_lignes, SUM(pieces) as total_pieces, SUM(total_value) as total_value";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet_details";
        $sql .= " GROUP BY unit ORDER BY total_value DESC";
        
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                echo "  - " . $obj->unit . ": " . $obj->nb_lignes . " lignes, " . $obj->total_pieces . " pièces, " . number_format($obj->total_value, 3) . " " . $obj->unit . "\n";
            }
            $db->free($resql);
        }
        echo "\n";
        
        // Données orphelines si il y en a
        if (!$report['integrity_ok']) {
            echo "⚠️ DONNÉES ORPHELINES DÉTECTÉES:\n";
            echo "  - " . count($report['orphaned_details']) . " détails orphelins\n";
            echo "  - " . count($report['orphaned_extrafields']) . " extrafields orphelins\n";
            echo "  💡 Utilisez 'cleanup' pour nettoyer\n\n";
        }
        
        echo "🔧 MAINTENANCE:\n";
        echo "  - Trigger automatique: " . (file_exists(__DIR__ . '/core/triggers/interface_99_modDetailproduit_Detailproduittrigger.class.php') ? "✅ Installé" : "❌ Non trouvé") . "\n";
        echo "  - Dernière vérification: " . date('Y-m-d H:i:s') . "\n";
        break;
        
    default:
        echo "❌ Action inconnue: " . $action . "\n";
        echo "Actions valides: check, cleanup, report\n";
        break;
}

echo "\n=== FIN ===\n";

?>
