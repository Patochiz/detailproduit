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
 * \file    test_installation.php
 * \ingroup detailproduit
 * \brief   Test script to verify module installation
 */

// Try main.inc.php using relative path
if (file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
} elseif (file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
} elseif (file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
} else {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('/detailproduit/class/commandedetdetails.class.php');

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Headers
header('Content-Type: text/html; charset=utf-8');

print '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test Installation - Module Détails Produit</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-ok { color: green; font-weight: bold; }
        .test-error { color: red; font-weight: bold; }
        .test-warning { color: orange; font-weight: bold; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .test-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .test-item { margin: 5px 0; padding: 5px 0; border-bottom: 1px dotted #eee; }
        .test-details { font-size: 12px; color: #666; margin-left: 20px; }
    </style>
</head>
<body>';

print '<h1>🧪 Test d\'installation - Module Détails Produit</h1>';

print '<div class="test-section">';
print '<div class="test-title">📋 Informations système</div>';

print '<div class="test-item">';
print '<strong>Version Dolibarr:</strong> ' . DOL_VERSION;
print '<div class="test-details">Minimum requis: 13.0</div>';
print '</div>';

print '<div class="test-item">';
print '<strong>Version PHP:</strong> ' . PHP_VERSION;
print '<div class="test-details">Minimum requis: 7.4</div>';
print '</div>';

print '<div class="test-item">';
print '<strong>Base de données:</strong> ' . $db->type . ' ' . $db->getVersion();
print '<div class="test-details">Compatible: MySQL 5.6+, MariaDB 10.0+</div>';
print '</div>';

print '</div>';

// Test 1: Module activation
print '<div class="test-section">';
print '<div class="test-title">1️⃣ Activation du module</div>';

print '<div class="test-item">';
if (isModEnabled('detailproduit')) {
    print '<span class="test-ok">✅ Module activé</span>';
} else {
    print '<span class="test-error">❌ Module non activé</span>';
    print '<div class="test-details">Allez dans Configuration → Modules/Applications et activez "Détails Produit"</div>';
}
print '</div>';

print '</div>';

// Test 2: Database table
print '<div class="test-section">';
print '<div class="test-title">2️⃣ Structure de base de données</div>';

print '<div class="test-item">';
$sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."commandedet_details'";
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    print '<span class="test-ok">✅ Table llx_commandedet_details existe</span>';
    
    // Test structure
    $sql = "DESCRIBE ".MAIN_DB_PREFIX."commandedet_details";
    $resql = $db->query($sql);
    if ($resql) {
        $fields = array();
        while ($obj = $db->fetch_object($resql)) {
            $fields[] = $obj->Field;
        }
        
        $required_fields = array('rowid', 'fk_commandedet', 'pieces', 'longueur', 'largeur', 'total_value', 'unit', 'description', 'rang', 'date_creation', 'tms');
        $missing_fields = array_diff($required_fields, $fields);
        
        if (empty($missing_fields)) {
            print '<div class="test-details test-ok">✅ Structure de table correcte</div>';
        } else {
            print '<div class="test-details test-error">❌ Champs manquants: ' . implode(', ', $missing_fields) . '</div>';
        }
    }
} else {
    print '<span class="test-error">❌ Table llx_commandedet_details n\'existe pas</span>';
    print '<div class="test-details">Désactivez puis réactivez le module</div>';
}
print '</div>';

// Test indexes
print '<div class="test-item">';
$sql = "SHOW INDEX FROM ".MAIN_DB_PREFIX."commandedet_details WHERE Key_name = 'idx_commandedet_details_fk_commandedet'";
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    print '<span class="test-ok">✅ Index de performance créés</span>';
} else {
    print '<span class="test-warning">⚠️ Index de performance manquants</span>';
    print '<div class="test-details">Performance peut être affectée pour de gros volumes</div>';
}
print '</div>';

print '</div>';

// Test 3: Class loading
print '<div class="test-section">';
print '<div class="test-title">3️⃣ Classes PHP</div>';

print '<div class="test-item">';
if (class_exists('CommandeDetDetails')) {
    print '<span class="test-ok">✅ Classe CommandeDetDetails chargée</span>';
    
    // Test instantiation
    try {
        $details_obj = new CommandeDetDetails($db);
        print '<div class="test-details test-ok">✅ Instantiation réussie</div>';
        
        // Test methods
        $methods = get_class_methods($details_obj);
        $required_methods = array('getDetailsForLine', 'saveDetailsForLine', 'deleteDetailsForLine', 'getTotalsByUnit', 'calculateUnitAndValue');
        $missing_methods = array_diff($required_methods, $methods);
        
        if (empty($missing_methods)) {
            print '<div class="test-details test-ok">✅ Toutes les méthodes présentes</div>';
        } else {
            print '<div class="test-details test-error">❌ Méthodes manquantes: ' . implode(', ', $missing_methods) . '</div>';
        }
    } catch (Exception $e) {
        print '<div class="test-details test-error">❌ Erreur d\'instantiation: ' . $e->getMessage() . '</div>';
    }
} else {
    print '<span class="test-error">❌ Classe CommandeDetDetails non trouvée</span>';
    print '<div class="test-details">Vérifiez le fichier /detailproduit/class/commandedetdetails.class.php</div>';
}
print '</div>';

print '</div>';

// Test 4: File structure
print '<div class="test-section">';
print '<div class="test-title">4️⃣ Structure des fichiers</div>';

$required_files = array(
    '/core/modules/modDetailproduit.class.php' => 'Descripteur du module',
    '/class/commandedetdetails.class.php' => 'Classe métier principale',
    '/core/hooks/detailproduit.class.php' => 'Hooks d\'intégration',
    '/ajax/details_handler.php' => 'Gestionnaire AJAX',
    '/css/details_popup.css' => 'Styles CSS',
    '/js/details_popup.js' => 'Scripts JavaScript',
    '/sql/llx_commandedet_details.sql' => 'Structure de table',
    '/sql/llx_commandedet_details.key.sql' => 'Index de performance',
    '/admin/setup.php' => 'Page de configuration',
    '/langs/fr_FR/detailproduit.lang' => 'Traductions françaises',
    '/langs/en_US/detailproduit.lang' => 'Traductions anglaises'
);

foreach ($required_files as $file => $description) {
    print '<div class="test-item">';
    $full_path = DOL_DOCUMENT_ROOT . '/custom/detailproduit' . $file;
    if (file_exists($full_path)) {
        print '<span class="test-ok">✅ ' . $file . '</span>';
        print '<div class="test-details">' . $description . ' (' . number_format(filesize($full_path)) . ' octets)</div>';
    } else {
        print '<span class="test-error">❌ ' . $file . '</span>';
        print '<div class="test-details">' . $description . ' - MANQUANT</div>';
    }
    print '</div>';
}

print '</div>';

// Test 5: Permissions
print '<div class="test-section">';
print '<div class="test-title">5️⃣ Permissions utilisateur</div>';

print '<div class="test-item">';
if ($user->hasRight('detailproduit', 'details', 'read')) {
    print '<span class="test-ok">✅ Permission de lecture</span>';
} else {
    print '<span class="test-error">❌ Permission de lecture manquante</span>';
    print '<div class="test-details">Allez dans Configuration → Utilisateurs & Groupes → [Votre utilisateur] → Permissions</div>';
}
print '</div>';

print '<div class="test-item">';
if ($user->hasRight('detailproduit', 'details', 'write')) {
    print '<span class="test-ok">✅ Permission d\'écriture</span>';
} else {
    print '<span class="test-warning">⚠️ Permission d\'écriture manquante</span>';
    print '<div class="test-details">Nécessaire pour créer/modifier les détails</div>';
}
print '</div>';

print '<div class="test-item">';
if ($user->hasRight('detailproduit', 'details', 'delete')) {
    print '<span class="test-ok">✅ Permission de suppression</span>';
} else {
    print '<span class="test-warning">⚠️ Permission de suppression manquante</span>';
    print '<div class="test-details">Nécessaire pour supprimer les détails</div>';
}
print '</div>';

print '</div>';

// Test 6: AJAX endpoints
print '<div class="test-section">';
print '<div class="test-title">6️⃣ Points d\'accès AJAX</div>';

$ajax_file = DOL_DOCUMENT_ROOT . '/custom/detailproduit/ajax/details_handler.php';
print '<div class="test-item">';
if (file_exists($ajax_file)) {
    print '<span class="test-ok">✅ Gestionnaire AJAX disponible</span>';
    
    // Test syntax
    $content = file_get_contents($ajax_file);
    if (strpos($content, '<?php') !== false && strpos($content, 'get_details') !== false) {
        print '<div class="test-details test-ok">✅ Structure AJAX correcte</div>';
    } else {
        print '<div class="test-details test-error">❌ Structure AJAX incorrecte</div>';
    }
} else {
    print '<span class="test-error">❌ Gestionnaire AJAX manquant</span>';
}
print '</div>';

print '</div>';

// Test 7: Calculation functions
print '<div class="test-section">';
print '<div class="test-title">7️⃣ Fonctions de calcul</div>';

if (class_exists('CommandeDetDetails')) {
    print '<div class="test-item">';
    
    // Test calculation m²
    $result = CommandeDetDetails::calculateUnitAndValue(2, 2000, 1000);
    if ($result['unit'] === 'm²' && abs($result['total_value'] - 4.0) < 0.001) {
        print '<span class="test-ok">✅ Calcul m² correct</span>';
        print '<div class="test-details">2 pièces × 2000mm × 1000mm = 4.000 m²</div>';
    } else {
        print '<span class="test-error">❌ Calcul m² incorrect</span>';
        print '<div class="test-details">Attendu: 4.000 m², Obtenu: ' . $result['total_value'] . ' ' . $result['unit'] . '</div>';
    }
    print '</div>';
    
    print '<div class="test-item">';
    
    // Test calculation ml
    $result = CommandeDetDetails::calculateUnitAndValue(3, 1500, 0);
    if ($result['unit'] === 'ml' && abs($result['total_value'] - 4.5) < 0.001) {
        print '<span class="test-ok">✅ Calcul ml correct</span>';
        print '<div class="test-details">3 pièces × 1500mm = 4.500 ml</div>';
    } else {
        print '<span class="test-error">❌ Calcul ml incorrect</span>';
        print '<div class="test-details">Attendu: 4.500 ml, Obtenu: ' . $result['total_value'] . ' ' . $result['unit'] . '</div>';
    }
    print '</div>';
    
    print '<div class="test-item">';
    
    // Test calculation u
    $result = CommandeDetDetails::calculateUnitAndValue(5, 0, 0);
    if ($result['unit'] === 'u' && abs($result['total_value'] - 5.0) < 0.001) {
        print '<span class="test-ok">✅ Calcul unités correct</span>';
        print '<div class="test-details">5 pièces = 5.000 u</div>';
    } else {
        print '<span class="test-error">❌ Calcul unités incorrect</span>';
        print '<div class="test-details">Attendu: 5.000 u, Obtenu: ' . $result['total_value'] . ' ' . $result['unit'] . '</div>';
    }
    print '</div>';
    
} else {
    print '<div class="test-item">';
    print '<span class="test-error">❌ Impossible de tester - Classe non disponible</span>';
    print '</div>';
}

print '</div>';

// Test summary
print '<div class="test-section" style="background: #f0f8ff; border-color: #4682b4;">';
print '<div class="test-title">📊 Résumé des tests</div>';

$total_tests = 0;
$passed_tests = 0;

// Compter les résultats
$content = ob_get_contents();
if ($content === false) $content = '';

$total_tests = substr_count($content, 'test-item');
$passed_tests = substr_count($content, 'test-ok">✅');
$warnings = substr_count($content, 'test-warning">⚠️');
$errors = substr_count($content, 'test-error">❌');

$success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100) : 0;

print '<div class="test-item">';
print '<strong>Taux de réussite:</strong> ' . $success_rate . '% (' . $passed_tests . '/' . $total_tests . ' tests)';
print '</div>';

print '<div class="test-item">';
if ($success_rate >= 80) {
    print '<span class="test-ok">✅ Installation réussie</span>';
    print '<div class="test-details">Le module est prêt à être utilisé</div>';
} elseif ($success_rate >= 60) {
    print '<span class="test-warning">⚠️ Installation partielle</span>';
    print '<div class="test-details">Certaines fonctionnalités peuvent ne pas fonctionner</div>';
} else {
    print '<span class="test-error">❌ Installation échouée</span>';
    print '<div class="test-details">Veuillez corriger les erreurs avant utilisation</div>';
}
print '</div>';

if ($warnings > 0 || $errors > 0) {
    print '<div class="test-item">';
    print '<strong>Actions recommandées:</strong>';
    print '<div class="test-details">';
    if ($errors > 0) {
        print '• Corriger les erreurs en rouge<br>';
        print '• Désactiver/réactiver le module si nécessaire<br>';
    }
    if ($warnings > 0) {
        print '• Configurer les permissions utilisateur<br>';
        print '• Vérifier la configuration dans admin/setup.php<br>';
    }
    print '</div>';
    print '</div>';
}

print '</div>';

print '<hr>';
print '<p><small>Test effectué le ' . date('d/m/Y à H:i:s') . ' par ' . $user->login . '</small></p>';

print '</body></html>';
