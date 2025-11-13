<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Script de correction manuelle des extrafields
 */

// Tentative d'inclusion de main.inc.php
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

if (!$user->admin) {
    accessforbidden('Seuls les administrateurs peuvent exécuter ce script');
}

require_once DOL_DOCUMENT_ROOT.'/custom/detailproduit/class/commandedetdetails.class.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Correction manuelle extrafields</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #f0fff0; padding: 10px; border-left: 5px solid green; margin: 10px 0; }
        .error { color: red; background: #ffe4e1; padding: 10px; border-left: 5px solid red; margin: 10px 0; }
        .info { color: blue; background: #f0f8ff; padding: 10px; border-left: 5px solid blue; margin: 10px 0; }
        .warning { color: orange; background: #fff8dc; padding: 10px; border-left: 5px solid orange; margin: 10px 0; }
        .btn { background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; margin: 5px; display: inline-block; }
        .btn-danger { background: #dc3545; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h1>🛠️ Correction manuelle des extrafields</h1>

<?php

$action = GETPOST('action', 'alpha');

if ($action === 'fix_all') {
    echo '<h2>🔄 Correction en cours...</h2>';
    
    // Récupérer toutes les lignes avec JSON mais sans detail
    $sql = "SELECT ef.fk_object, ef.detailjson, cd.qty, cd.label";
    $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet_extrafields ef";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet cd ON cd.rowid = ef.fk_object";
    $sql .= " WHERE ef.detailjson IS NOT NULL AND (ef.detail IS NULL OR ef.detail = '')";
    
    $resql = $db->query($sql);
    
    if ($resql) {
        $fixed = 0;
        $errors = 0;
        
        while ($obj = $db->fetch_object($resql)) {
            $fk_object = $obj->fk_object;
            $json_data = $obj->detailjson;
            
            echo '<h3>Ligne ' . $fk_object . ' : ' . htmlspecialchars($obj->label) . '</h3>';
            
            // Décoder le JSON
            $details = json_decode($json_data, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($details)) {
                // Générer le format d'affichage manuellement
                $formatted_lines = array();
                
                foreach ($details as $detail) {
                    $pieces = (int) ($detail['pieces'] ?? 0);
                    $longueur = !empty($detail['longueur']) ? (int) $detail['longueur'] : null;
                    $largeur = !empty($detail['largeur']) ? (int) $detail['largeur'] : null;
                    $total_value = (float) ($detail['total_value'] ?? 0);
                    $unit = $detail['unit'] ?? 'u';
                    $description = htmlspecialchars($detail['description'] ?? '', ENT_QUOTES, 'UTF-8');
                    
                    // Format : "Nbr x longueur x largeur (quantité unité) description"
                    $line_parts = array();
                    $line_parts[] = $pieces;
                    
                    if ($longueur !== null) {
                        $line_parts[] = $longueur;
                    }
                    
                    if ($largeur !== null) {
                        $line_parts[] = $largeur;
                    }
                    
                    $formatted_line = implode(' x ', $line_parts);
                    $formatted_line .= ' (' . number_format($total_value, 2, '.', '') . ' ' . $unit . ')';
                    
                    if (!empty($description)) {
                        $formatted_line .= ' ' . $description;
                    }
                    
                    $formatted_lines[] = $formatted_line;
                }
                
                $formatted_detail = implode('<br>', $formatted_lines);
                
                echo '<p><strong>Format généré :</strong> <code>' . htmlspecialchars($formatted_detail) . '</code></p>';
                
                // Mettre à jour en base
                $sql_update = "UPDATE " . MAIN_DB_PREFIX . "commandedet_extrafields";
                $sql_update .= " SET detail = '" . $db->escape($formatted_detail) . "'";
                $sql_update .= " WHERE fk_object = " . ((int) $fk_object);
                
                $resql_update = $db->query($sql_update);
                
                if ($resql_update) {
                    echo '<div class="success">✅ Format d\'affichage corrigé</div>';
                    $fixed++;
                    
                    // Calculer et mettre à jour la quantité
                    $details_obj = new CommandeDetDetails($db);
                    $totals = $details_obj->getTotalsByUnit($fk_object);
                    
                    if ($totals !== -1 && !empty($totals)) {
                        // Déterminer l'unité principale
                        $main_unit = 'm²';
                        $max_value = 0;
                        
                        foreach ($totals as $unit => $data) {
                            if ($data['total_value'] > $max_value) {
                                $max_value = $data['total_value'];
                                $main_unit = $unit;
                            }
                        }
                        
                        $calculated_quantity = $totals[$main_unit]['total_value'];
                        
                        echo '<p><strong>Quantité calculée :</strong> ' . $calculated_quantity . ' ' . $main_unit . '</p>';
                        echo '<p><strong>Quantité actuelle :</strong> ' . $obj->qty . '</p>';
                        
                        if (abs($calculated_quantity - $obj->qty) > 0.01) {
                            $update_result = $details_obj->updateCommandLineQuantity($fk_object, $calculated_quantity, $main_unit);
                            
                            if ($update_result > 0) {
                                echo '<div class="success">✅ Quantité mise à jour : ' . $calculated_quantity . '</div>';
                            } else {
                                echo '<div class="error">❌ Échec mise à jour quantité</div>';
                                $errors++;
                            }
                        } else {
                            echo '<div class="info">ℹ️ Quantité déjà correcte</div>';
                        }
                    }
                    
                } else {
                    echo '<div class="error">❌ Erreur mise à jour : ' . $db->lasterror() . '</div>';
                    $errors++;
                }
                
            } else {
                echo '<div class="error">❌ JSON invalide : ' . json_last_error_msg() . '</div>';
                $errors++;
            }
            
            echo '<hr>';
        }
        
        echo '<h2>📊 Résumé</h2>';
        echo '<div class="info">';
        echo '<ul>';
        echo '<li><strong>Lignes corrigées :</strong> ' . $fixed . '</li>';
        echo '<li><strong>Erreurs :</strong> ' . $errors . '</li>';
        echo '</ul>';
        echo '</div>';
        
    } else {
        echo '<div class="error">❌ Erreur SQL : ' . $db->lasterror() . '</div>';
    }
    
} else {
    // Menu principal
    echo '<div class="info">';
    echo '<h2>🔧 Correction automatique</h2>';
    echo '<p>Ce script corrige automatiquement :</p>';
    echo '<ul>';
    echo '<li>✅ Les extrafields "detail" vides</li>';
    echo '<li>✅ Les quantités non synchronisées</li>';
    echo '<li>✅ Le format d\'affichage manquant</li>';
    echo '</ul>';
    echo '</div>';
    
    // Compter les lignes à corriger
    $sql = "SELECT COUNT(*) as total";
    $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet_extrafields ef";
    $sql .= " WHERE ef.detailjson IS NOT NULL AND (ef.detail IS NULL OR ef.detail = '')";
    
    $resql = $db->query($sql);
    
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $to_fix = $obj->total;
        
        if ($to_fix > 0) {
            echo '<div class="warning">';
            echo '<h3>⚠️ Lignes à corriger : ' . $to_fix . '</h3>';
            echo '<p>Des lignes ont des données JSON mais pas de format d\'affichage.</p>';
            echo '</div>';
            
            echo '<p>';
            echo '<a href="?action=fix_all&token=' . newToken() . '" class="btn btn-danger" onclick="return confirm(\'Corriger ' . $to_fix . ' lignes ?\')">🛠️ Corriger automatiquement</a>';
            echo '</p>';
        } else {
            echo '<div class="success">✅ Toutes les lignes sont correctes</div>';
        }
    }
    
    echo '<p>';
    echo '<a href="debug_extrafields.php" class="btn">🔧 Script de débogage détaillé</a>';
    echo '<a href="test_extrafields.php" class="btn">🧪 Tests complets</a>';
    echo '</p>';
}

?>

</body>
</html>
