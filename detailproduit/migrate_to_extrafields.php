<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Script de migration des données de llx_commandedet_details vers les extrafields
 * À exécuter une seule fois après la mise à jour du module
 */

// Tentative d'inclusion de main.inc.php
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main.inc.php failed");

// Vérifier les permissions
if (!$user->admin) {
    accessforbidden('Seuls les administrateurs peuvent exécuter ce script');
}

// Mode sécurisé : nécessite confirmation
$confirm = GETPOST('confirm', 'alpha');
$action = GETPOST('action', 'alpha');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Migration détails produit vers extrafields</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #f0fff0; padding: 10px; border-left: 5px solid green; }
        .warning { color: orange; background: #fff8dc; padding: 10px; border-left: 5px solid orange; }
        .error { color: red; background: #ffe4e1; padding: 10px; border-left: 5px solid red; }
        .info { color: blue; background: #f0f8ff; padding: 10px; border-left: 5px solid blue; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        .btn-danger { background: #dc3545; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>

<h1>🔄 Migration des détails produit vers extrafields</h1>

<?php

if ($action === 'analyze') {
    echo '<h2>📊 Analyse des données existantes</h2>';
    
    // Vérifier si la table existe
    $sql = "SHOW TABLES LIKE '" . MAIN_DB_PREFIX . "commandedet_details'";
    $resql = $db->query($sql);
    
    if (!$resql || $db->num_rows($resql) == 0) {
        echo '<div class="info">ℹ️ La table llx_commandedet_details n\'existe pas ou est vide. Aucune migration nécessaire.</div>';
        echo '<p><a href="?" class="btn">🔙 Retour</a></p>';
        exit;
    }
    
    // Compter les détails dans la table
    $sql = "SELECT COUNT(*) as total FROM " . MAIN_DB_PREFIX . "commandedet_details";
    $resql = $db->query($sql);
    $total_details = 0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $total_details = $obj->total;
    }
    
    // Compter les lignes de commande uniques
    $sql = "SELECT COUNT(DISTINCT fk_commandedet) as total FROM " . MAIN_DB_PREFIX . "commandedet_details";
    $resql = $db->query($sql);
    $total_lines = 0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $total_lines = $obj->total;
    }
    
    // Vérifier les extrafields existants
    $sql = "SELECT COUNT(*) as total FROM " . MAIN_DB_PREFIX . "commandedet_extrafields WHERE detailjson IS NOT NULL";
    $resql = $db->query($sql);
    $existing_extrafields = 0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $existing_extrafields = $obj->total;
    }
    
    echo '<div class="info">';
    echo '<h3>📈 Statistiques</h3>';
    echo '<ul>';
    echo '<li><strong>Détails dans la table :</strong> ' . number_format($total_details) . '</li>';
    echo '<li><strong>Lignes de commande concernées :</strong> ' . number_format($total_lines) . '</li>';
    echo '<li><strong>Extrafields déjà remplis :</strong> ' . number_format($existing_extrafields) . '</li>';
    echo '</ul>';
    echo '</div>';
    
    if ($total_details == 0) {
        echo '<div class="success">✅ Aucune donnée à migrer.</div>';
        echo '<p><a href="?" class="btn">🔙 Retour</a></p>';
        exit;
    }
    
    if ($existing_extrafields > 0) {
        echo '<div class="warning">⚠️ Attention : ' . $existing_extrafields . ' extrafields contiennent déjà des données. La migration les écrasera.</div>';
    }
    
    // Afficher un échantillon des données
    echo '<h3>📋 Échantillon des données à migrer</h3>';
    $sql = "SELECT d.fk_commandedet, d.pieces, d.longueur, d.largeur, d.total_value, d.unit, d.description";
    $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet_details d";
    $sql .= " ORDER BY d.fk_commandedet, d.rang LIMIT 10";
    
    $resql = $db->query($sql);
    if ($resql) {
        echo '<table>';
        echo '<tr><th>Ligne commande</th><th>Pièces</th><th>Longueur</th><th>Largeur</th><th>Total</th><th>Unité</th><th>Description</th></tr>';
        
        while ($obj = $db->fetch_object($resql)) {
            echo '<tr>';
            echo '<td>' . $obj->fk_commandedet . '</td>';
            echo '<td>' . $obj->pieces . '</td>';
            echo '<td>' . ($obj->longueur ?: '-') . '</td>';
            echo '<td>' . ($obj->largeur ?: '-') . '</td>';
            echo '<td>' . $obj->total_value . '</td>';
            echo '<td>' . $obj->unit . '</td>';
            echo '<td>' . htmlspecialchars($obj->description ?: '-') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo '<h3>🚀 Actions disponibles</h3>';
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="migrate">';
    echo '<input type="hidden" name="token" value="' . newToken() . '">';
    echo '<p>';
    echo '<button type="submit" class="btn btn-danger" onclick="return confirm(\'Êtes-vous sûr de vouloir migrer ' . $total_details . ' détails vers les extrafields ?\')">🔄 Migrer vers extrafields</button>';
    echo '<a href="?" class="btn">❌ Annuler</a>';
    echo '</p>';
    echo '</form>';
    
} elseif ($action === 'migrate') {
    
    // Vérifier le token CSRF
    if (!checkToken()) {
        echo '<div class="error">❌ Token de sécurité invalide</div>';
        exit;
    }
    
    echo '<h2>🔄 Migration en cours...</h2>';
    
    $db->begin();
    
    $migrated_lines = 0;
    $migrated_details = 0;
    $errors = array();
    
    try {
        // Récupérer toutes les lignes de commande avec détails
        $sql = "SELECT DISTINCT fk_commandedet FROM " . MAIN_DB_PREFIX . "commandedet_details ORDER BY fk_commandedet";
        $resql = $db->query($sql);
        
        if (!$resql) {
            throw new Exception('Erreur lors de la récupération des lignes de commande');
        }
        
        while ($obj = $db->fetch_object($resql)) {
            $fk_commandedet = $obj->fk_commandedet;
            
            // Récupérer tous les détails pour cette ligne
            $sql_details = "SELECT pieces, longueur, largeur, total_value, unit, description";
            $sql_details .= " FROM " . MAIN_DB_PREFIX . "commandedet_details";
            $sql_details .= " WHERE fk_commandedet = " . ((int) $fk_commandedet);
            $sql_details .= " ORDER BY rang ASC";
            
            $resql_details = $db->query($sql_details);
            if (!$resql_details) {
                $errors[] = "Erreur lors de la récupération des détails pour la ligne $fk_commandedet";
                continue;
            }
            
            $details_array = array();
            while ($detail = $db->fetch_object($resql_details)) {
                $details_array[] = array(
                    'pieces' => (float) $detail->pieces,
                    'longueur' => !empty($detail->longueur) ? (float) $detail->longueur : null,
                    'largeur' => !empty($detail->largeur) ? (float) $detail->largeur : null,
                    'total_value' => (float) $detail->total_value,
                    'unit' => (string) $detail->unit,
                    'description' => (string) $detail->description
                );
                $migrated_details++;
            }
            
            if (count($details_array) > 0) {
                // Encoder en JSON
                $json_data = json_encode($details_array, JSON_UNESCAPED_UNICODE);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = "Erreur encodage JSON pour ligne $fk_commandedet: " . json_last_error_msg();
                    continue;
                }
                
                // Générer le format d'affichage
                $formatted_lines = array();
                foreach ($details_array as $detail) {
                    $pieces = (int) $detail['pieces'];
                    $longueur = !empty($detail['longueur']) ? (int) $detail['longueur'] : null;
                    $largeur = !empty($detail['largeur']) ? (int) $detail['largeur'] : null;
                    $total_value = number_format($detail['total_value'], 2, '.', '');
                    $unit = $detail['unit'];
                    $description = $detail['description'] ?? '';
                    
                    $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
                    
                    $line_parts = array();
                    $line_parts[] = $pieces;
                    
                    if ($longueur !== null) {
                        $line_parts[] = $longueur;
                    }
                    
                    if ($largeur !== null) {
                        $line_parts[] = $largeur;
                    }
                    
                    $formatted_line = implode(' x ', $line_parts);
                    $formatted_line .= ' (' . $total_value . ' ' . $unit . ')';
                    
                    if (!empty($description)) {
                        $formatted_line .= ' ' . $description;
                    }
                    
                    $formatted_lines[] = $formatted_line;
                }
                
                $formatted_detail = implode('<br>', $formatted_lines);
                
                // Mettre à jour ou insérer l'extrafield
                $sql_check = "SELECT fk_object FROM " . MAIN_DB_PREFIX . "commandedet_extrafields WHERE fk_object = " . ((int) $fk_commandedet);
                $resql_check = $db->query($sql_check);
                
                if ($resql_check && $db->num_rows($resql_check) > 0) {
                    // Mettre à jour
                    $sql_update = "UPDATE " . MAIN_DB_PREFIX . "commandedet_extrafields";
                    $sql_update .= " SET detailjson = '" . $db->escape($json_data) . "'";
                    $sql_update .= ", detail = '" . $db->escape($formatted_detail) . "'";
                    $sql_update .= " WHERE fk_object = " . ((int) $fk_commandedet);
                    
                    if (!$db->query($sql_update)) {
                        $errors[] = "Erreur mise à jour extrafield pour ligne $fk_commandedet: " . $db->lasterror();
                        continue;
                    }
                } else {
                    // Insérer
                    $sql_insert = "INSERT INTO " . MAIN_DB_PREFIX . "commandedet_extrafields";
                    $sql_insert .= " (fk_object, detailjson, detail) VALUES";
                    $sql_insert .= " (" . ((int) $fk_commandedet) . ", '" . $db->escape($json_data) . "', '" . $db->escape($formatted_detail) . "')";
                    
                    if (!$db->query($sql_insert)) {
                        $errors[] = "Erreur insertion extrafield pour ligne $fk_commandedet: " . $db->lasterror();
                        continue;
                    }
                }
                
                $migrated_lines++;
            }
        }
        
        if (count($errors) == 0) {
            $db->commit();
            echo '<div class="success">';
            echo '<h3>✅ Migration terminée avec succès !</h3>';
            echo '<ul>';
            echo '<li><strong>Lignes de commande migrées :</strong> ' . number_format($migrated_lines) . '</li>';
            echo '<li><strong>Détails migrés :</strong> ' . number_format($migrated_details) . '</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<h3>🗑️ Suppression de l\'ancienne table</h3>';
            echo '<div class="warning">⚠️ La migration est terminée. Vous pouvez maintenant supprimer l\'ancienne table si vous le souhaitez.</div>';
            echo '<form method="post">';
            echo '<input type="hidden" name="action" value="drop_table">';
            echo '<input type="hidden" name="token" value="' . newToken() . '">';
            echo '<p>';
            echo '<button type="submit" class="btn btn-danger" onclick="return confirm(\'Êtes-vous ABSOLUMENT sûr de vouloir supprimer la table llx_commandedet_details ? Cette action est IRRÉVERSIBLE !\')">🗑️ Supprimer l\'ancienne table</button>';
            echo '<a href="?" class="btn">🔙 Retour au menu</a>';
            echo '</p>';
            echo '</form>';
            
        } else {
            $db->rollback();
            echo '<div class="error">';
            echo '<h3>❌ Erreurs lors de la migration</h3>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
            echo '<p><a href="?" class="btn">🔙 Retour</a></p>';
        }
        
    } catch (Exception $e) {
        $db->rollback();
        echo '<div class="error">❌ Erreur critique : ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<p><a href="?" class="btn">🔙 Retour</a></p>';
    }
    
} elseif ($action === 'drop_table') {
    
    // Vérifier le token CSRF
    if (!checkToken()) {
        echo '<div class="error">❌ Token de sécurité invalide</div>';
        exit;
    }
    
    echo '<h2>🗑️ Suppression de l\'ancienne table</h2>';
    
    try {
        $sql = "DROP TABLE IF EXISTS " . MAIN_DB_PREFIX . "commandedet_details";
        $resql = $db->query($sql);
        
        if ($resql) {
            echo '<div class="success">✅ Table llx_commandedet_details supprimée avec succès.</div>';
        } else {
            echo '<div class="error">❌ Erreur lors de la suppression : ' . $db->lasterror() . '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    echo '<p><a href="?" class="btn">🔙 Retour au menu</a></p>';
    
} else {
    // Menu principal
    echo '<div class="info">';
    echo '<h2>ℹ️ Information</h2>';
    echo '<p>Ce script permet de migrer les données existantes de la table <code>llx_commandedet_details</code> vers les extrafields <code>detailjson</code> et <code>detail</code>.</p>';
    echo '<p><strong>⚠️ Important :</strong> Effectuez une sauvegarde de votre base de données avant de procéder à la migration.</p>';
    echo '</div>';
    
    echo '<h2>🚀 Actions disponibles</h2>';
    echo '<p>';
    echo '<a href="?action=analyze&token=' . newToken() . '" class="btn">📊 Analyser les données existantes</a>';
    echo '</p>';
}

?>

</body>
</html>
