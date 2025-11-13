<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Script de création automatique des extrafields pour le module detailproduit
 */

// Tentative d'inclusion de main.inc.php
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Vérifications de sécurité
if (!$user->admin) {
    accessforbidden('Seuls les administrateurs peuvent exécuter ce script');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Création des extrafields detailproduit</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #f0fff0; padding: 10px; border-left: 5px solid green; margin: 10px 0; }
        .error { color: red; background: #ffe4e1; padding: 10px; border-left: 5px solid red; margin: 10px 0; }
        .info { color: blue; background: #f0f8ff; padding: 10px; border-left: 5px solid blue; margin: 10px 0; }
        .warning { color: orange; background: #fff8dc; padding: 10px; border-left: 5px solid orange; margin: 10px 0; }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; text-decoration: none; border-radius: 3px; display: inline-block; }
        .btn-danger { background: #dc3545; }
        .btn:hover { opacity: 0.8; }
        code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h1>🛠️ Création des extrafields detailproduit</h1>

<?php

$action = GETPOST('action', 'alpha');

if ($action === 'create_extrafields') {
    
    // Vérifier le token CSRF
    if (!checkToken()) {
        echo '<div class="error">❌ Token de sécurité invalide</div>';
        exit;
    }
    
    echo '<h2>🔧 Création des extrafields en cours...</h2>';
    
    $extrafields = new ExtraFields($db);
    $element = 'commandedet';
    $created = 0;
    $errors = array();
    
    // Configuration des extrafields à créer
    $fields_to_create = array(
        'detailjson' => array(
            'label' => 'Détails JSON',
            'type' => 'text',
            'size' => '65535',
            'elementtype' => $element,
            'visible' => 0,
            'required' => 0,
            'unique' => 0,
            'default' => '',
            'help' => 'Données JSON complètes des détails produit (usage interne)',
            'computed' => '',
            'pos' => 100
        ),
        'detail' => array(
            'label' => 'Détails produit',
            'type' => 'html',
            'size' => '65535',
            'elementtype' => $element,
            'visible' => 1,
            'required' => 0,
            'unique' => 0,
            'default' => '',
            'help' => 'Affichage formaté des détails produit',
            'computed' => '',
            'pos' => 101
        ),
        'ref_chantier' => array(
            'label' => 'Ref Chantier',
            'type' => 'varchar',
            'size' => '255',
            'elementtype' => $element,
            'visible' => 1,
            'required' => 0,
            'unique' => 0,
            'default' => '',
            'help' => 'Référence du chantier pour les services',
            'computed' => '',
            'pos' => 102
        )
    );
    
    try {
        foreach ($fields_to_create as $key => $field_config) {
            echo '<h3>🔄 Création de l\'extrafield "' . $key . '"</h3>';
            
            // Vérifier si l'extrafield existe déjà
            $existing = $extrafields->fetch_name_optionals_label($element);
            
            if (isset($existing[$key])) {
                echo '<div class="warning">⚠️ Extrafield "' . $key . '" existe déjà, ignoré.</div>';
                continue;
            }
            
            // Créer l'extrafield
            $result = $extrafields->addExtraField(
                $key,                           // attribute code
                $field_config['label'],         // label
                $field_config['type'],          // type
                $field_config['pos'],           // position
                $field_config['size'],          // size
                $field_config['elementtype'],   // elementtype
                $field_config['unique'],        // unique
                $field_config['required'],      // required
                $field_config['default'],       // default value
                '',                             // param (empty for our use case)
                0,                              // always editable
                '',                             // perms
                '',                             // list
                $field_config['help'],          // help
                '',                             // computed
                '',                             // entity
                '',                             // langfile
                $field_config['visible'],       // enabled
                $field_config['visible']        // totalizable
            );
            
            if ($result > 0) {
                echo '<div class="success">✅ Extrafield "' . $key . '" créé avec succès (ID: ' . $result . ')</div>';
                $created++;
                
                // Afficher la configuration
                echo '<div class="info">';
                echo '<h4>📊 Configuration</h4>';
                echo '<ul>';
                echo '<li><strong>Code :</strong> ' . $key . '</li>';
                echo '<li><strong>Libellé :</strong> ' . $field_config['label'] . '</li>';
                echo '<li><strong>Type :</strong> ' . $field_config['type'] . '</li>';
                echo '<li><strong>Visible :</strong> ' . ($field_config['visible'] ? 'Oui' : 'Non') . '</li>';
                echo '<li><strong>Requis :</strong> ' . ($field_config['required'] ? 'Oui' : 'Non') . '</li>';
                echo '</ul>';
                echo '</div>';
                
            } else {
                $error_msg = 'Erreur lors de la création de l\'extrafield "' . $key . '"';
                if (!empty($extrafields->errors)) {
                    $error_msg .= ': ' . implode(', ', $extrafields->errors);
                } else {
                    $error_msg .= ' (code erreur: ' . $result . ')';
                }
                
                echo '<div class="error">❌ ' . $error_msg . '</div>';
                $errors[] = $error_msg;
            }
        }
        
        // Résumé final
        echo '<h2>📊 Résumé</h2>';
        
        if ($created > 0) {
            echo '<div class="success">';
            echo '<h3>✅ Création terminée avec succès !</h3>';
            echo '<ul>';
            echo '<li><strong>Extrafields créés :</strong> ' . $created . '</li>';
            echo '<li><strong>Erreurs :</strong> ' . count($errors) . '</li>';
            echo '</ul>';
            echo '</div>';
            
            if (count($errors) == 0) {
                echo '<div class="info">';
                echo '<h3>🎉 Installation complète !</h3>';
                echo '<p>Les extrafields ont été créés avec succès. Vous pouvez maintenant :</p>';
                echo '<ol>';
                echo '<li>Tester le module avec le <a href="test_extrafields.php" class="btn">🧪 Script de test</a></li>';
                echo '<li>Utiliser le popup détails sur les lignes de commande</li>';
                echo '<li>Vérifier l\'affichage dans les listes et fiches</li>';
                echo '</ol>';
                echo '</div>';
            }
        }
        
        if (count($errors) > 0) {
            echo '<div class="error">';
            echo '<h3>❌ Erreurs rencontrées</h3>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '<p><strong>Solution :</strong> Créez les extrafields manuellement via l\'interface d\'administration de Dolibarr.</p>';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Erreur critique : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    echo '<p><a href="?" class="btn">🔙 Retour au menu</a></p>';
    
} elseif ($action === 'check_existing') {
    echo '<h2>🔍 Vérification des extrafields existants</h2>';
    
    $extrafields = new ExtraFields($db);
    $element = 'commandedet';
    
    // Récupérer les extrafields existants
    $existing = $extrafields->fetch_name_optionals_label($element);
    
    echo '<h3>📋 Extrafields existants pour "' . $element . '"</h3>';
    
    if (!empty($existing)) {
        echo '<table>';
        echo '<tr><th>Code</th><th>Libellé</th><th>Type</th><th>Visible</th><th>Requis</th><th>Status</th></tr>';

        $required_fields = array('detailjson', 'detail', 'ref_chantier');
        
        foreach ($existing as $key => $field) {
            $is_required = in_array($key, $required_fields);
            $status = $is_required ? '✅ Requis' : 'ℹ️ Optionnel';
            
            echo '<tr>';
            echo '<td><code>' . htmlspecialchars($key) . '</code></td>';
            echo '<td>' . htmlspecialchars($field['label']) . '</td>';
            echo '<td>' . htmlspecialchars($field['type']) . '</td>';
            echo '<td>' . ($field['visible'] ? 'Oui' : 'Non') . '</td>';
            echo '<td>' . ($field['required'] ? 'Oui' : 'Non') . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Vérifier si les champs requis sont présents
        $missing_fields = array();
        foreach ($required_fields as $required_field) {
            if (!isset($existing[$required_field])) {
                $missing_fields[] = $required_field;
            }
        }
        
        if (count($missing_fields) > 0) {
            echo '<div class="warning">';
            echo '<h3>⚠️ Extrafields manquants</h3>';
            echo '<p>Les extrafields suivants sont requis pour le module detailproduit :</p>';
            echo '<ul>';
            foreach ($missing_fields as $field) {
                echo '<li><code>' . $field . '</code></li>';
            }
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<div class="success">✅ Tous les extrafields requis sont présents !</div>';
        }
        
    } else {
        echo '<div class="warning">⚠️ Aucun extrafield trouvé pour l\'élément "' . $element . '"</div>';
    }
    
    echo '<p><a href="?" class="btn">🔙 Retour au menu</a></p>';
    
} else {
    // Menu principal
    echo '<div class="info">';
    echo '<h2>ℹ️ À propos</h2>';
    echo '<p>Ce script permet de créer automatiquement les extrafields requis pour le module <strong>detailproduit</strong>.</p>';
    echo '<p>Les extrafields créés seront :</p>';
    echo '<ul>';
    echo '<li><code>detailjson</code> : Stockage JSON des données (type: text long, invisible)</li>';
    echo '<li><code>detail</code> : Affichage formaté (type: HTML, visible)</li>';
    echo '<li><code>ref_chantier</code> : Référence du chantier pour les services (type: varchar, visible)</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div class="warning">';
    echo '<h3>⚠️ Prérequis</h3>';
    echo '<ul>';
    echo '<li>Vous devez être connecté en tant qu\'administrateur</li>';
    echo '<li>Le module "Extrafields" doit être activé dans Dolibarr</li>';
    echo '<li>Effectuez une sauvegarde avant de procéder</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<h2>🚀 Actions disponibles</h2>';
    echo '<p>';
    echo '<a href="?action=check_existing&token=' . newToken() . '" class="btn">🔍 Vérifier les extrafields existants</a>';
    echo '</p>';
    echo '<p>';
    echo '<a href="?action=create_extrafields&token=' . newToken() . '" class="btn btn-danger" onclick="return confirm(\'Êtes-vous sûr de vouloir créer les extrafields ? Cette action modifiera la structure de la base de données.\')">🛠️ Créer les extrafields automatiquement</a>';
    echo '</p>';
    
    echo '<div class="info">';
    echo '<h3>📖 Alternative manuelle</h3>';
    echo '<p>Si la création automatique ne fonctionne pas, vous pouvez créer les extrafields manuellement :</p>';
    echo '<ol>';
    echo '<li>Aller dans <strong>Administration → Modules → Extrafields</strong></li>';
    echo '<li>Sélectionner <strong>"Order lines"</strong></li>';
    echo '<li>Créer les deux extrafields selon la <a href="MIGRATION_EXTRAFIELDS.md" target="_blank">documentation</a></li>';
    echo '</ol>';
    echo '</div>';
}

?>

</body>
</html>
