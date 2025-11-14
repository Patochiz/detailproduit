// 🔍 SCRIPT DE DIAGNOSTIC LABEL_UPDATE.JS
// Copiez-collez ce script dans la console du navigateur (F12 → Console)
// sur une page de commande Dolibarr

console.log('===========================================');
console.log('🔍 DIAGNOSTIC LABEL_UPDATE.JS');
console.log('===========================================\n');

// Test 1: Vérifier les fonctions globales
console.log('📋 TEST 1: Fonctions globales');
console.log('-------------------------------------------');

const labelFunctions = Object.keys(window).filter(k => 
    k.toLowerCase().includes('label') || k.toLowerCase().includes('modal')
);

console.log('Fonctions trouvées avec "label" ou "modal":', labelFunctions.length);
labelFunctions.forEach(fn => {
    console.log('  -', fn, ':', typeof window[fn]);
});

console.log('\n📋 TEST 2: Fonctions spécifiques attendues');
console.log('-------------------------------------------');

const expectedFunctions = [
    'openLabelUpdateModal',
    'closeLabelUpdateModal',
    'saveLabelUpdate'
];

let allFound = true;
expectedFunctions.forEach(fn => {
    const exists = typeof window[fn] === 'function';
    const status = exists ? '✅' : '❌';
    console.log(status, fn + ':', typeof window[fn]);
    if (!exists) allFound = false;
});

console.log('\n📋 TEST 3: Variables globales');
console.log('-------------------------------------------');
console.log('window.DOL_URL_ROOT:', window.DOL_URL_ROOT || '❌ NON DÉFINI');
console.log('window.token:', window.token ? window.token.substring(0, 15) + '...' : '❌ NON DÉFINI');
console.log('window.newtoken:', window.newtoken ? window.newtoken.substring(0, 15) + '...' : '❌ NON DÉFINI');

console.log('\n📋 TEST 4: Vérification des scripts chargés');
console.log('-------------------------------------------');

const scripts = Array.from(document.querySelectorAll('script[src]'));
const detailproduitScripts = scripts.filter(s => s.src.includes('detailproduit'));

console.log('Scripts detailproduit trouvés:', detailproduitScripts.length);
detailproduitScripts.forEach(script => {
    const src = script.src;
    const loaded = script.readyState === undefined || script.readyState === 'complete';
    const status = loaded ? '✅' : '⏳';
    console.log(status, src);
});

const labelScript = scripts.find(s => s.src.includes('label_update.js'));
if (labelScript) {
    console.log('✅ Script label_update.js trouvé dans le DOM:', labelScript.src);
} else {
    console.log('❌ Script label_update.js NON trouvé dans le DOM');
}

console.log('\n📋 TEST 5: Vérification des modals');
console.log('-------------------------------------------');

const labelModal = document.getElementById('labelUpdateModal');
const detailsModal = document.getElementById('detailsModal');

console.log('Modal labelUpdateModal:', labelModal ? '✅ Existe' : '❌ N\'existe pas');
console.log('Modal detailsModal:', detailsModal ? '✅ Existe' : '❌ N\'existe pas');

console.log('\n📋 TEST 6: Test de chargement direct');
console.log('-------------------------------------------');

const baseUrl = window.DOL_URL_ROOT || '/doli';
const testUrl = baseUrl + '/custom/detailproduit/js/label_update.js';

console.log('URL à tester:', testUrl);
console.log('Lancement du test fetch...');

fetch(testUrl, { method: 'HEAD' })
    .then(response => {
        console.log('  Status:', response.status, response.statusText);
        if (response.ok) {
            console.log('  ✅ Fichier accessible');
        } else {
            console.log('  ❌ Fichier inaccessible (erreur HTTP)');
        }
        
        return fetch(testUrl);
    })
    .then(response => response.text())
    .then(content => {
        console.log('  Taille du fichier:', content.length, 'caractères');
        
        // Vérifier les signatures
        const hasOpenFunction = content.includes('window.openLabelUpdateModal');
        const hasCloseFunction = content.includes('window.closeLabelUpdateModal');
        const hasSaveFunction = content.includes('window.saveLabelUpdate');
        
        console.log('  Contient window.openLabelUpdateModal:', hasOpenFunction ? '✅' : '❌');
        console.log('  Contient window.closeLabelUpdateModal:', hasCloseFunction ? '✅' : '❌');
        console.log('  Contient window.saveLabelUpdate:', hasSaveFunction ? '✅' : '❌');
        
        // Extraire les premières lignes
        const firstLines = content.split('\n').slice(0, 5).join('\n');
        console.log('  Premières lignes du fichier:\n', firstLines);
    })
    .catch(error => {
        console.log('  ❌ Erreur de chargement:', error.message);
    });

console.log('\n===========================================');
console.log('📊 RÉSUMÉ DU DIAGNOSTIC');
console.log('===========================================');

setTimeout(() => {
    if (allFound) {
        console.log('%c✅ SUCCÈS: Toutes les fonctions sont chargées!', 'color: green; font-weight: bold; font-size: 14px;');
        console.log('%cVous pouvez maintenant tester: openLabelUpdateModal(1, 2, "Test")', 'color: blue;');
    } else {
        console.log('%c❌ PROBLÈME: Les fonctions ne sont pas chargées', 'color: red; font-weight: bold; font-size: 14px;');
        console.log('\n🔍 ACTIONS À ENTREPRENDRE:');
        console.log('1. Vérifiez que le fichier label_update.js est bien uploadé sur le serveur');
        console.log('2. Videz le cache du navigateur (Ctrl + Shift + R)');
        console.log('3. Videz le cache Dolibarr (Configuration → Purger le cache)');
        console.log('4. Vérifiez que le fichier se charge dans l\'onglet Network (F12 → Network → Rechargez la page)');
        console.log('\n📄 Consultez le fichier DIAGNOSTIC_LABEL_LOADING.md pour plus de détails');
    }
    
    console.log('\n===========================================');
}, 2000);

// Retourner un objet avec les résultats
({
    labelFunctions: labelFunctions,
    allFunctionsFound: allFound,
    scriptInDOM: !!labelScript,
    modalExists: !!labelModal,
    testUrl: testUrl
});
