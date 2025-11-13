<?php
/* 
 * Fichier à inclure dans le hook pour forcer l'injection des variables
 * Variables globales JavaScript pour module Détails Produit
 */

if (!defined('DOL_URL_ROOT') || !isset($user)) {
    return '';
}

// Variables globales à injecter dans JavaScript
$dol_url_root = DOL_URL_ROOT;
$csrf_token = newToken();

// JavaScript à injecter
$js_injection = '
<script type="text/javascript">
// Variables globales Dolibarr pour module Détails Produit
window.DOL_URL_ROOT = window.DOL_URL_ROOT || "' . $dol_url_root . '";
window.token = window.token || "' . $csrf_token . '";

// Debug: afficher les variables
console.log("=== VARIABLES DOLIBARR INJECTÉES ===");
console.log("DOL_URL_ROOT:", window.DOL_URL_ROOT);
console.log("token:", window.token ? window.token.substring(0,10) + "..." : "UNDEFINED");

// Forcer la réinitialisation du module si déjà chargé
if (typeof initializeGlobalVariables === "function") {
    console.log("Forçage de la réinitialisation...");
    initializeGlobalVariables();
}
</script>';

echo $js_injection;