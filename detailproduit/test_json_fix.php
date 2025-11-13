<?php
/**
 * Test rapide pour vérifier la correction JSON
 * Fichier temporaire - à supprimer après test
 */

// Simuler des données comme elles seraient envoyées par le JavaScript corrigé
$test_json = '[
    {
        "pieces": 10,
        "longueur": 3000,
        "largeur": 300,
        "total_value": 9.0,
        "unit": "m²",
        "description": "Test description"
    },
    {
        "pieces": 5,
        "longueur": 2000,
        "largeur": null,
        "total_value": 10.0,
        "unit": "ml",
        "description": "Autre test"
    }
]';

echo "<h2>Test de validation JSON - Module DetailProduit</h2>";

echo "<h3>JSON de test :</h3>";
echo "<pre>" . htmlspecialchars($test_json) . "</pre>";

echo "<h3>Validation :</h3>";

// Test de décodage
$decoded = json_decode($test_json, true);
$json_error = json_last_error();

if ($json_error === JSON_ERROR_NONE) {
    echo "✅ <strong style='color: green;'>JSON VALIDE</strong><br>";
    echo "Nombre d'éléments décodés : " . count($decoded) . "<br>";
    
    echo "<h3>Données décodées :</h3>";
    echo "<pre>";
    foreach ($decoded as $index => $detail) {
        echo "Ligne " . ($index + 1) . ":\n";
        foreach ($detail as $key => $value) {
            $valueStr = is_null($value) ? 'NULL' : $value;
            echo "  - $key: $valueStr (" . gettype($value) . ")\n";
        }
        echo "\n";
    }
    echo "</pre>";
    
    // Test de re-sérialisation
    $re_encoded = json_encode($decoded);
    if ($re_encoded !== false) {
        echo "✅ <strong style='color: green;'>RE-SÉRIALISATION RÉUSSIE</strong><br>";
        echo "Longueur : " . strlen($re_encoded) . " caractères<br>";
    } else {
        echo "❌ <strong style='color: red;'>ERREUR RE-SÉRIALISATION</strong><br>";
    }
    
} else {
    echo "❌ <strong style='color: red;'>JSON INVALIDE</strong><br>";
    echo "Code d'erreur : " . $json_error . "<br>";
    echo "Message : " . json_last_error_msg() . "<br>";
}

echo "<h3>Test avec les anciennes données problématiques :</h3>";

// Simuler l'ancien format problématique
$old_problematic = '[{pieces:10,longueur:3000,largeur:300,total_val:9}]';
echo "Ancien format : <code>" . htmlspecialchars($old_problematic) . "</code><br>";

$old_decoded = json_decode($old_problematic, true);
$old_error = json_last_error();

if ($old_error === JSON_ERROR_NONE) {
    echo "⚠️ Ancien format décodé (ne devrait pas arriver)<br>";
} else {
    echo "✅ <strong style='color: green;'>Ancien format rejeté correctement</strong><br>";
    echo "Erreur : " . json_last_error_msg() . "<br>";
}

echo "<hr>";
echo "<p><em>Ce fichier de test peut être supprimé après vérification.</em></p>";
?>
