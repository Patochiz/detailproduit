<?php
/* Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
 *
 * Test d'intégration de la régénération automatique PDF
 */

require_once '../../../main.inc.php';

if (!$user->admin) {
    accessforbidden('Test réservé aux administrateurs');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Intégration PDF - Module DetailProduit</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin: 10px 0; 
            background: #f9f9f9; 
        }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        button { 
            padding: 8px 15px; 
            margin: 5px; 
            border: none; 
            border-radius: 3px; 
            cursor: pointer; 
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        #console-output { 
            background: #000; 
            color: #0f0; 
            padding: 10px; 
            height: 200px; 
            overflow-y: auto; 
            font-family: monospace; 
            font-size: 12px; 
        }
    </style>
</head>
<body>

<h1>🧪 Test d'Intégration PDF - Module DetailProduit</h1>

<div class="test-section">
    <h2>1. État de la page actuelle</h2>
    <p><strong>URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
    <p><strong>Page:</strong> <?php echo basename($_SERVER['SCRIPT_NAME']); ?></p>
    
    <div id="page-analysis">
        <p class="info">Analyse en cours...</p>
    </div>
</div>

<div class="test-section">
    <h2>2. Tests de détection PDF</h2>
    
    <button class="btn-primary" onclick="testPageDetection()">
        🔍 Détecter Page PDF
    </button>
    
    <button class="btn-warning" onclick="testButtonDetection()">
        🎯 Détecter Bouton GÉNÉRER
    </button>
    
    <button class="btn-success" onclick="simulatePDFGeneration()">
        ⚡ Simuler Génération PDF
    </button>
    
    <div id="test-results" style="margin-top: 10px;">
        <!-- Résultats des tests -->
    </div>
</div>

<div class="test-section">
    <h2>3. Simulation de boutons PDF (pour test)</h2>
    <p class="info">Ces boutons simulent la présence de boutons Dolibarr pour tester la détection :</p>
    
    <!-- Simulation boutons PDF Dolibarr -->
    <form name="formpdf" style="margin: 10px 0;">
        <select name="model">
            <option>COMMANDE CLIENT</option>
        </select>
        <input type="submit" value="GÉNÉRER" style="background: #17a2b8; color: white; padding: 5px 10px;">
    </form>
    
    <div class="tabsAction">
        <input type="submit" value="Générer PDF" onclick="alert('PDF généré !'); return false;" style="background: #28a745; color: white; padding: 5px 10px;">
    </div>
</div>

<div class="test-section">
    <h2>4. Console de debug</h2>
    <div id="console-output">
        Chargement du module de test...<br>
    </div>
    <button class="btn-primary" onclick="clearConsole()">🗑️ Vider Console</button>
</div>

<script>
// Surcharge console.log pour afficher dans notre div
const originalConsoleLog = console.log;
const consoleOutput = document.getElementById('console-output');

console.log = function(...args) {
    originalConsoleLog.apply(console, args);
    
    const message = args.map(arg => 
        typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
    ).join(' ');
    
    const timestamp = new Date().toLocaleTimeString();
    consoleOutput.innerHTML += `[${timestamp}] ${message}<br>`;
    consoleOutput.scrollTop = consoleOutput.scrollHeight;
};

function clearConsole() {
    consoleOutput.innerHTML = '';
}

function addResult(message, type = 'info') {
    const resultsDiv = document.getElementById('test-results');
    const className = type === 'success' ? 'success' : type === 'error' ? 'error' : 'info';
    resultsDiv.innerHTML += `<p class="${className}">✓ ${message}</p>`;
}

function testPageDetection() {
    console.log('🧪 Test détection de page PDF...');
    
    if (typeof isPDFGenerationPage === 'function') {
        const result = isPDFGenerationPage();
        if (result) {
            addResult('Page avec génération PDF détectée', 'success');
            console.log('✅ Page PDF détectée');
        } else {
            addResult('Page sans génération PDF', 'info');
            console.log('ℹ️ Page non-PDF');
        }
    } else {
        addResult('Fonction isPDFGenerationPage non disponible', 'error');
        console.log('❌ Fonction manquante');
    }
}

function testButtonDetection() {
    console.log('🧪 Test détection bouton GÉNÉRER...');
    
    if (typeof testPDFButtonDetection === 'function') {
        addResult('Test de détection lancé (voir console)', 'info');
        testPDFButtonDetection();
    } else {
        addResult('Fonction testPDFButtonDetection non disponible', 'error');
        console.log('❌ Fonction de test manquante');
    }
}

function simulatePDFGeneration() {
    console.log('🧪 Simulation génération PDF...');
    
    if (typeof triggerPDFRegeneration === 'function') {
        const result = triggerPDFRegeneration();
        if (result) {
            addResult('Simulation réussie', 'success');
        } else {
            addResult('Simulation échouée (normal si pas de bouton valide)', 'info');
        }
    } else {
        addResult('Fonction triggerPDFRegeneration non disponible', 'error');
        console.log('❌ Fonction de simulation manquante');
    }
}

// Analyse automatique de la page au chargement
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Analyse automatique de la page...');
    
    const analysisDiv = document.getElementById('page-analysis');
    let analysis = '<ul>';
    
    // Vérifier la présence de boutons GÉNÉRER
    const generateButtons = document.querySelectorAll('input[type="submit"][value*="GÉNÉRER"], input[type="submit"][value*="Générer"]');
    analysis += `<li>Boutons GÉNÉRER trouvés: <strong>${generateButtons.length}</strong></li>`;
    
    // Vérifier la présence de formulaires PDF
    const pdfForms = document.querySelectorAll('form[name="formpdf"]');
    analysis += `<li>Formulaires PDF trouvés: <strong>${pdfForms.length}</strong></li>`;
    
    // Vérifier la présence de sélecteurs de modèle
    const modelSelects = document.querySelectorAll('select[name="model"]');
    analysis += `<li>Sélecteurs de modèle trouvés: <strong>${modelSelects.length}</strong></li>`;
    
    // Vérifier si les fonctions du module sont disponibles
    const functions = ['isPDFGenerationPage', 'triggerPDFRegeneration', 'testPDFButtonDetection'];
    const availableFunctions = functions.filter(func => typeof window[func] === 'function');
    analysis += `<li>Fonctions PDF disponibles: <strong>${availableFunctions.length}/${functions.length}</strong></li>`;
    
    analysis += '</ul>';
    
    if (availableFunctions.length === functions.length) {
        analysis += '<p class="success">✅ Module PDF entièrement chargé</p>';
    } else {
        analysis += '<p class="error">❌ Module PDF partiellement chargé</p>';
        analysis += '<p><em>Assurez-vous que details_popup.js est bien inclus</em></p>';
    }
    
    analysisDiv.innerHTML = analysis;
    
    console.log('📊 Analyse terminée');
    console.log('Fonctions disponibles:', availableFunctions);
});

</script>

<!-- Inclure le module JavaScript (chemin relatif) -->
<script src="../js/details_popup.js"></script>

<div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 5px;">
    <h3>💡 Comment utiliser ce test</h3>
    <ol>
        <li><strong>Vérifiez l'analyse automatique</strong> - Elle doit montrer "Module PDF entièrement chargé"</li>
        <li><strong>Testez la détection de page</strong> - Cliquez sur "Détecter Page PDF"</li>
        <li><strong>Testez la détection de bouton</strong> - Cliquez sur "Détecter Bouton GÉNÉRER"</li>
        <li><strong>Simulez la génération</strong> - Cliquez sur "Simuler Génération PDF"</li>
        <li><strong>Consultez la console</strong> pour voir les détails des opérations</li>
    </ol>
    
    <p><strong>Note:</strong> Pour tester en conditions réelles, allez sur une page de commande Dolibarr et utilisez la console du navigateur avec <code>testPDFButtonDetection()</code></p>
</div>

</body>
</html>
