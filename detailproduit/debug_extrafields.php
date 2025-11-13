<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Script de débogage pour les extrafields detailproduit
 */

// Tentative d'inclusion de main.inc.php
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Vérifications de sécurité
if (!$user->admin) {
    accessforbidden('Seuls les administrateurs peuvent exécuter ce script de débogage');
}

require_once DOL_DOCUMENT_ROOT.'/custom/detailproduit/class/commandedetdetails.class.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Débogage extrafields detailproduit</title>
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
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .btn { background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; margin: 5px; display: inline-block; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>

<h1>🔧 Débogage extrafields detailproduit</h1>

<?php

$action = GETPOST('action', 'alpha');
$commandedet_id = GETPOST('commandedet_id', 'int');

if ($action === 'debug_save') {
    if (!$commandedet_id) {
        echo '<div class="error">❌ Veuillez spécifier un ID de ligne de commande.</div>';
    } else {
        echo '<h2>🔬 Débogage détaillé pour la ligne ' . $commandedet_id . '</h2>';
        
        // Vérifier que la ligne de commande existe
        $sql = "SELECT rowid, qty, label FROM " . MAIN_DB_PREFIX . "commandedet WHERE rowid = " . ((int) $commandedet_id);
        $resql = $db->query($sql);
        
        if (!$resql || !$db->num_rows($resql)) {
            echo '<div class="error">❌ Ligne de commande ' . $commandedet_id . ' non trouvée.</div>';
        } else {
            $obj = $db->fetch_object($resql);
            echo '<div class="info">ℹ️ Ligne trouvée : "' . htmlspecialchars($obj->label) . '" (quantité actuelle: ' . $obj->qty . ')</div>';
            
            // Créer des données de test détaillées
            $test_details = array(
                array(
                    'pieces' => 15,
                    'longueur' => 2500,
                    'largeur' => 400,
                    'total_value' => 1.5, // 15 × 2500/1000 × 400/1000 = 1.5
                    'unit' => 'm²',
                    'description' => 'Debug Test 1'
                ),
                array(
                    'pieces' => 8,
                    'longueur' => 3000,
                    'largeur' => null,
                    'total_value' => 24.0, // 8 × 3000/1000 = 24
                    'unit' => 'ml',
                    'description' => 'Debug Test 2'
                )
            );
            
            echo '<h3>📝 Données de test pour débogage</h3>';
            echo '<pre>' . print_r($test_details, true) . '</pre>';
            
            // Tester étape par étape
            $details_obj = new CommandeDetDetails($db);
            
            // Étape 1 : Test de génération du format d'affichage
            echo '<h3>🔧 Étape 1 : Test génération format d\'affichage</h3>';
            
            // Utiliser la réflexion pour accéder à la méthode privée
            $reflection = new ReflectionClass($details_obj);
            $generateMethod = $reflection->getMethod('generateFormattedDetail');
            $generateMethod->setAccessible(true);
            
            try {
                $formatted_detail = $generateMethod->invokeArgs($details_obj, array($test_details));
                echo '<div class="success">✅ Format d\'affichage généré</div>';
                echo '<div class="info">';
                echo '<h4>📝 Format généré :</h4>';
                echo '<p><strong>Brut :</strong> <code>' . htmlspecialchars($formatted_detail) . '</code></p>';
                echo '<p><strong>Rendu HTML :</strong></p>';
                echo '<div style="border: 1px solid #ddd; padding: 10px; background: white;">' . $formatted_detail . '</div>';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="error">❌ Erreur génération format : ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // Étape 2 : Test de sauvegarde JSON
            echo '<h3>💾 Étape 2 : Test sauvegarde JSON</h3>';
            
            $json_data = json_encode($test_details, JSON_UNESCAPED_UNICODE);
            echo '<div class="info">';
            echo '<h4>📊 JSON généré :</h4>';
            echo '<pre>' . htmlspecialchars($json_data) . '</pre>';
            echo '</div>';
            
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<div class="success">✅ JSON valide</div>';
            } else {
                echo '<div class="error">❌ JSON invalide : ' . json_last_error_msg() . '</div>';
            }
            
            // Étape 3 : Test de sauvegarde complète
            echo '<h3>💾 Étape 3 : Test sauvegarde complète</h3>';
            
            $result = $details_obj->saveDetailsForLine($commandedet_id, $test_details, $user);
            
            if ($result > 0) {
                echo '<div class="success">✅ Sauvegarde réussie</div>';
                
                // Vérifier le contenu exact en base
                echo '<h4>🔍 Vérification en base de données</h4>';
                
                $sql = "SELECT detailjson, detail FROM " . MAIN_DB_PREFIX . "commandedet_extrafields WHERE fk_object = " . ((int) $commandedet_id);
                $resql = $db->query($sql);
                
                if ($resql && $db->num_rows($resql)) {
                    $extrafield = $db->fetch_object($resql);
                    
                    echo '<div class="info">';
                    echo '<h5>📊 Contenu detailjson en base :</h5>';
                    echo '<pre>' . htmlspecialchars($extrafield->detailjson) . '</pre>';
                    echo '</div>';
                    
                    echo '<div class="info">';
                    echo '<h5>📝 Contenu detail en base :</h5>';
                    if (empty($extrafield->detail)) {
                        echo '<div class="error">❌ Le champ detail est VIDE en base !</div>';
                        
                        // Diagnostiquer pourquoi
                        echo '<h6>🔍 Diagnostic :</h6>';
                        
                        // Test manuel de mise à jour
                        $manual_format = "TEST MANUEL : 15 x 2500 x 400 (1.50 m²) Debug<br>8 x 3000 (24.00 ml) Debug";
                        
                        $sql_update = "UPDATE " . MAIN_DB_PREFIX . "commandedet_extrafields";
                        $sql_update .= " SET detail = '" . $db->escape($manual_format) . "'";
                        $sql_update .= " WHERE fk_object = " . ((int) $commandedet_id);
                        
                        $resql_update = $db->query($sql_update);
                        
                        if ($resql_update) {
                            echo '<div class="success">✅ Test de mise à jour manuelle réussi</div>';
                            echo '<div class="warning">⚠️ Le problème vient de la génération automatique du format</div>';
                        } else {
                            echo '<div class="error">❌ Échec mise à jour manuelle : ' . $db->lasterror() . '</div>';
                        }
                        
                    } else {
                        echo '<p><strong>Brut :</strong> <code>' . htmlspecialchars($extrafield->detail) . '</code></p>';
                        echo '<p><strong>Rendu HTML :</strong></p>';
                        echo '<div style="border: 1px solid #ddd; padding: 10px; background: white;">' . $extrafield->detail . '</div>';
                        echo '<div class="success">✅ Le champ detail est correctement rempli</div>';
                    }
                } else {
                    echo '<div class="error">❌ Extrafields non trouvés après sauvegarde</div>';
                }
                
                // Étape 4 : Test de récupération
                echo '<h4>🔄 Test de récupération</h4>';
                
                $retrieved_details = $details_obj->getDetailsForLine($commandedet_id);
                
                if ($retrieved_details !== -1) {
                    echo '<div class="success">✅ Récupération réussie : ' . count($retrieved_details) . ' lignes</div>';
                    echo '<pre>' . print_r($retrieved_details, true) . '</pre>';
                } else {
                    echo '<div class="error">❌ Erreur lors de la récupération</div>';
                }
                
                // Étape 5 : Test de calcul automatique de quantité
                echo '<h4>🧮 Test calcul automatique de quantité</h4>';
                
                $totals = $details_obj->getTotalsByUnit($commandedet_id);
                if ($totals !== -1) {
                    echo '<div class="info">';
                    echo '<h5>📊 Totaux calculés :</h5>';
                    echo '<pre>' . print_r($totals, true) . '</pre>';
                    
                    // Déterminer l'unité principale et calculer la quantité totale
                    $main_unit = 'm²';
                    $max_value = 0;
                    
                    foreach ($totals as $unit => $data) {
                        if ($data['total_value'] > $max_value) {
                            $max_value = $data['total_value'];
                            $main_unit = $unit;
                        }
                    }
                    
                    $calculated_quantity = $totals[$main_unit]['total_value'];
                    
                    echo '<p><strong>Unité principale :</strong> ' . $main_unit . '</p>';
                    echo '<p><strong>Quantité calculée :</strong> ' . $calculated_quantity . '</p>';
                    echo '<p><strong>Quantité actuelle en base :</strong> ' . $obj->qty . '</p>';
                    
                    if (abs($calculated_quantity - $obj->qty) > 0.01) {
                        echo '<div class="warning">⚠️ La quantité en base ne correspond pas au calcul</div>';
                        
                        // Test de mise à jour de quantité
                        $update_result = $details_obj->updateCommandLineQuantity($commandedet_id, $calculated_quantity, $main_unit);
                        
                        if ($update_result > 0) {
                            echo '<div class="success">✅ Quantité mise à jour manuellement</div>';
                        } else {
                            echo '<div class="error">❌ Échec mise à jour quantité</div>';
                        }
                    } else {
                        echo '<div class="success">✅ Quantité cohérente</div>';
                    }
                    
                    echo '</div>';
                } else {
                    echo '<div class="error">❌ Erreur calcul des totaux</div>';
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
    
} elseif ($action === 'list_lines') {
    echo '<h2>📋 Lignes de commande avec extrafields</h2>';
    
    $sql = "SELECT cd.rowid, cd.fk_commande, cd.qty, cd.label, c.ref as commande_ref, ef.detailjson, ef.detail";
    $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet cd";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande c ON c.rowid = cd.fk_commande";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet_extrafields ef ON ef.fk_object = cd.rowid";
    $sql .= " WHERE ef.detailjson IS NOT NULL";
    $sql .= " ORDER BY cd.rowid DESC LIMIT 10";
    
    $resql = $db->query($sql);
    if ($resql) {
        if ($db->num_rows($resql) > 0) {
            echo '<table>';
            echo '<tr><th>ID ligne</th><th>Commande</th><th>Quantité</th><th>Libellé</th><th>JSON</th><th>Detail</th><th>Action</th></tr>';
            
            while ($obj = $db->fetch_object($resql)) {
                $json_status = !empty($obj->detailjson) ? '✅' : '❌';
                $detail_status = !empty($obj->detail) ? '✅' : '❌';
                
                echo '<tr>';
                echo '<td>' . $obj->rowid . '</td>';
                echo '<td>' . $obj->commande_ref . '</td>';
                echo '<td>' . $obj->qty . '</td>';
                echo '<td>' . htmlspecialchars($obj->label) . '</td>';
                echo '<td>' . $json_status . '</td>';
                echo '<td>' . $detail_status . '</td>';
                echo '<td><a href="?action=debug_save&commandedet_id=' . $obj->rowid . '&token=' . newToken() . '">🔧 Déboguer</a></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<div class="warning">⚠️ Aucune ligne avec extrafields trouvée.</div>';
        }
    } else {
        echo '<div class="error">❌ Erreur : ' . $db->lasterror() . '</div>';
    }
    
} else {
    // Menu principal
    echo '<div class="info">';
    echo '<h2>🔧 Débogage des extrafields</h2>';
    echo '<p>Ce script permet de déboguer les problèmes suivants :</p>';
    echo '<ul>';
    echo '<li>❌ Extrafield "detail" qui reste vide</li>';
    echo '<li>❌ Quantité totale non mise à jour</li>';
    echo '<li>❌ Génération du format d\'affichage</li>';
    echo '<li>❌ Sauvegarde/récupération JSON</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<h2>🚀 Actions disponibles</h2>';
    echo '<p>';
    echo '<a href="?action=list_lines&token=' . newToken() . '" class="btn">📋 Lister les lignes avec extrafields</a>';
    echo '</p>';
    
    // Formulaire pour déboguer une ligne spécifique
    echo '<h3>🎯 Déboguer une ligne spécifique</h3>';
    echo '<form method="get">';
    echo '<input type="hidden" name="action" value="debug_save">';
    echo '<input type="hidden" name="token" value="' . newToken() . '">';
    echo '<label>ID de ligne de commande :</label> ';
    echo '<input type="number" name="commandedet_id" placeholder="123" required> ';
    echo '<button type="submit" class="btn">🔬 Déboguer</button>';
    echo '</form>';
}

?>

</body>
</html>
