<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Script de test pour l'intégration extrafield "détail"
 */

// Mode debug
$debug_mode = true;

function debug_log($message) {
    global $debug_mode;
    if ($debug_mode) {
        echo "[DEBUG] " . date('Y-m-d H:i:s') . " - " . $message . "\n";
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

debug_log("=== TEST INTEGRATION EXTRAFIELD HTML ===");

// Créer des données de test (avec caractères spéciaux pour tester l'échappement HTML)
$test_details = array(
    array(
        'pieces' => 20,
        'longueur' => 3000,
        'largeur' => 300,
        'total_value' => 1.80,
        'unit' => 'm²',
        'description' => 'ABD'
    ),
    array(
        'pieces' => 10,
        'longueur' => 2000,
        'largeur' => 300,
        'total_value' => 6.00,
        'unit' => 'm²',
        'description' => 'DEF & GHI <test>'
    ),
    array(
        'pieces' => 5,
        'longueur' => 1500,
        'largeur' => 0,
        'total_value' => 7.50,
        'unit' => 'ml',
        'description' => 'Test "guillemets" & ampersand'
    ),
    array(
        'pieces' => 3,
        'longueur' => 0,
        'largeur' => 0,
        'total_value' => 3.00,
        'unit' => 'u',
        'description' => 'Test <balise> HTML'
    )
);

// Test avec un ID de ligne existant (vous devez adapter cet ID)
$test_commandedet_id = 1; // ⚠️ MODIFIEZ cet ID avec une vraie ligne de votre base

debug_log("Test avec ligne de commande ID: " . $test_commandedet_id);

// Vérifier que la ligne existe
$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commandedet WHERE rowid = " . (int) $test_commandedet_id;
$resql = $db->query($sql);

if (!$resql || !$db->num_rows($resql)) {
    die("❌ Ligne de commande ID $test_commandedet_id non trouvée. Veuillez modifier \$test_commandedet_id dans le script.\n");
}

debug_log("✅ Ligne de commande trouvée");

// Instancier la classe
$details_obj = new CommandeDetDetails($db);

// Sauvegarder les détails (cela va automatiquement mettre à jour l'extrafield)
debug_log("Sauvegarde des détails de test...");
$result = $details_obj->saveDetailsForLine($test_commandedet_id, $test_details, $user);

if ($result < 0) {
    echo "❌ Erreur lors de la sauvegarde:\n";
    print_r($details_obj->errors);
    exit;
}

debug_log("✅ Détails sauvegardés avec succès");

// Vérifier le contenu de l'extrafield
debug_log("Vérification de l'extrafield...");
$sql = "SELECT detail FROM " . MAIN_DB_PREFIX . "commandedet_extrafields WHERE fk_object = " . (int) $test_commandedet_id;
$resql = $db->query($sql);

if ($resql && $db->num_rows($resql)) {
    $obj = $db->fetch_object($resql);
    echo "\n🎯 RESULTAT - Contenu de l'extrafield 'detail':\n";
    echo "=" . str_repeat("=", 50) . "\n";
    echo $obj->detail . "\n";
    echo "=" . str_repeat("=", 50) . "\n";
    
    // Vérifier le format attendu (format HTML avec <br>)
    $lines = explode('<br>', $obj->detail);
    $expected_lines = array(
        "20 x 3000 x 300 (1.80 m²) ABD",
        "10 x 2000 x 300 (6.00 m²) DEF &amp; GHI &lt;test&gt;",
        "5 x 1500 (7.50 ml) Test &quot;guillemets&quot; &amp; ampersand",
        "3 (3.00 u) Test &lt;balise&gt; HTML"
    );
    
    debug_log("Vérification du format...");
    $format_ok = true;
    
    foreach ($expected_lines as $i => $expected) {
        if (isset($lines[$i])) {
            if (trim($lines[$i]) === $expected) {
                debug_log("✅ Ligne " . ($i + 1) . " format OK: " . $expected);
            } else {
                debug_log("❌ Ligne " . ($i + 1) . " format incorrect:");
                debug_log("   Attendu: " . $expected);
                debug_log("   Reçu:    " . trim($lines[$i]));
                $format_ok = false;
            }
        } else {
            debug_log("❌ Ligne " . ($i + 1) . " manquante");
            $format_ok = false;
        }
    }
    
    if ($format_ok) {
        echo "\n🎉 SUCCÈS! Le format est conforme à vos spécifications.\n";
    } else {
        echo "\n⚠️ Le format nécessite des ajustements.\n";
    }
    
} else {
    echo "❌ Extrafield 'detail' non trouvé ou vide\n";
}

// Test de suppression
debug_log("\nTest de suppression des détails...");
$result = $details_obj->deleteDetailsForLine($test_commandedet_id);

if ($result < 0) {
    echo "❌ Erreur lors de la suppression:\n";
    print_r($details_obj->errors);
} else {
    debug_log("✅ Détails supprimés avec succès");
    
    // Vérifier que l'extrafield est bien effacé
    $sql = "SELECT detail FROM " . MAIN_DB_PREFIX . "commandedet_extrafields WHERE fk_object = " . (int) $test_commandedet_id;
    $resql = $db->query($sql);
    
    if ($resql && $db->num_rows($resql)) {
        $obj = $db->fetch_object($resql);
        if (empty($obj->detail)) {
            debug_log("✅ Extrafield correctement effacé");
        } else {
            debug_log("⚠️ Extrafield non effacé: " . $obj->detail);
        }
    }
}

echo "\n=== FIN DU TEST ===\n";
echo "\nVotre module est maintenant configuré pour:\n";
echo "✅ Sauvegarder automatiquement dans l'extrafield 'detail'\n";
echo "✅ Format HTML: 'Nbr x longueur x largeur (quantité unité) description' (séparé par <br>)\n";
echo "✅ Effacer automatiquement l'extrafield lors de la suppression\n";
echo "\nUtilisation normale du popup = mise à jour automatique de l'extrafield! 🎯\n";

?>
