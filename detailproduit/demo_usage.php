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
 * \file    demo_usage.php
 * \ingroup detailproduit
 * \brief   Demonstration of module usage with examples
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
if (!$user->hasRight('detailproduit', 'details', 'read')) {
    accessforbidden();
}

// Headers
header('Content-Type: text/html; charset=utf-8');

print '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Démonstration - Module Détails Produit</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .demo-section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .demo-title { font-size: 20px; font-weight: bold; color: #2c5aa0; margin-bottom: 15px; }
        .demo-example { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .demo-code { background: #f1f3f4; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; margin: 10px 0; }
        .demo-result { background: #e8f5e8; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .demo-table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        .demo-table th, .demo-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .demo-table th { background: #f1f3f4; font-weight: bold; }
        .demo-highlight { background: #fff3cd; padding: 2px 4px; border-radius: 2px; }
        .demo-success { color: #28a745; font-weight: bold; }
        .demo-info { color: #17a2b8; font-weight: bold; }
        .demo-warning { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>';

print '<h1>🎯 Démonstration - Module Détails Produit</h1>';

print '<div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
print '<strong>ℹ️ À propos de cette démonstration</strong><br>';
print 'Cette page présente les fonctionnalités du module Détails Produit avec des exemples concrets d\'utilisation.';
print '</div>';

// Section 1: Concept général
print '<div class="demo-section">';
print '<div class="demo-title">1️⃣ Concept général du module</div>';

print '<p><strong>Problématique résolue :</strong></p>';
print '<p>Dans Dolibarr, une ligne de commande affiche généralement une quantité globale. 
Ce module permet de détailler cette quantité en spécifiant les dimensions de chaque pièce.</p>';

print '<div class="demo-example">';
print '<strong>Exemple typique :</strong><br>';
print '🔹 <strong>Commande :</strong> 5000mm de tube acier<br>';
print '🔹 <strong>Détail :</strong> 2 × 2000mm + 1 × 1000mm<br>';
print '🔹 <strong>Résultat :</strong> Traçabilité précise des découpes';
print '</div>';

print '</div>';

// Section 2: Calculs automatiques
print '<div class="demo-section">';
print '<div class="demo-title">2️⃣ Calculs automatiques</div>';

print '<p>Le module calcule automatiquement l\'unité et la valeur selon les dimensions saisies :</p>';

print '<table class="demo-table">';
print '<tr><th>Dimensions saisies</th><th>Unité calculée</th><th>Formule</th><th>Exemple</th></tr>';
print '<tr>';
print '<td>Longueur ET Largeur</td>';
print '<td><span class="demo-highlight">m²</span></td>';
print '<td>Nb pièces × (L/1000) × (l/1000)</td>';
print '<td>2 pcs × 2000mm × 1000mm = <strong>4,000 m²</strong></td>';
print '</tr>';
print '<tr>';
print '<td>Longueur OU Largeur</td>';
print '<td><span class="demo-highlight">ml</span></td>';
print '<td>Nb pièces × (Dimension/1000)</td>';
print '<td>3 pcs × 1500mm = <strong>4,500 ml</strong></td>';
print '</tr>';
print '<tr>';
print '<td>Aucune dimension</td>';
print '<td><span class="demo-highlight">u</span></td>';
print '<td>Nb pièces</td>';
print '<td>5 pièces = <strong>5,000 u</strong></td>';
print '</tr>';
print '</table>';

// Test en direct
if (class_exists('CommandeDetDetails')) {
    print '<div class="demo-example">';
    print '<strong>🧮 Test des calculs en direct :</strong><br>';
    
    $test_cases = array(
        array('pieces' => 2, 'longueur' => 2000, 'largeur' => 1000, 'description' => '2 panneaux rectangulaires'),
        array('pieces' => 3, 'longueur' => 1500, 'largeur' => 0, 'description' => '3 barres linéaires'),
        array('pieces' => 5, 'longueur' => 0, 'largeur' => 0, 'description' => '5 pièces unitaires')
    );
    
    foreach ($test_cases as $test) {
        $result = CommandeDetDetails::calculateUnitAndValue($test['pieces'], $test['longueur'], $test['largeur']);
        print '<div class="demo-result">';
        print '▶️ ' . $test['description'] . ' → <strong>' . number_format($result['total_value'], 3, ',', ' ') . ' ' . $result['unit'] . '</strong>';
        print '</div>';
    }
    
    print '</div>';
}

print '</div>';

// Section 3: Interface utilisateur
print '<div class="demo-section">';
print '<div class="demo-title">3️⃣ Interface utilisateur</div>';

print '<p><strong>Accès à l\'interface :</strong></p>';
print '<ol>';
print '<li>Créer une commande client et ajouter un produit</li>';
print '<li>Cliquer sur le bouton <span class="demo-highlight">📋 Détails</span> à côté de la ligne produit</li>';
print '<li>Une popup s\'ouvre avec un mini-tableur</li>';
print '</ol>';

print '<p><strong>Navigation optimisée :</strong></p>';
print '<ul>';
print '<li><strong>Tab :</strong> Navigation horizontale (cellule suivante)</li>';
print '<li><strong>Entrée :</strong> Navigation verticale (même colonne, ligne suivante)</li>';
print '<li><strong>Nouvelles lignes :</strong> Création automatique en fin de tableau</li>';
print '</ul>';

print '<div class="demo-example">';
print '<strong>🎯 Fonctionnalités avancées :</strong><br>';
print '🔹 <strong>Tri des colonnes :</strong> Clic sur les en-têtes<br>';
print '🔹 <strong>Calcul automatique :</strong> Mise à jour en temps réel<br>';
print '🔹 <strong>Export CSV :</strong> Téléchargement des détails<br>';
print '🔹 <strong>Synchronisation :</strong> Mise à jour de la quantité commande<br>';
print '🔹 <strong>Validation :</strong> Contrôle de cohérence des données';
print '</div>';

print '</div>';

// Section 4: Exemple concret d'utilisation
print '<div class="demo-section">';
print '<div class="demo-title">4️⃣ Exemple concret d\'utilisation</div>';

print '<p><strong>Scénario :</strong> Commande de tubes acier pour un projet de construction</p>';

print '<div class="demo-example">';
print '<strong>Données de la commande :</strong><br>';
print '• <strong>Produit :</strong> Tube acier rectangulaire<br>';
print '• <strong>Quantité totale :</strong> 5000mm<br>';
print '• <strong>Prix unitaire :</strong> 2,50€/mm<br>';
print '• <strong>Total ligne :</strong> 12 500,00€';
print '</div>';

print '<p><strong>Détails de découpe :</strong></p>';

print '<table class="demo-table">';
print '<tr><th>Nb pièces</th><th>Longueur (mm)</th><th>Largeur (mm)</th><th>Total</th><th>Unité</th><th>Description</th></tr>';
print '<tr><td>2</td><td>2000</td><td>50</td><td>0,200</td><td>m²</td><td>Panneaux rectangulaires</td></tr>';
print '<tr><td>1</td><td>1000</td><td>-</td><td>1,000</td><td>ml</td><td>Barre linéaire</td></tr>';
print '<tr><td>5</td><td>-</td><td>-</td><td>5,000</td><td>u</td><td>Pièces à l\'unité</td></tr>';
print '<tr style="background: #f8f9fa; font-weight: bold;"><td colspan="3">TOTAL</td><td colspan="3">8 pièces (0,200 m² + 1,000 ml + 5,000 u)</td></tr>';
print '</table>';

print '<div class="demo-result">';
print '<span class="demo-success">✅ Résultat :</span> La quantité de 5000mm est répartie en 8 pièces avec des dimensions précises pour la production.';
print '</div>';

print '</div>';

// Section 5: Intégration avec d'autres modules
print '<div class="demo-section">';
print '<div class="demo-title">5️⃣ Intégration avec d\'autres modules</div>';

print '<p>Le module Détails Produit peut être utilisé par d\'autres modules Dolibarr :</p>';

print '<div class="demo-example">';
print '<strong>🏭 Module Production :</strong><br>';
print '<div class="demo-code">';
print '// Récupérer les détails pour optimiser la découpe<br>';
print '$details = $details_obj->getDetailsForLine($commandedet_id);<br>';
print 'foreach ($details as $detail) {<br>';
print '&nbsp;&nbsp;&nbsp;&nbsp;// Optimiser les chutes, calculer le gaspillage<br>';
print '}';
print '</div>';
print '</div>';

print '<div class="demo-example">';
print '<strong>📦 Module Expédition :</strong><br>';
print '<div class="demo-code">';
print '// Générer des étiquettes par pièce<br>';
print 'foreach ($details as $detail) {<br>';
print '&nbsp;&nbsp;&nbsp;&nbsp;// Créer étiquette avec dimensions + code-barres<br>';
print '&nbsp;&nbsp;&nbsp;&nbsp;$label = $detail[\'pieces\'] . \' × \' . $detail[\'longueur\'] . \'mm\';<br>';
print '}';
print '</div>';
print '</div>';

print '<div class="demo-example">';
print '<strong>💰 Module Facturation :</strong><br>';
print '<div class="demo-code">';
print '// Facturation détaillée<br>';
print '$desc = "2 pièces × 2000mm × 50mm = 0,200 m²\\n";<br>';
print '$desc .= "1 pièce × 1000mm = 1,000 ml";';
print '</div>';
print '</div>';

print '</div>';

// Section 6: API pour développeurs
print '<div class="demo-section">';
print '<div class="demo-title">6️⃣ API pour développeurs</div>';

print '<p><strong>Principales méthodes de la classe CommandeDetDetails :</strong></p>';

print '<div class="demo-code">';
print '// Récupérer les détails d\'une ligne de commande<br>';
print '$details = $detailsObj->getDetailsForLine($commandedet_id);<br><br>';

print '// Sauvegarder les détails<br>';
print '$result = $detailsObj->saveDetailsForLine($commandedet_id, $details_array, $user);<br><br>';

print '// Calculer les totaux par unité<br>';
print '$totals = $detailsObj->getTotalsByUnit($commandedet_id);<br><br>';

print '// Mettre à jour la quantité de commande<br>';
print '$result = $detailsObj->updateCommandLineQuantity($commandedet_id, $new_qty, $unit);<br><br>';

print '// Calculer l\'unité et la valeur<br>';
print '$calc = CommandeDetDetails::calculateUnitAndValue($pieces, $longueur, $largeur);<br>';
print '// Retourne: array(\'unit\' => \'m²\', \'total_value\' => 4.0)';
print '</div>';

print '<p><strong>Endpoints AJAX disponibles :</strong></p>';

print '<ul>';
print '<li><code>GET /ajax/details_handler.php?action=get_details</code> - Récupérer les détails</li>';
print '<li><code>POST /ajax/details_handler.php action=save_details</code> - Sauvegarder les détails</li>';
print '<li><code>POST /ajax/details_handler.php action=update_command_quantity</code> - Mettre à jour la quantité</li>';
print '<li><code>GET /ajax/details_handler.php?action=export_details_csv</code> - Exporter en CSV</li>';
print '</ul>';

print '</div>';

// Section 7: Conseils d'utilisation
print '<div class="demo-section">';
print '<div class="demo-title">7️⃣ Conseils d\'utilisation</div>';

print '<div class="demo-example">';
print '<span class="demo-info">💡 Bonnes pratiques :</span><br>';
print '• Saisissez toujours le <strong>nombre de pièces</strong> en premier<br>';
print '• Utilisez des <strong>descriptions claires</strong> pour identifier les pièces<br>';
print '• Vérifiez la <strong>cohérence des totaux</strong> avant sauvegarde<br>';
print '• Utilisez l\'<strong>export CSV</strong> pour documenter les découpes<br>';
print '• Mettez à jour la <strong>quantité commande</strong> après finalisation';
print '</div>';

print '<div class="demo-example">';
print '<span class="demo-warning">⚠️ Points d\'attention :</span><br>';
print '• Les dimensions sont en <strong>millimètres</strong><br>';
print '• La suppression des détails est <strong>irréversible</strong><br>';
print '• Les permissions Dolibarr sont <strong>respectées</strong><br>';
print '• Les calculs sont <strong>automatiques</strong> (pas de saisie manuelle)<br>';
print '• Le tri peut <strong>réorganiser</strong> les lignes';
print '</div>';

print '</div>';

// Section finale
print '<div class="demo-section" style="background: #f0f8ff; border-color: #4682b4;">';
print '<div class="demo-title">🎉 Prêt à utiliser le module ?</div>';

print '<p><strong>Étapes suivantes :</strong></p>';
print '<ol>';
print '<li>Assurez-vous que le module est <strong>activé</strong></li>';
print '<li>Vérifiez vos <strong>permissions</strong> utilisateur</li>';
print '<li>Créez une <strong>commande test</strong> avec un produit</li>';
print '<li>Cliquez sur le bouton <strong>📋 Détails</strong></li>';
print '<li>Saisissez vos premiers <strong>détails de dimensions</strong></li>';
print '</ol>';

print '<div style="text-align: center; margin-top: 20px;">';
print '<a href="' . DOL_URL_ROOT . '/commande/card.php?action=create" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">🚀 Créer une commande test</a>';
print '</div>';

print '</div>';

print '<hr>';
print '<p><small>Démonstration générée le ' . date('d/m/Y à H:i:s') . ' - Module Détails Produit v1.0</small></p>';

print '</body></html>';
