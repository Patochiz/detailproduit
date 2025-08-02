<?php
/**
 * Fichier de test pour vérifier le bon fonctionnement du module detailproduit
 * À placer temporairement dans le dossier ajax/ pour tester le module
 */

// Inclusion de main.inc.php
$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res) die("Include of main fails");

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Module DetailProduit</title></head><body>";
echo "<h1>🧪 Test Module DetailProduit</h1>";

echo "<h2>✅ Vérifications de base</h2>";

// Vérifier l'authentification
if (!$user || !$user->id) {
    echo "<p style='color:red'>❌ Utilisateur non authentifié</p>";
} else {
    echo "<p style='color:green'>✅ Utilisateur authentifié: " . $user->login . " (ID: " . $user->id . ")</p>";
}

// Vérifier l'activation du module
if (!isModEnabled('detailproduit')) {
    echo "<p style='color:red'>❌ Module detailproduit non activé</p>";
} else {
    echo "<p style='color:green'>✅ Module detailproduit activé</p>";
}

// Vérifier la classe
try {
    dol_include_once('/detailproduit/class/commandedetdetails.class.php');
    $test_obj = new CommandeDetDetails($db);
    echo "<p style='color:green'>✅ Classe CommandeDetDetails chargée</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur lors du chargement de la classe: " . $e->getMessage() . "</p>";
}

// Test de génération de token
$token = newToken();
echo "<p>🔑 Token généré: " . substr($token, 0, 20) . "...</p>";

echo "<h2>🔧 Variables d'environnement</h2>";
echo "<p><strong>DOL_URL_ROOT:</strong> " . DOL_URL_ROOT . "</p>";
echo "<p><strong>DOL_DOCUMENT_ROOT:</strong> " . DOL_DOCUMENT_ROOT . "</p>";
echo "<p><strong>MAIN_DB_PREFIX:</strong> " . MAIN_DB_PREFIX . "</p>";

echo "<h2>📋 Test JavaScript</h2>";
echo "<p>Variables injectées pour tester le JavaScript :</p>";

// Inclure les assets du module
echo '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/detailproduit/css/details_popup.css', 1).'">';

echo '<script type="text/javascript">';
echo 'window.DOL_URL_ROOT = "'.DOL_URL_ROOT.'";';
echo 'window.token = "'.$token.'";';
echo 'window.newtoken = "'.$token.'";';
echo '</script>';

echo '<script type="text/javascript" src="'.dol_buildpath('/detailproduit/js/details_popup.js', 1).'"></script>';

echo '<script type="text/javascript">';
echo 'document.addEventListener("DOMContentLoaded", function() {';
echo '  console.log("🧪 Test des variables JavaScript:");';
echo '  console.log("DOL_URL_ROOT:", window.DOL_URL_ROOT);';
echo '  console.log("token:", window.token ? window.token.substring(0,10) + "..." : "UNDEFINED");';
echo '  console.log("Fonctions disponibles:", {';
echo '    openDetailsModal: typeof openDetailsModal,';
echo '    addDetailsButtonsToExistingLines: typeof addDetailsButtonsToExistingLines';
echo '  });';
echo '  ';
echo '  // Test d\'ouverture du modal';
echo '  if (typeof openDetailsModal === "function") {';
echo '    document.getElementById("testBtn").style.display = "block";';
echo '  }';
echo '});';
echo '</script>';

echo '<button id="testBtn" onclick="openDetailsModal(999, 10, \'Produit Test\')" style="display:none; padding:10px; background:#17a2b8; color:white; border:none; border-radius:3px; cursor:pointer;">🧪 Tester le modal</button>';

echo "<h2>📊 État de la base de données</h2>";

// Vérifier la table
$sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."commandedet_details'";
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    echo "<p style='color:green'>✅ Table llx_commandedet_details existe</p>";
    
    // Compter les enregistrements
    $sql_count = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."commandedet_details";
    $resql_count = $db->query($sql_count);
    if ($resql_count) {
        $obj = $db->fetch_object($resql_count);
        echo "<p>📊 Nombre d'enregistrements: " . $obj->nb . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ Table llx_commandedet_details n'existe pas</p>";
}

echo "<h2>🔗 Liens utiles</h2>";
echo "<ul>";
echo "<li><a href='debug_diagnostic.php'>📋 Diagnostic complet</a></li>";
echo "<li><a href='../../../commande/card.php?action=create'>🆕 Créer une commande de test</a></li>";
echo "<li><a href='../../../commande/list.php'>📋 Liste des commandes</a></li>";
echo "</ul>";

echo "<p style='margin-top:30px; font-size:12px; color:#666;'>Généré le " . date('Y-m-d H:i:s') . "</p>";

echo "</body></html>";
?>