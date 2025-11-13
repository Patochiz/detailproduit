<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Script de test des extrafields detailjson et detail
 */

// Tentative d'inclusion de main.inc.php
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Vérifications de sécurité
if (!$user->admin) {
    accessforbidden('Seuls les administrateurs peuvent exécuter ce script de test');
}

if (!isModEnabled('detailproduit')) {
    dol_print_error($db, 'Module detailproduit non activé');
}

require_once DOL_DOCUMENT_ROOT.'/custom/detailproduit/class/commandedetdetails.class.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test extrafields detailproduit</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #f0fff0; padding: 10px; border-left: 5px solid green; margin: 10px 0; }
        .error { color: red; background: #ffe4e1; padding: 10px; border-left: 5px solid red; margin: 10px 0; }
        .info { color: blue; background: #f0f8ff; padding: 10px; border-left: 5px solid blue; margin: 10px 0; }
        .warning { color: orange; background: #fff8dc; padding: 10px; border-left: 5px solid orange; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>

<h1>🧪 Test des extrafields detailproduit</h1>

<?php

$action = GETPOST('action', 'alpha');

if ($action === 'test_save') {
    $commandedet_id = GETPOST('commandedet_id', 'int');
    
    if (!$commandedet_id) {
        echo '<div class="error">❌ Veuillez spécifier un ID de ligne de commande valide.</div>';
    } else {
        echo '<h2>🔬 Test de sauvegarde pour la ligne ' . $commandedet_id . '</h2>';
        
        // Vérifier que la ligne de commande existe
        $sql = "SELECT rowid, qty, label FROM " . MAIN_DB_PREFIX . "commandedet WHERE rowid = " . ((int) $commandedet_id);
        $resql = $db->query($sql);
        
        if (!$resql || !$db->num_rows($resql)) {
            echo '<div class="error">❌ Ligne de commande ' . $commandedet_id . ' non trouvée.</div>';
        } else {
            $obj = $db->fetch_object($resql);
            echo '<div class="info">ℹ️ Ligne trouvée : "' . htmlspecialchars($obj->label) . '" (quantité: ' . $obj->qty . ')</div>';
            
            // Créer des données de test
            $test_details = array(
                array(
                    'pieces' => 20,
                    'longueur' => 3000,
                    'largeur' => 300,
                    'total_value' => 1.8, // 20 × 3000/1000 × 300/1000
                    'unit' => 'm²',
                    'description' => 'Test ABD'
                ),
                array(
                    'pieces' => 10,
                    'longueur' => 2000,
                    'largeur' => 300,
                    'total_value' => 0.6, // 10 × 2000/1000 × 300/1000  
                    'unit' => 'm²',
                    'description' => 'Test DEF'
                ),
                array(
                    'pieces' => 5,
                    'longueur' => 1500,
                    'largeur' => null,
                    'total_value' => 7.5, // 5 × 1500/1000
                    'unit' => 'ml',
                    'description' => 'Test linéaire'
                )
            );
            
            echo '<h3>📝 Données de test</h3>';
            echo '<pre>' . print_r($test_details, true) . '</pre>';
            
            // Tester la sauvegarde
            $details_obj = new CommandeDetDetails($db);
            $result = $details_obj->saveDetailsForLine($commandedet_id, $test_details, $user);
            
            if ($result > 0) {
                echo '<div class="success">✅ Sauvegarde réussie !</div>';
                
                // Vérifier le contenu des extrafields
                $sql = "SELECT detailjson, detail FROM " . MAIN_DB_PREFIX . "commandedet_extrafields WHERE fk_object = " . ((int) $commandedet_id);
                $resql = $db->query($sql);
                
                if ($resql && $db->num_rows($resql)) {
                    $extrafield = $db->fetch_object($resql);
                    
                    echo '<h3>📊 Contenu extrafield "detailjson"</h3>';
                    echo '<pre>' . htmlspecialchars($extrafield->detailjson) . '</pre>';
                    
                    echo '<h3>📝 Contenu extrafield "detail" (format d\'affichage)</h3>';
                    echo '<div style="border: 1px solid #ddd; padding: 10px; background: white;">';
                    echo $extrafield->detail; // Affichage HTML direct
                    echo '</div>';
                    
                    // Tester la récupération
                    echo '<h3>🔍 Test de récupération</h3>';
                    $retrieved_details = $details_obj->getDetailsForLine($commandedet_id);
                    
                    if ($retrieved_details !== -1) {
                        echo '<div class="success">✅ Récupération réussie : ' . count($retrieved_details) . ' lignes</div>';
                        echo '<table>';
                        echo '<tr><th>Pièces</th><th>Longueur</th><th>Largeur</th><th>Total</th><th>Unité</th><th>Description</th></tr>';
                        
                        foreach ($retrieved_details as $detail) {
                            echo '<tr>';
                            echo '<td>' . $detail['pieces'] . '</td>';
                            echo '<td>' . ($detail['longueur'] ?: '-') . '</td>';
                            echo '<td>' . ($detail['largeur'] ?: '-') . '</td>';
                            echo '<td>' . $detail['total_value'] . '</td>';
                            echo '<td>' . $detail['unit'] . '</td>';
                            echo '<td>' . htmlspecialchars($detail['description']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                        
                        // Tester le résumé
                        echo '<h3>📋 Test du résumé</h3>';
                        $summary = $details_obj->getSummaryForDisplay($commandedet_id);
                        if ($summary) {
                            echo '<div class="info">📊 Résumé généré : ' . htmlspecialchars($summary) . '</div>';
                        }
                        
                    } else {
                        echo '<div class="error">❌ Erreur lors de la récupération</div>';
                        if (!empty($details_obj->errors)) {
                            echo '<ul>';
                            foreach ($details_obj->errors as $error) {
                                echo '<li>' . htmlspecialchars($error) . '</li>';
                            }
                            echo '</ul>';
                        }
                    }
                    
                } else {
                    echo '<div class="error">❌ Extrafields non trouvés après sauvegarde</div>';
                }
                
            } else {
                echo '<div class="error">❌ Erreur lors de la sauvegarde</div>';
                if (!empty($details_obj->errors)) {
                    echo '<ul>';
                    foreach ($details_obj->errors as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                }
            }
        }
    }
    
} elseif ($action === 'check_extrafields') {
    echo '<h2>🔍 Vérification des extrafields existants</h2>';
    
    // Vérifier si la table extrafields existe et a les bonnes colonnes
    $sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "commandedet_extrafields";
    $resql = $db->query($sql);
    
    if (!$resql) {
        echo '<div class="error">❌ Table commandedet_extrafields non trouvée : ' . $db->lasterror() . '</div>';
    } else {
        echo '<div class="success">✅ Table commandedet_extrafields trouvée</div>';
        
        $columns = array();
        while ($obj = $db->fetch_object($resql)) {
            $columns[] = $obj->Field;
        }
        
        $required_columns = array('fk_object', 'detailjson', 'detail');
        $missing_columns = array();
        
        foreach ($required_columns as $col) {
            if (!in_array($col, $columns)) {
                $missing_columns[] = $col;
            }
        }
        
        if (count($missing_columns) > 0) {
            echo '<div class="error">❌ Colonnes manquantes : ' . implode(', ', $missing_columns) . '</div>';
            echo '<div class="warning">⚠️ Vous devez créer les extrafields "detailjson" et "detail" depuis l\'interface d\'administration de Dolibarr.</div>';
        } else {
            echo '<div class="success">✅ Toutes les colonnes requises sont présentes</div>';
            
            // Compter les extrafields existants
            $sql = "SELECT COUNT(*) as total_all, ";
            $sql .= "SUM(CASE WHEN detailjson IS NOT NULL THEN 1 ELSE 0 END) as total_json, ";
            $sql .= "SUM(CASE WHEN detail IS NOT NULL THEN 1 ELSE 0 END) as total_detail ";
            $sql .= "FROM " . MAIN_DB_PREFIX . "commandedet_extrafields";
            
            $resql = $db->query($sql);
            if ($resql) {
                $stats = $db->fetch_object($resql);
                echo '<div class="info">';
                echo '<h3>📊 Statistiques</h3>';
                echo '<ul>';
                echo '<li>Total extrafields : ' . $stats->total_all . '</li>';
                echo '<li>Avec detailjson : ' . $stats->total_json . '</li>';
                echo '<li>Avec detail : ' . $stats->total_detail . '</li>';
                echo '</ul>';
                echo '</div>';
            }
        }
        
        echo '<h3>📋 Colonnes existantes</h3>';
        echo '<ul>';
        foreach ($columns as $col) {
            $status = in_array($col, $required_columns) ? '✅' : 'ℹ️';
            echo '<li>' . $status . ' ' . $col . '</li>';
        }
        echo '</ul>';
    }
    
} elseif ($action === 'list_commandedet') {
    echo '<h2>📋 Lignes de commande disponibles pour test</h2>';
    
    // Lister quelques lignes de commande récentes
    $sql = "SELECT cd.rowid, cd.fk_commande, cd.qty, cd.label, c.ref as commande_ref";
    $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet cd";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande c ON c.rowid = cd.fk_commande";
    $sql .= " ORDER BY cd.rowid DESC LIMIT 20";
    
    $resql = $db->query($sql);
    if ($resql) {
        if ($db->num_rows($resql) > 0) {
            echo '<table>';
            echo '<tr><th>ID ligne</th><th>Commande</th><th>Quantité</th><th>Libellé</th><th>Action</th></tr>';
            
            while ($obj = $db->fetch_object($resql)) {
                echo '<tr>';
                echo '<td>' . $obj->rowid . '</td>';
                echo '<td>' . $obj->commande_ref . '</td>';
                echo '<td>' . $obj->qty . '</td>';
                echo '<td>' . htmlspecialchars($obj->label) . '</td>';
                echo '<td><a href="?action=test_save&commandedet_id=' . $obj->rowid . '&token=' . newToken() . '">🧪 Tester</a></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<div class="warning">⚠️ Aucune ligne de commande trouvée.</div>';
        }
    } else {
        echo '<div class="error">❌ Erreur : ' . $db->lasterror() . '</div>';
    }
    
} else {
    // Menu principal
    echo '<div class="info">';
    echo '<h2>ℹ️ À propos de ce test</h2>';
    echo '<p>Ce script permet de tester le bon fonctionnement des extrafields <code>detailjson</code> et <code>detail</code> pour le module detailproduit.</p>';
    echo '<p><strong>Prérequis :</strong> Les extrafields doivent être créés depuis l\'interface d\'administration de Dolibarr :</p>';
    echo '<ul>';
    echo '<li><code>detailjson</code> : Type "Text long" pour stocker les données JSON</li>';
    echo '<li><code>detail</code> : Type "HTML" pour l\'affichage formaté</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<h2>🚀 Actions disponibles</h2>';
    echo '<p>';
    echo '<a href="?action=check_extrafields&token=' . newToken() . '" style="background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; margin: 5px;">🔍 Vérifier les extrafields</a>';
    echo '<a href="?action=list_commandedet&token=' . newToken() . '" style="background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; margin: 5px;">📋 Lister les lignes de commande</a>';
    echo '</p>';
}

?>

</body>
</html>
