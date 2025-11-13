<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Script de vérification post-migration vers extrafields
 */

// Tentative d'inclusion de main.inc.php
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Vérification post-migration - DetailProduit 2.0</title>
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
        .check-ok { color: green; font-weight: bold; }
        .check-error { color: red; font-weight: bold; }
        .check-warning { color: orange; font-weight: bold; }
        .btn { background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; margin: 5px; display: inline-block; }
    </style>
</head>
<body>

<h1>🔍 Vérification post-migration DetailProduit 2.0</h1>

<?php

echo '<div class="info">';
echo '<h2>📋 Cette vérification contrôle</h2>';
echo '<ul>';
echo '<li>✅ Module activé et version correcte</li>';
echo '<li>✅ Extrafields requis créés</li>';
echo '<li>✅ Fichiers du module présents</li>';
echo '<li>✅ Classes et méthodes fonctionnelles</li>';
echo '<li>✅ Configuration générale</li>';
echo '</ul>';
echo '</div>';

$all_checks_ok = true;
$warnings = array();
$errors = array();

// 1. Vérifier que le module est activé
echo '<h2>1️⃣ Module DetailProduit</h2>';

if (isModEnabled('detailproduit')) {
    echo '<div class="success">✅ Module detailproduit activé</div>';
    
    // Vérifier la version du module
    $module_dir = DOL_DOCUMENT_ROOT . '/custom/detailproduit/core/modules/modDetailproduit.class.php';
    if (file_exists($module_dir)) {
        include_once $module_dir;
        $module = new modDetailproduit($db);
        echo '<div class="info">ℹ️ Version du module : ' . $module->version . '</div>';
        
        if (version_compare($module->version, '2.0', '>=')) {
            echo '<div class="success">✅ Version 2.0+ détectée (extrafields)</div>';
        } else {
            echo '<div class="warning">⚠️ Version ancienne détectée - mise à jour recommandée</div>';
            $warnings[] = 'Version du module < 2.0';
        }
    } else {
        echo '<div class="error">❌ Fichier de module non trouvé</div>';
        $errors[] = 'Fichier modDetailproduit.class.php manquant';
        $all_checks_ok = false;
    }
} else {
    echo '<div class="error">❌ Module detailproduit non activé</div>';
    $errors[] = 'Module non activé';
    $all_checks_ok = false;
}

// 2. Vérifier les extrafields
echo '<h2>2️⃣ Extrafields requis</h2>';

$sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "commandedet_extrafields";
$resql = $db->query($sql);

if ($resql) {
    $columns = array();
    while ($obj = $db->fetch_object($resql)) {
        $columns[] = $obj->Field;
    }
    
    $required_fields = array('detailjson', 'detail');
    $missing_fields = array();
    
    foreach ($required_fields as $field) {
        if (in_array($field, $columns)) {
            echo '<div class="success">✅ Extrafield <code>' . $field . '</code> présent</div>';
        } else {
            echo '<div class="error">❌ Extrafield <code>' . $field . '</code> manquant</div>';
            $missing_fields[] = $field;
            $errors[] = "Extrafield $field manquant";
            $all_checks_ok = false;
        }
    }
    
    if (count($missing_fields) > 0) {
        echo '<div class="warning">';
        echo '<h3>🛠️ Action requise</h3>';
        echo '<p>Créez les extrafields manquants :</p>';
        echo '<a href="create_extrafields.php" class="btn">🔧 Créer automatiquement</a>';
        echo '</div>';
    }
    
} else {
    echo '<div class="error">❌ Table commandedet_extrafields non accessible</div>';
    $errors[] = 'Table extrafields non accessible';
    $all_checks_ok = false;
}

// 3. Vérifier les fichiers essentiels
echo '<h2>3️⃣ Fichiers du module</h2>';

$essential_files = array(
    'class/commandedetdetails.class.php' => 'Classe principale (modifiée)',
    'ajax/details_handler.php' => 'Handler AJAX',
    'js/details_popup.js' => 'JavaScript interface',
    'css/details_popup.css' => 'Styles CSS',
    'core/hooks/detailproduit.class.php' => 'Hooks Dolibarr',
    'create_extrafields.php' => 'Création extrafields (nouveau)',
    'test_extrafields.php' => 'Tests et validation (nouveau)',
    'MIGRATION_EXTRAFIELDS.md' => 'Documentation migration (nouveau)',
    'README.md' => 'Documentation générale'
);

$base_path = DOL_DOCUMENT_ROOT . '/custom/detailproduit/';

foreach ($essential_files as $file => $description) {
    $full_path = $base_path . $file;
    if (file_exists($full_path)) {
        echo '<div class="success">✅ ' . $file . ' - ' . $description . '</div>';
    } else {
        echo '<div class="error">❌ ' . $file . ' - ' . $description . ' (MANQUANT)</div>';
        $errors[] = "Fichier manquant: $file";
        $all_checks_ok = false;
    }
}

// 4. Vérifier la classe CommandeDetDetails
echo '<h2>4️⃣ Classe CommandeDetDetails</h2>';

try {
    require_once DOL_DOCUMENT_ROOT . '/custom/detailproduit/class/commandedetdetails.class.php';
    
    $details_obj = new CommandeDetDetails($db);
    echo '<div class="success">✅ Classe CommandeDetDetails chargée</div>';
    
    // Vérifier les méthodes clés
    $key_methods = array('getDetailsForLine', 'saveDetailsForLine', 'generateFormattedDetail');
    
    foreach ($key_methods as $method) {
        if (method_exists($details_obj, $method)) {
            echo '<div class="success">✅ Méthode <code>' . $method . '()</code> présente</div>';
        } else {
            echo '<div class="error">❌ Méthode <code>' . $method . '()</code> manquante</div>';
            $errors[] = "Méthode $method manquante";
            $all_checks_ok = false;
        }
    }
    
} catch (Exception $e) {
    echo '<div class="error">❌ Erreur lors du chargement de la classe : ' . htmlspecialchars($e->getMessage()) . '</div>';
    $errors[] = 'Classe CommandeDetDetails non fonctionnelle';
    $all_checks_ok = false;
}

// 5. Vérifier les permissions
echo '<h2>5️⃣ Permissions utilisateur</h2>';

if ($user->admin) {
    echo '<div class="success">✅ Utilisateur administrateur - accès complet</div>';
} else {
    if ($user->hasRight('commande', 'lire')) {
        echo '<div class="success">✅ Permission lecture commandes</div>';
    } else {
        echo '<div class="error">❌ Permission lecture commandes manquante</div>';
        $errors[] = 'Permission lecture manquante';
    }
    
    if ($user->hasRight('commande', 'creer')) {
        echo '<div class="success">✅ Permission écriture commandes</div>';
    } else {
        echo '<div class="warning">⚠️ Permission écriture commandes manquante (lecture seule)</div>';
        $warnings[] = 'Permission écriture manquante';
    }
}

// 6. Test de connectivité AJAX
echo '<h2>6️⃣ Connectivité AJAX</h2>';

$ajax_url = dol_buildpath('/detailproduit/ajax/details_handler.php', 1);
echo '<div class="info">ℹ️ URL AJAX : <code>' . $ajax_url . '</code></div>';

// Test basique de présence du fichier
$ajax_file = DOL_DOCUMENT_ROOT . '/custom/detailproduit/ajax/details_handler.php';
if (file_exists($ajax_file) && is_readable($ajax_file)) {
    echo '<div class="success">✅ Handler AJAX accessible</div>';
} else {
    echo '<div class="error">❌ Handler AJAX non accessible</div>';
    $errors[] = 'Handler AJAX non accessible';
    $all_checks_ok = false;
}

// 7. Statistiques des données
echo '<h2>7️⃣ Statistiques des données</h2>';

// Compter les extrafields avec données
$sql = "SELECT COUNT(*) as total_json, SUM(CASE WHEN detail IS NOT NULL THEN 1 ELSE 0 END) as total_detail";
$sql .= " FROM " . MAIN_DB_PREFIX . "commandedet_extrafields WHERE detailjson IS NOT NULL";

$resql = $db->query($sql);
if ($resql) {
    $stats = $db->fetch_object($resql);
    echo '<div class="info">';
    echo '<h3>📊 Données existantes</h3>';
    echo '<ul>';
    echo '<li><strong>Extrafields avec JSON :</strong> ' . $stats->total_json . '</li>';
    echo '<li><strong>Extrafields avec affichage :</strong> ' . $stats->total_detail . '</li>';
    echo '</ul>';
    echo '</div>';
    
    if ($stats->total_json > 0) {
        echo '<div class="success">✅ Des données de détails sont présentes</div>';
    } else {
        echo '<div class="info">ℹ️ Aucune donnée de détails (normal pour nouvelle installation)</div>';
    }
}

// Vérifier s'il reste des données dans l'ancienne table
$sql = "SHOW TABLES LIKE '" . MAIN_DB_PREFIX . "commandedet_details'";
$resql = $db->query($sql);

if ($resql && $db->num_rows($resql) > 0) {
    $sql_count = "SELECT COUNT(*) as total FROM " . MAIN_DB_PREFIX . "commandedet_details";
    $resql_count = $db->query($sql_count);
    
    if ($resql_count) {
        $count = $db->fetch_object($resql_count);
        if ($count->total > 0) {
            echo '<div class="warning">⚠️ Ancienne table détectée avec ' . $count->total . ' enregistrements</div>';
            echo '<div class="info">💡 Vous pouvez migrer ces données avec <a href="migrate_to_extrafields.php">migrate_to_extrafields.php</a></div>';
            $warnings[] = 'Données dans ancienne table à migrer';
        } else {
            echo '<div class="info">ℹ️ Ancienne table vide détectée</div>';
        }
    }
} else {
    echo '<div class="success">✅ Ancienne table supprimée (migration complète)</div>';
}

// Résumé final
echo '<h2>📊 Résumé de la vérification</h2>';

if ($all_checks_ok) {
    echo '<div class="success">';
    echo '<h3>🎉 Migration réussie !</h3>';
    echo '<p>Tous les contrôles essentiels sont passés. Le module DetailProduit 2.0 est prêt à être utilisé.</p>';
    echo '</div>';
} else {
    echo '<div class="error">';
    echo '<h3>❌ Problèmes détectés</h3>';
    echo '<p>Veuillez corriger les erreurs suivantes :</p>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

if (count($warnings) > 0) {
    echo '<div class="warning">';
    echo '<h3>⚠️ Avertissements</h3>';
    echo '<ul>';
    foreach ($warnings as $warning) {
        echo '<li>' . htmlspecialchars($warning) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

// Actions recommandées
echo '<h2>🚀 Actions recommandées</h2>';
echo '<p>';

if ($all_checks_ok) {
    echo '<a href="test_extrafields.php" class="btn">🧪 Tester le fonctionnement</a>';
    echo '<a href="' . DOL_URL_ROOT . '/commande/card.php" class="btn">📋 Tester sur une commande</a>';
} else {
    if (count($missing_fields) > 0) {
        echo '<a href="create_extrafields.php" class="btn">🔧 Créer les extrafields</a>';
    }
    echo '<a href="MIGRATION_EXTRAFIELDS.md" class="btn">📖 Consulter la documentation</a>';
}

echo '</p>';

echo '<div class="info">';
echo '<h3>📚 Documentation</h3>';
echo '<ul>';
echo '<li><a href="README.md">README.md</a> - Guide général</li>';
echo '<li><a href="MIGRATION_EXTRAFIELDS.md">MIGRATION_EXTRAFIELDS.md</a> - Guide de migration</li>';
echo '<li><a href="CHANGELOG_EXTRAFIELDS.md">CHANGELOG_EXTRAFIELDS.md</a> - Historique des modifications</li>';
echo '</ul>';
echo '</div>';

?>

</body>
</html>
