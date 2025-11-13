<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 * 
 * Script de diagnostic pour le module Détails Produit
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

// Access control - uniquement admin pour la sécurité
if (!$user->admin) {
    accessforbidden();
}

header('Content-Type: text/html; charset=utf-8');

print '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Diagnostic AJAX - Module Détails Produit</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-ok { color: green; font-weight: bold; }
        .test-error { color: red; font-weight: bold; }
        .test-warning { color: orange; font-weight: bold; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; }
        button { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        #results { margin-top: 20px; }
    </style>
</head>
<body>';

print '<h1>🔧 Diagnostic AJAX - Module Détails Produit</h1>';

// Informations de base
print '<div class="section">';
print '<h2>📋 Informations du système</h2>';
print '<p><strong>Utilisateur:</strong> ' . $user->login . ' (ID: ' . $user->id . ')</p>';
print '<p><strong>Permissions commande:</strong> ' . ($user->hasRight('commande', 'lire') ? '✅ Lecture' : '❌ Lecture') . ' / ' . ($user->hasRight('commande', 'creer') ? '✅ Écriture' : '❌ Écriture') . '</p>';
print '<p><strong>Module activé:</strong> ' . (isModEnabled('detailproduit') ? '✅ Oui' : '❌ Non') . '</p>';
print '<p><strong>Session ID:</strong> ' . session_id() . '</p>';
print '<p><strong>Token session:</strong> ' . substr($_SESSION['newtoken'] ?? 'undefined', 0, 10) . '...</p>';
print '</div>';

// Test des variables JavaScript
print '<div class="section">';
print '<h2>🔗 Variables JavaScript</h2>';
print '<div class="code">';
print 'var DOL_URL_ROOT = "' . DOL_URL_ROOT . '";<br>';
print 'var token = "' . newToken() . '";<br>';
print '</div>';
print '<p>Ces variables doivent être disponibles dans le JavaScript de la page.</p>';
print '</div>';

// URL AJAX
$ajax_url = DOL_URL_ROOT . '/custom/detailproduit/ajax/details_handler.php';
print '<div class="section">';
print '<h2>📡 URL AJAX</h2>';
print '<p><strong>URL complète:</strong> <code>' . $ajax_url . '</code></p>';
print '<p><strong>Fichier existe:</strong> ' . (file_exists(DOL_DOCUMENT_ROOT . '/custom/detailproduit/ajax/details_handler.php') ? '✅ Oui' : '❌ Non') . '</p>';
print '</div>';

// Test AJAX avec JavaScript
print '<div class="section">';
print '<h2>🧪 Test AJAX</h2>';
print '<p>Cliquez sur les boutons ci-dessous pour tester les requêtes AJAX :</p>';
print '<button onclick="testAjaxCall(\'get_details\', {commandedet_id: 999})">Test GET_DETAILS</button>';
print '<button onclick="testAjaxCall(\'save_details\', {commandedet_id: 999, details_json: \'[{\"pieces\":1,\"description\":\"Test\"}]\'})">Test SAVE_DETAILS</button>';
print '<button onclick="clearResults()">Effacer résultats</button>';
print '<div id="results"></div>';
print '</div>';

// Logs PHP récents
print '<div class="section">';
print '<h2>📝 Logs récents</h2>';
print '<p>Vérifiez les logs PHP pour voir les messages de debug du module :</p>';
print '<div class="code">';
print 'Chemin logs PHP: ' . ini_get('error_log') . '<br>';
print 'Log Dolibarr: ' . DOL_DATA_ROOT . '/dolibarr.log<br>';
print '</div>';
print '</div>';

print '<script>
var ajaxUrl = "' . $ajax_url . '";
var token = "' . newToken() . '";

function testAjaxCall(action, params) {
    var results = document.getElementById("results");
    
    results.innerHTML += "<h3>Test " + action + "</h3>";
    results.innerHTML += "<p>Envoi de la requête...</p>";
    
    var formData = new FormData();
    formData.append("action", action);
    formData.append("token", token);
    
    for(var key in params) {
        formData.append(key, params[key]);
    }
    
    results.innerHTML += "<p><strong>URL:</strong> " + ajaxUrl + "</p>";
    results.innerHTML += "<p><strong>Action:</strong> " + action + "</p>";
    results.innerHTML += "<p><strong>Token:</strong> " + token.substring(0, 10) + "...</p>";
    
    fetch(ajaxUrl, {
        method: "POST", 
        body: formData
    })
    .then(response => {
        results.innerHTML += "<p><strong>Status:</strong> " + response.status + " " + response.statusText + "</p>";
        return response.text();
    })
    .then(text => {
        results.innerHTML += "<p><strong>Réponse brute:</strong></p>";
        results.innerHTML += "<div class=\"code\">" + text.substring(0, 500) + (text.length > 500 ? "..." : "") + "</div>";
        
        try {
            var json = JSON.parse(text);
            results.innerHTML += "<p><strong>JSON parsé:</strong></p>";
            results.innerHTML += "<div class=\"code\">" + JSON.stringify(json, null, 2) + "</div>";
        } catch(e) {
            results.innerHTML += "<p><strong>Erreur JSON:</strong> " + e.message + "</p>";
        }
    })
    .catch(error => {
        results.innerHTML += "<p><strong>Erreur réseau:</strong> " + error.message + "</p>";
    });
    
    results.innerHTML += "<hr>";
}

function clearResults() {
    document.getElementById("results").innerHTML = "";
}
</script>';

print '</body></html>';