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
 * \file    js/details_popup.js
 * \ingroup detailproduit
 * \brief   JavaScript for product details popup management
 */

// Variables globales
let currentCommandedetId = null;
let currentTotalQuantity = 0;
let currentProductName = '';
let rowCounter = 0;
let sortColumn = -1;
let sortDirection = 'asc';
let isLoading = false;

// Token CSRF et URL de base (récupérés des variables globales PHP)
let detailsToken = '';
let ajaxUrl = '';

// Configuration pour debug
const DEBUG_MODE = true;

/**
 * SYSTÈME DE RÉGÉNÉRATION AUTOMATIQUE DU PDF
 * Détecte et simule le clic sur le bouton GÉNÉRER après sauvegarde
 */

/**
 * Détecter et simuler la génération PDF
 */
function triggerPDFRegeneration() {
    console.log('🔄 Recherche du bouton de génération PDF...');
    
    // Méthodes pour trouver le bouton GÉNÉRER
    const pdfGenerateSelectors = [
        'input[type="submit"][value*="GÉNÉRER"]',
        'input[type="submit"][value*="Générer"]', 
        'input[type="submit"][value*="générer"]',
        'button[onclick*="generate"]',
        'button[onclick*="pdf"]',
        'input[name="action"][value="builddoc"] ~ input[type="submit"]',
        'form[name="formpdf"] input[type="submit"]',
        '.fiche .tabsAction input[type="submit"]'
    ];
    
    let generateButton = null;
    
    // Essayer chaque sélecteur
    for (let selector of pdfGenerateSelectors) {
        const buttons = document.querySelectorAll(selector);
        for (let button of buttons) {
            // Vérifier que c'est bien un bouton de génération PDF
            const buttonText = button.value || button.textContent || '';
            if (buttonText.toLowerCase().includes('génér') || 
                buttonText.toLowerCase().includes('generat') ||
                buttonText.toLowerCase().includes('créer') ||
                buttonText.toLowerCase().includes('pdf')) {
                
                // Vérifier que le bouton est visible et activé
                if (button.offsetParent !== null && !button.disabled) {
                    generateButton = button;
                    console.log('✅ Bouton PDF trouvé:', buttonText, selector);
                    break;
                }
            }
        }
        if (generateButton) break;
    }
    
    if (!generateButton) {
        console.log('⚠️ Bouton de génération PDF non trouvé');
        // Fallback : actualiser la page normale
        setTimeout(() => {
            console.log('🔄 Actualisation de la page (fallback)...');
            window.location.reload();
        }, 1000);
        return false;
    }
    
    console.log('🎯 Simulation du clic sur le bouton PDF...');
    
    // Simuler le clic sur le bouton
    try {
        // Méthode 1 : Clic direct
        generateButton.click();
        console.log('✅ Clic simulé avec succès');
        return true;
        
    } catch (error) {
        console.log('⚠️ Erreur clic direct, tentative alternative...', error);
        
        // Méthode 2 : Événement manuel
        try {
            const clickEvent = new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
            });
            generateButton.dispatchEvent(clickEvent);
            console.log('✅ Événement click dispatché');
            return true;
            
        } catch (error2) {
            console.log('⚠️ Erreur événement, soumission manuelle...', error2);
            
            // Méthode 3 : Soumission de formulaire
            const form = generateButton.closest('form');
            if (form) {
                // S'assurer que l'action est correcte
                if (generateButton.name && generateButton.value) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = generateButton.name;
                    hiddenInput.value = generateButton.value;
                    form.appendChild(hiddenInput);
                }
                
                form.submit();
                console.log('✅ Formulaire soumis manuellement');
                return true;
            }
        }
    }
    
    console.log('❌ Impossible de simuler la génération PDF');
    return false;
}

/**
 * Fonction pour détecter si on est sur une page avec génération PDF
 */
function isPDFGenerationPage() {
    // Vérifier la présence d'éléments caractéristiques
    const indicators = [
        'input[type="submit"][value*="GÉNÉRER"]',
        'form[name="formpdf"]',
        '.fichethirdleft .fichecenter', // Zone des fichiers joints
        'select[name="model"]' // Sélecteur de modèle de document
    ];
    
    return indicators.some(selector => document.querySelector(selector) !== null);
}

/**
 * Fonction de test pour vérifier la détection du bouton PDF
 */
function testPDFButtonDetection() {
    console.log('🧪 Test de détection du bouton PDF...');
    
    const result = triggerPDFRegeneration();
    
    if (result) {
        console.log('✅ Test réussi - Le bouton PDF a été détecté et activé');
    } else {
        console.log('❌ Test échoué - Bouton PDF non trouvé ou non activable');
        
        // Afficher tous les boutons et formulaires pour diagnostic
        console.log('🔍 Diagnostic - Boutons trouvés:');
        document.querySelectorAll('input[type="submit"], button').forEach((btn, index) => {
            console.log(`${index}: "${btn.value || btn.textContent}" - ${btn.tagName} - Visible: ${btn.offsetParent !== null}`);
        });
        
        console.log('🔍 Diagnostic - Formulaires trouvés:');
        document.querySelectorAll('form').forEach((form, index) => {
            console.log(`${index}: name="${form.name}" action="${form.action}"`);
        });
    }
}

/**
 * Fonctions utilitaires pour récupérer les variables manquantes
 */
function findTokenInPage() {
    // Méthode 1: Chercher dans les variables globales injectées
    if (typeof token !== 'undefined' && token) {
        return token;
    }
    if (typeof newtoken !== 'undefined' && newtoken) {
        return newtoken;
    }
    
    // Méthode 2: Chercher dans les inputs hidden
    const tokenInputs = document.querySelectorAll('input[name="token"], input[name="newtoken"]');
    for (let input of tokenInputs) {
        if (input.value && input.value.length > 10) {
            return input.value;
        }
    }
    
    // Méthode 3: Chercher dans les formulaires
    const forms = document.querySelectorAll('form');
    for (let form of forms) {
        const formData = new FormData(form);
        if (formData.get('token')) {
            return formData.get('token');
        }
        if (formData.get('newtoken')) {
            return formData.get('newtoken');
        }
    }
    
    // Méthode 4: Chercher dans les meta tags
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        return metaToken.getAttribute('content');
    }
    
    // Méthode 5: Extraire depuis les liens avec token
    const linksWithToken = document.querySelectorAll('a[href*="token="]');
    for (let link of linksWithToken) {
        const match = link.href.match(/[?&]token=([^&]+)/);
        if (match && match[1].length > 10) {
            return match[1];
        }
    }
    
    return null;
}

function findBaseUrl() {
    // Méthode 1: Variable globale injectée
    if (typeof DOL_URL_ROOT !== 'undefined' && DOL_URL_ROOT) {
        return DOL_URL_ROOT;
    }
    
    // Méthode 2: Analyser l'URL courante
    const currentPath = window.location.pathname;
    
    // Chercher 'doli' ou 'dolibarr' dans le chemin
    const segments = currentPath.split('/');
    let baseSegments = [];
    
    for (let i = 0; i < segments.length; i++) {
        baseSegments.push(segments[i]);
        if (segments[i] === 'doli' || segments[i] === 'dolibarr') {
            break;
        }
    }
    
    if (baseSegments.length > 0 && baseSegments[baseSegments.length - 1] === 'doli') {
        return baseSegments.join('/');
    }
    
    // Méthode 3: Analyser les liens CSS/JS existants
    const scripts = document.querySelectorAll('script[src*="doli"], link[href*="doli"]');
    for (let script of scripts) {
        const src = script.src || script.href;
        const match = src.match(/^(.*\/doli)/);
        if (match) {
            return match[1].replace(window.location.origin, '');
        }
    }
    
    // Fallback
    return '/doli';
}

/**
 * Initialisation du module avec support PDF
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Initialisation du module detailproduit...');

    // Vérifier si label_update.js est chargé
    console.log('🔍 Vérification chargement label_update.js:', {
        openLabelUpdateModal: typeof openLabelUpdateModal,
        closeLabelUpdateModal: typeof closeLabelUpdateModal,
        saveLabelUpdate: typeof saveLabelUpdate
    });

    // Initialiser les variables depuis les globales PHP
    initializeGlobalVariables();
    
    // Créer le modal s'il n'existe pas
    createDetailsModal();
    
    // Vérifier si on est sur une page compatible PDF
    if (isPDFGenerationPage()) {
        console.log('✅ Page avec génération PDF détectée');
        
        // Observer les changements pour détecter les nouveaux modals
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // Vérifier si un modal de détails a été ajouté
                    const detailsModal = document.getElementById('detailsModal');
                    if (detailsModal && !detailsModal.dataset.pdfEnhanced) {
                        detailsModal.dataset.pdfEnhanced = 'true';
                        
                        // Mettre à jour les boutons du modal
                        const saveButtons = detailsModal.querySelectorAll('button[onclick*="saveDetails"]');
                        saveButtons.forEach(button => {
                            // Changer le texte pour indiquer la régénération PDF
                            const originalText = button.innerHTML;
                            if (!originalText.includes('PDF')) {
                                button.innerHTML = originalText.replace('Sauvegarder', 'Sauvegarder & PDF');
                                button.title = 'Sauvegarder les détails et régénérer automatiquement le PDF';
                            }
                        });
                        
                        console.log('🎯 Modal de détails enrichi pour PDF');
                    }
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    } else {
        console.log('ℹ️ Page sans génération PDF - mode standard');
    }
    
    // Ajouter les boutons détails aux lignes existantes
    setTimeout(function() {
        addDetailsButtonsToExistingLines();
    }, 500);
});

/**
 * Initialiser les variables globales depuis PHP
 */
function initializeGlobalVariables() {
    console.log('🔍 Recherche des variables globales...');
    
    // Token CSRF depuis différentes sources
    detailsToken = findTokenInPage();
    
    // URL de base
    const baseUrl = findBaseUrl();
    ajaxUrl = baseUrl + '/custom/detailproduit/ajax/details_handler.php';
    
    console.log('✅ Variables initialisées:', {
        token: detailsToken ? detailsToken.substring(0,10) + '...' : 'MANQUANT ❌',
        ajaxUrl: ajaxUrl,
        baseUrl: baseUrl,
        currentLocation: window.location.pathname
    });
    
    // Vérification critique
    if (!detailsToken) {
        console.error('❗ TOKEN MANQUANT - Recherche approfondie...');
        
        // Debug: afficher tous les inputs
        const allInputs = document.querySelectorAll('input[type="hidden"]');
        console.log('🔍 Inputs cachés trouvés:', Array.from(allInputs).map(input => ({
            name: input.name,
            value: input.value ? input.value.substring(0, 20) + '...' : 'VIDE'
        })));
        
        // Debug: afficher les variables globales
        console.log('🔍 Variables globales:', {
            'window.token': typeof window.token !== 'undefined' ? window.token.substring(0,10) + '...' : 'UNDEFINED',
            'window.newtoken': typeof window.newtoken !== 'undefined' ? window.newtoken.substring(0,10) + '...' : 'UNDEFINED',
            'window.DOL_URL_ROOT': typeof window.DOL_URL_ROOT !== 'undefined' ? window.DOL_URL_ROOT : 'UNDEFINED'
        });
    }
}

/**
 * Créer le modal de détails dans le DOM
 */
function createDetailsModal() {
    if (document.getElementById('detailsModal')) {
        return; // Modal déjà créé
    }

    const modalHTML = `
        <div id="detailsModal" class="details-modal">
            <div class="details-modal-content">
                <div class="details-modal-header">
                    <h3 id="detailsModalTitle">Détails du produit</h3>
                    <button class="details-modal-close" onclick="closeDetailsModal()">&times;</button>
                </div>
                
                <div class="details-modal-body">
                    <div class="details-summary-info">
                        <div><strong>Produit:</strong> <span id="detailsProductName"></span></div>
                        <div><strong>Quantité totale à répartir:</strong> <span id="detailsTotalQuantity"></span></div>
                    </div>

                    <div class="details-toolbar">
                        <button class="details-btn details-btn-success" onclick="addDetailsRow()">+ Ajouter une ligne</button>
                        <button class="details-btn" onclick="clearAllDetails()">🗑️ Vider tout</button>
                        <button class="details-btn details-btn-primary" onclick="updateCommandQuantity()">🔄 Mettre à jour la quantité commande</button>
                    </div>

                    <div class="details-spreadsheet-container">
                        <table class="details-spreadsheet-table" id="detailsTable">
                            <thead>
                                <tr>
                                    <th class="details-sortable-header details-col-pieces" onclick="sortDetailsTable(0)">
                                        Nb pièces
                                        <span class="details-sort-icon"></span>
                                    </th>
                                    <th class="details-sortable-header details-col-longueur" onclick="sortDetailsTable(1)">
                                        Longueur (mm)
                                        <span class="details-sort-icon"></span>
                                    </th>
                                    <th class="details-sortable-header details-col-largeur" onclick="sortDetailsTable(2)">
                                        Largeur (mm)
                                        <span class="details-sort-icon"></span>
                                    </th>
                                    <th class="details-col-total">Total <span class="details-unit-label" id="detailsUnitLabel">m²</span></th>
                                    <th class="details-sortable-header details-col-description" onclick="sortDetailsTable(4)">
                                        Description
                                        <span class="details-sort-icon"></span>
                                    </th>
                                    <th class="details-col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="detailsTableBody">
                                <!-- Les lignes seront générées par JS -->
                            </tbody>
                            <tfoot>
                                <tr class="details-total-row">
                                    <td><strong>Total:</strong></td>
                                    <td colspan="2" class="details-text-center"><strong id="detailsTotalPieces">0</strong> pièces</td>
                                    <td><strong id="detailsTotalQuantityDisplay">0</strong> <span id="detailsTotalUnit">m²</span></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div id="detailsValidationMessage" class="details-validation-message"></div>
                </div>

                <div class="details-modal-footer">
                    <div class="details-tip">
                        💡 Tip: Utilisez Tab pour naviguer horizontalement, Entrée pour naviguer verticalement
                    </div>
                    <div>
                        <button class="details-btn" onclick="closeDetailsModal()">Annuler</button>
                        <button class="details-btn details-btn-success" onclick="saveDetails()">💾 Sauvegarder</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Fermer le modal en cliquant à l'extérieur
    window.onclick = function(event) {
        const modal = document.getElementById('detailsModal');
        if (event.target === modal) {
            closeDetailsModal();
        }
    };
}

/**
 * Ajouter les boutons détails aux lignes de commande existantes
 */
function addDetailsButtonsToExistingLines() {
    console.log('🔍 Recherche des lignes de commande...');
    
    // Rechercher les lignes de commande avec plusieurs sélecteurs possibles
    const possibleSelectors = [
        '#tablelines tbody tr[id^="row-"]',  // Standard Dolibarr
        '.liste tbody tr[class*="oddeven"]', // Liste standard
        'table.liste tr[id^="row"]',         // Autre format
        '#tablelines tbody tr',              // Fallback générique
        '.liste_ligne_produit tr'            // Ancien format
    ];
    
    let commandLines = [];
    
    // Essayer chaque sélecteur jusqu'à trouver des lignes
    for (let selector of possibleSelectors) {
        commandLines = document.querySelectorAll(selector);
        if (commandLines.length > 0) {
            console.log('✅ Lignes trouvées avec sélecteur:', selector, '(', commandLines.length, 'lignes)');
            break;
        }
    }
    
    if (commandLines.length === 0) {
        console.log('❌ Aucune ligne de commande trouvée');
        return;
    }
    
    let buttonsAdded = 0;
    
    commandLines.forEach(function(line) {
        // Vérifier que c'est bien une ligne de produit (pas header/footer)
        if (line.classList.contains('liste_titre') || 
            line.classList.contains('liste_total') ||
            line.querySelector('th')) {
            return; // Skip headers and totals
        }
        
        const lineId = extractLineId(line);
        if (lineId) {
            addDetailsButtonToLine(lineId, line);
            buttonsAdded++;
        }
    });
    
    console.log('✅ Boutons détails ajoutés:', buttonsAdded);
}

/**
 * Extraire l'ID de ligne depuis l'élément DOM
 */
function extractLineId(lineElement) {
    // Méthode 1 : ID direct de l'élément (row-123)
    if (lineElement.id && lineElement.id.includes('row-')) {
        return lineElement.id.replace('row-', '');
    }
    
    // Méthode 2 : Attributs data
    if (lineElement.dataset && lineElement.dataset.lineId) {
        return lineElement.dataset.lineId;
    }
    
    // Méthode 3 : Input hidden avec l'ID ligne
    const hiddenInputs = lineElement.querySelectorAll('input[type="hidden"]');
    for (let input of hiddenInputs) {
        if (input.name && (input.name.includes('idprod') || input.name.includes('lineid'))) {
            if (input.value && !isNaN(input.value)) {
                return input.value;
            }
        }
    }
    
    // Méthode 4 : Chercher dans les liens d'édition
    const editLinks = lineElement.querySelectorAll('a[href*="action=editline"]');
    for (let link of editLinks) {
        const href = link.href;
        const match = href.match(/[?&]lineid=(\d+)/);
        if (match) {
            return match[1];
        }
    }
    
    // Méthode 5 : Chercher dans les inputs name="qty"
    const qtyInputs = lineElement.querySelectorAll('input[name*="qty"]');
    for (let input of qtyInputs) {
        const match = input.name.match(/qty(\d+)/);
        if (match) {
            return match[1];
        }
    }
    
    if (DEBUG_MODE) {
        console.log('⚠️ Impossible d\'extraire l\'ID de ligne pour:', lineElement);
    }
    
    return null;
}

/**
 * Ajouter un bouton détails à une ligne de commande
 */
function addDetailsButtonToLine(lineId, lineElement) {
    if (!lineElement) {
        lineElement = document.getElementById('row-' + lineId);
    }
    
    if (!lineElement) {
        if (DEBUG_MODE) {
            console.log('❌ Element de ligne non trouvé pour ID:', lineId);
        }
        return;
    }

    // Éviter les doublons
    if (lineElement.querySelector('.details-btn-open')) {
        return;
    }

    // Trouver la cellule d'actions (dernière cellule avec des boutons)
    let targetCell = lineElement.querySelector('.linecoledit') ||
                     lineElement.querySelector('.linecolaction') ||
                     lineElement.querySelector('td:last-child');
    
    // Si pas de cellule d'actions trouvée, créer une nouvelle cellule
    if (!targetCell || !targetCell.querySelector('a, button')) {
        // Chercher une cellule qui contient des liens/boutons
        const cells = lineElement.querySelectorAll('td');
        for (let i = cells.length - 1; i >= 0; i--) {
            if (cells[i].querySelector('a, button, .pictoedit')) {
                targetCell = cells[i];
                break;
            }
        }
    }
    
    if (targetCell) {
        // Détecter le type de produit et l'ID du tiers
        const productType = extractProductType(lineElement);
        const socid = extractSocid();
        
        const detailsButton = document.createElement('a');
        detailsButton.href = '#';
        detailsButton.className = 'details-btn-open';
        
        if (productType === 1) {
            // Service : bouton pour le popup de label
            detailsButton.title = 'Modifier le label du service';
            detailsButton.innerHTML = '🏷️';
            detailsButton.style.cssText = 'margin-left: 5px; text-decoration: none; font-size: 11px; padding: 2px 6px; background: #28a745; color: white; border-radius: 2px;';
            
            detailsButton.onclick = function(e) {
                e.preventDefault();
                const productName = extractProductName(lineElement);
                console.log('🏷️ Ouverture popup label pour service:', productName);

                // S'assurer que label_update.js est chargé
                if (typeof openLabelUpdateModal === 'function') {
                    console.log('✅ Fonction openLabelUpdateModal trouvée, appel en cours...');
                    openLabelUpdateModal(lineId, socid, productName);
                } else {
                    console.error('❌ Fonction openLabelUpdateModal non trouvée');
                    console.error('🔍 Fonctions window disponibles:', Object.keys(window).filter(k => k.includes('Label') || k.includes('label')));

                    // Réessayer après un court délai (le script n'est peut-être pas encore chargé)
                    console.log('⏳ Tentative de rechargement après délai...');
                    setTimeout(function() {
                        if (typeof openLabelUpdateModal === 'function') {
                            console.log('✅ Fonction trouvée après délai, appel...');
                            openLabelUpdateModal(lineId, socid, productName);
                        } else {
                            console.error('❌ Fonction toujours non trouvée après délai');
                            alert('Erreur : Le module de mise à jour de label n\'est pas chargé.\n\nVeuillez vider le cache de votre navigateur (Ctrl+F5) et réessayer.');
                        }
                    }, 100);
                }
                return false;
            };
        } else {
            // Produit physique : bouton pour le popup de détails dimensions
            detailsButton.title = 'Détails produit';
            detailsButton.innerHTML = '📋';
            detailsButton.style.cssText = 'margin-left: 5px; text-decoration: none; font-size: 11px; padding: 2px 6px; background: #17a2b8; color: white; border-radius: 2px;';
            
            detailsButton.onclick = function(e) {
                e.preventDefault();
                const productName = extractProductName(lineElement);
                const quantity = extractQuantity(lineElement);
                openDetailsModal(lineId, quantity, productName);
                return false;
            };
        }

        targetCell.appendChild(detailsButton);

        // Ajouter l'indicateur de résumé uniquement pour les produits physiques
        if (productType !== 1) {
            loadAndDisplaySummary(lineId, targetCell);
        }
        
        console.log('✅ Bouton ajouté pour ligne ID:', lineId, 'Type:', productType === 1 ? 'Service' : 'Produit');
    } else {
        console.log('❌ Impossible de trouver une cellule cible pour ligne ID:', lineId);
    }
}

/**
 * Extraire le nom du produit depuis la ligne
 */
function extractProductName(lineElement) {
    // Chercher un lien vers la fiche produit
    const productLink = lineElement.querySelector('a[href*="product/card.php"], a[href*="product/index.php"]');
    if (productLink) {
        return productLink.textContent.trim();
    }
    
    // Chercher dans les cellules de description
    const descriptionCells = lineElement.querySelectorAll('.linecoldescription, .linecolproduct, td');
    for (let cell of descriptionCells) {
        const text = cell.textContent.trim();
        if (text && text.length > 3 && !text.match(/^\d+([.,]\d+)?$/)) {
            return text;
        }
    }
    
    return 'Produit';
}

/**
 * Extraire la quantité depuis la ligne
 */
function extractQuantity(lineElement) {
    // Chercher un input de quantité
    const qtyInput = lineElement.querySelector('input[name*="qty"]');
    if (qtyInput) {
        return parseFloat(qtyInput.value) || 1;
    }

    // Chercher dans une cellule de quantité
    const qtyCells = lineElement.querySelectorAll('.linecolqty, td');
    for (let cell of qtyCells) {
        const text = cell.textContent.trim();
        const qtyMatch = text.match(/[\d,\.]+/);
        if (qtyMatch) {
            return parseFloat(qtyMatch[0].replace(',', '.')) || 1;
        }
    }

    return 1;
}

/**
 * Extraire le type de produit depuis la ligne
 * product_type = 0 pour produit physique, 1 pour service
 */
function extractProductType(lineElement) {
    // Méthode 1: Chercher un input hidden avec product_type
    const productTypeInput = lineElement.querySelector('input[name*="product_type"]');
    if (productTypeInput && productTypeInput.value) {
        return parseInt(productTypeInput.value);
    }

    // Méthode 2: Chercher dans les attributs data
    if (lineElement.dataset && lineElement.dataset.productType) {
        return parseInt(lineElement.dataset.productType);
    }

    // Méthode 3: Chercher des indices dans le contenu
    // Les services ont souvent une icône ou un indicateur spécifique
    const hasServiceIcon = lineElement.querySelector('.fa-concierge-bell, .fa-handshake');
    if (hasServiceIcon) {
        return 1;
    }

    // Méthode 4: Regarder si la ligne contient un lien vers un service
    const productLinks = lineElement.querySelectorAll('a[href*="product/card.php"]');
    for (let link of productLinks) {
        if (link.href.includes('type=1')) {
            return 1;
        }
    }

    // Par défaut, considérer comme un produit physique
    return 0;
}

/**
 * Extraire l'ID du tiers (socid) depuis la page
 */
function extractSocid() {
    // Méthode 1: Chercher dans les variables globales
    if (typeof window.socid !== 'undefined' && window.socid) {
        return parseInt(window.socid);
    }

    // Méthode 2: Chercher dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const socidParam = urlParams.get('socid');
    if (socidParam) {
        return parseInt(socidParam);
    }

    // Méthode 3: Chercher dans les liens vers la fiche tiers
    const thirdpartyLinks = document.querySelectorAll('a[href*="societe/card.php"]');
    for (let link of thirdpartyLinks) {
        const match = link.href.match(/[?&]socid=(\d+)/);
        if (match) {
            return parseInt(match[1]);
        }
    }

    // Méthode 4: Chercher dans les inputs cachés
    const socidInput = document.querySelector('input[name="socid"]');
    if (socidInput && socidInput.value) {
        return parseInt(socidInput.value);
    }

    // Méthode 5: Chercher dans les formulaires
    const forms = document.querySelectorAll('form');
    for (let form of forms) {
        const formData = new FormData(form);
        const socidValue = formData.get('socid');
        if (socidValue) {
            return parseInt(socidValue);
        }
    }

    return 0;
}

/**
 * Charger et afficher le résumé des détails
 * DÉSACTIVÉ : Ne plus afficher le résumé après le bouton
 */
function loadAndDisplaySummary(lineId, targetCell) {
    // Fonction désactivée - plus d'affichage de résumé
    return;
    
    if (!ajaxUrl || !detailsToken) {
        if (DEBUG_MODE) {
            console.log('❌ AJAX URL ou token manquant pour résumé ligne', lineId);
        }
        return;
    }
    
    const formData = new URLSearchParams();
    formData.append('action', 'get_details');
    formData.append('commandedet_id', lineId);
    formData.append('token', detailsToken);
    
    fetch(ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.details && data.details.length > 0) {
            const summary = generateSummaryText(data.details);
            
            let summarySpan = targetCell.querySelector('.details-summary');
            if (!summarySpan) {
                summarySpan = document.createElement('span');
                summarySpan.className = 'details-summary details-has-content';
                targetCell.appendChild(summarySpan);
            }
            summarySpan.textContent = summary;
        }
    })
    .catch(error => {
        if (DEBUG_MODE) {
            console.error('❌ Erreur lors du chargement du résumé:', error);
        }
    });
}

/**
 * Générer le texte de résumé depuis les détails
 */
function generateSummaryText(details) {
    if (!details || details.length === 0) {
        return '';
    }

    const totals = { 'm²': 0, 'ml': 0, 'u': 0 };
    let totalPieces = 0;

    details.forEach(detail => {
        totalPieces += parseFloat(detail.pieces) || 0;
        if (detail.unit && totals.hasOwnProperty(detail.unit)) {
            totals[detail.unit] += parseFloat(detail.total_value) || 0;
        }
    });

    const summaryParts = [];
    for (const [unit, total] of Object.entries(totals)) {
        if (total > 0) {
            summaryParts.push(total.toFixed(3) + ' ' + unit);
        }
    }

    return totalPieces + ' pièces (' + summaryParts.join(' + ') + ')';
}

/**
 * Ouvrir le modal de détails
 */
function openDetailsModal(commandedetId, totalQty, productName) {
    if (isLoading) {
        return;
    }

    console.log('🔄 Ouverture modal pour ligne:', commandedetId);

    // Ré-initialiser les variables si nécessaire
    if (!detailsToken || !ajaxUrl) {
        console.log('🔄 Variables manquantes, ré-initialisation...');
        initializeGlobalVariables();
    }
    
    // Vérifier à nouveau
    if (!detailsToken || !ajaxUrl) {
        console.error('❗ Variables critiques manquantes:', {
            detailsToken: detailsToken ? detailsToken.substring(0,10) + '...' : 'MANQUANT',
            ajaxUrl: ajaxUrl || 'MANQUANT',
            currentUrl: window.location.href
        });
        
        // Essayer de récupérer le token une dernière fois
        const lastChanceToken = findTokenInPage();
        if (lastChanceToken) {
            detailsToken = lastChanceToken;
            console.log('✅ Token récupéré en dernière chance:', detailsToken.substring(0,10) + '...');
        } else {
            alert('Erreur de configuration. Variables JavaScript manquantes.\nToken CSRF non trouvé. Veuillez rafraîchir la page.');
            return;
        }
    }

    currentCommandedetId = commandedetId;
    currentTotalQuantity = totalQty || 1;
    currentProductName = productName || 'Produit';
    
    document.getElementById('detailsModalTitle').textContent = 'Détails du produit';
    document.getElementById('detailsProductName').textContent = currentProductName;
    document.getElementById('detailsTotalQuantity').textContent = currentTotalQuantity;
    
    // Charger les détails existants
    loadExistingDetails();
    
    document.getElementById('detailsModal').style.display = 'block';
}

/**
 * Fermer le modal de détails
 */
function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
    clearValidationMessage();
    currentCommandedetId = null;
}

/**
 * Charger les détails existants depuis le serveur
 */
function loadExistingDetails() {
    if (!currentCommandedetId || !ajaxUrl || !detailsToken) {
        console.error('❗ Variables critiques manquantes dans loadExistingDetails:', {
            currentCommandedetId,
            ajaxUrl: ajaxUrl || 'MANQUANT',
            detailsToken: detailsToken ? detailsToken.substring(0,10) + '...' : 'MANQUANT'
        });
        
        // Ajouter une ligne vide par défaut
        const tableBody = document.getElementById('detailsTableBody');
        tableBody.innerHTML = '';
        rowCounter = 0;
        addDetailsRow();
        calculateTotals();
        return;
    }

    isLoading = true;
    showValidationMessage('Chargement des détails...', 'info');

    const formData = new URLSearchParams();
    formData.append('action', 'get_details');
    formData.append('commandedet_id', currentCommandedetId);
    formData.append('token', detailsToken);
    
    console.log('🔄 loadExistingDetails - Requête AJAX:', {
        url: ajaxUrl,
        commandedet_id: currentCommandedetId,
        token: detailsToken.substring(0,10) + '...'
    });
    
    fetch(ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => {
        console.log('📥 loadExistingDetails - Réponse reçue:', {
            status: response.status,
            statusText: response.statusText,
            headers: Object.fromEntries(response.headers.entries())
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('📄 loadExistingDetails - Texte brut reçu:', text.substring(0, 200) + '...');
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('❌ Erreur de parsing JSON:', e);
            console.error('📄 Texte reçu:', text);
            throw new Error('Réponse non-JSON reçue du serveur');
        }
        
        isLoading = false;
        clearValidationMessage();

        const tableBody = document.getElementById('detailsTableBody');
        tableBody.innerHTML = '';
        rowCounter = 0;
        
        if (data.success && data.details && data.details.length > 0) {
            // Charger les détails existants
            data.details.forEach(detail => {
                addDetailsRow(detail);
            });
            console.log('✅ Détails chargés:', data.details.length, 'lignes');
        } else {
            // Ajouter une ligne vide
            addDetailsRow();
            console.log('ℹ️ Aucun détail existant, ligne vide ajoutée');
        }
        
        calculateTotals();
    })
    .catch(error => {
        isLoading = false;
        console.error('❌ Erreur loadExistingDetails:', error);
        showValidationMessage('Erreur lors du chargement des détails: ' + error.message, 'error');
        
        // Ajouter une ligne vide en cas d'erreur
        const tableBody = document.getElementById('detailsTableBody');
        tableBody.innerHTML = '';
        rowCounter = 0;
        addDetailsRow();
        calculateTotals();
    });
}

/**
 * Ajouter une ligne de détail
 */
function addDetailsRow(data = null) {
    const tableBody = document.getElementById('detailsTableBody');
    const row = document.createElement('tr');
    const rowId = ++rowCounter;
    
    row.innerHTML = `
        <td>
            <input type="number" 
                   class="details-cell-input details-cell-number" 
                   id="pieces_${rowId}" 
                   value="${data ? data.pieces : ''}" 
                   min="1" 
                   step="1"
                   placeholder="1"
                   onchange="calculateRowTotal(${rowId})"
                   onkeydown="handleKeyNavigation(event, ${rowId}, 0)">
        </td>
        <td>
            <input type="number" 
                   class="details-cell-input details-cell-number" 
                   id="longueur_${rowId}" 
                   value="${data ? (data.longueur || '') : ''}" 
                   min="0" 
                   step="0.1"
                   placeholder="0"
                   onchange="calculateRowTotal(${rowId})"
                   onkeydown="handleKeyNavigation(event, ${rowId}, 1)">
        </td>
        <td>
            <input type="number" 
                   class="details-cell-input details-cell-number" 
                   id="largeur_${rowId}" 
                   value="${data ? (data.largeur || '') : ''}" 
                   min="0" 
                   step="0.1"
                   placeholder="0"
                   onchange="calculateRowTotal(${rowId})"
                   onkeydown="handleKeyNavigation(event, ${rowId}, 2)">
        </td>
        <td class="details-cell-calculated">
            <span id="total_${rowId}" data-value="0" data-unit="">0 u</span>
        </td>
        <td>
            <input type="text" 
                   class="details-cell-input" 
                   id="description_${rowId}" 
                   value="${data ? (data.description || '') : ''}"
                   placeholder="Description..."
                   onkeydown="handleKeyNavigation(event, ${rowId}, 4)">
        </td>
        <td class="details-row-actions">
            <button class="details-row-delete" onclick="removeDetailsRow(this)" title="Supprimer">✖</button>
        </td>
    `;
    
    tableBody.appendChild(row);
    
    if (data) {
        calculateRowTotal(rowId);
    }
    
    // Focus sur le premier champ de la nouvelle ligne
    setTimeout(() => {
        const firstInput = document.getElementById(`pieces_${rowId}`);
        if (firstInput) {
            firstInput.focus();
        }
    }, 10);
}

/**
 * Supprimer une ligne de détail
 */
function removeDetailsRow(button) {
    const row = button.closest('tr');
    row.remove();
    calculateTotals();
}

/**
 * Calculer le total d'une ligne
 */
function calculateRowTotal(rowId) {
    const pieces = parseFloat(document.getElementById(`pieces_${rowId}`).value) || 0;
    const longueur = parseFloat(document.getElementById(`longueur_${rowId}`).value) || 0;
    const largeur = parseFloat(document.getElementById(`largeur_${rowId}`).value) || 0;
    
    let total = 0;
    let unit = '';
    
    if (longueur > 0 && largeur > 0) {
        // m² = Nb pièces × Longueur/1000 × Largeur/1000
        total = pieces * (longueur / 1000) * (largeur / 1000);
        unit = 'm²';
    } else if (longueur > 0 && largeur === 0) {
        // ml = Nb pièces × Longueur/1000
        total = pieces * (longueur / 1000);
        unit = 'ml';
    } else if (longueur === 0 && largeur > 0) {
        // ml = Nb pièces × Largeur/1000
        total = pieces * (largeur / 1000);
        unit = 'ml';
    } else if (longueur === 0 && largeur === 0 && pieces > 0) {
        // u = Nb pièces
        total = pieces;
        unit = 'u';
    }
    
    const totalElement = document.getElementById(`total_${rowId}`);
    totalElement.textContent = total.toFixed(3) + ' ' + unit;
    totalElement.setAttribute('data-value', total);
    totalElement.setAttribute('data-unit', unit);
    
    calculateTotals();
}

/**
 * Calculer les totaux généraux
 */
function calculateTotals() {
    let totalPieces = 0;
    const totals = { 'm²': 0, 'ml': 0, 'u': 0 };
    const unitCounts = { 'm²': 0, 'ml': 0, 'u': 0 };
    
    const rows = document.querySelectorAll('#detailsTableBody tr');
    rows.forEach(row => {
        const piecesInput = row.querySelector('input[id^="pieces_"]');
        if (piecesInput) {
            const pieces = parseFloat(piecesInput.value) || 0;
            totalPieces += pieces;
            
            const totalCell = row.querySelector('[id^="total_"]');
            if (totalCell) {
                const value = parseFloat(totalCell.getAttribute('data-value')) || 0;
                const unit = totalCell.getAttribute('data-unit') || '';
                
                if (unit && totals.hasOwnProperty(unit) && value > 0) {
                    totals[unit] += value;
                    unitCounts[unit]++;
                }
            }
        }
    });
    
    document.getElementById('detailsTotalPieces').textContent = totalPieces.toLocaleString();
    
    // Déterminer l'unité principale (celle qui a la plus grande valeur totale)
    let mainUnit = 'm²';
    let maxValue = 0;
    for (const [unit, total] of Object.entries(totals)) {
        if (total > maxValue) {
            maxValue = total;
            mainUnit = unit;
        }
    }
    
    // Si aucune valeur, utiliser l'unité avec le plus d'occurrences
    if (maxValue === 0) {
        let maxCount = 0;
        for (const [unit, count] of Object.entries(unitCounts)) {
            if (count > maxCount) {
                maxCount = count;
                mainUnit = unit;
            }
        }
    }
    
    // Afficher le total dans l'unité principale
    const mainTotal = totals[mainUnit];
    document.getElementById('detailsTotalQuantityDisplay').textContent = mainTotal.toFixed(3);
    document.getElementById('detailsTotalUnit').textContent = mainUnit;
    document.getElementById('detailsUnitLabel').textContent = mainUnit;
    
    // Si on a un mélange d'unités, afficher un détail en tooltip
    const usedUnits = Object.entries(totals).filter(([unit, value]) => value > 0);
    if (usedUnits.length > 1) {
        const details = usedUnits.map(([unit, value]) => `${value.toFixed(3)} ${unit}`).join(' + ');
        document.getElementById('detailsTotalQuantityDisplay').title = `Détail: ${details}`;
    } else {
        document.getElementById('detailsTotalQuantityDisplay').title = '';
    }
    
    validateTotals(totals, mainUnit);
}

/**
 * Valider les totaux
 */
function validateTotals(totals, mainUnit) {
    const calculatedTotal = totals[mainUnit];
    
    showValidationMessage(`ℹ️ Total calculé: ${calculatedTotal.toFixed(3)} ${mainUnit}`, 'success');
    
    return true;
}

/**
 * Trier le tableau
 */
function sortDetailsTable(columnIndex) {
    const tableBody = document.getElementById('detailsTableBody');
    const rows = Array.from(tableBody.querySelectorAll('tr'));
    
    // Déterminer la direction du tri
    if (sortColumn === columnIndex) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortDirection = 'asc';
        sortColumn = columnIndex;
    }
    
    // Supprimer les classes de tri précédentes
    document.querySelectorAll('.details-sortable-header').forEach(header => {
        header.classList.remove('details-sort-asc', 'details-sort-desc');
    });
    
    // Ajouter la classe de tri à la colonne courante
    const headers = document.querySelectorAll('.details-sortable-header');
    if (headers[columnIndex]) {
        headers[columnIndex].classList.add(sortDirection === 'asc' ? 'details-sort-asc' : 'details-sort-desc');
    }
    
    // Trier les lignes
    rows.sort((a, b) => {
        let aValue, bValue;
        
        if (columnIndex === 4) { // Description (texte)
            aValue = a.querySelector('input[id^="description_"]').value.toLowerCase();
            bValue = b.querySelector('input[id^="description_"]').value.toLowerCase();
        } else { // Colonnes numériques
            const aInput = a.querySelectorAll('input[type="number"]')[columnIndex];
            const bInput = b.querySelectorAll('input[type="number"]')[columnIndex];
            aValue = parseFloat(aInput ? aInput.value : 0) || 0;
            bValue = parseFloat(bInput ? bInput.value : 0) || 0;
        }
        
        if (aValue < bValue) return sortDirection === 'asc' ? -1 : 1;
        if (aValue > bValue) return sortDirection === 'asc' ? 1 : -1;
        return 0;
    });
    
    // Réorganiser les lignes dans le tableau
    rows.forEach(row => tableBody.appendChild(row));
}

/**
 * Mettre à jour la quantité de la commande automatiquement (sans confirmation)
 * Utilisée après sauvegarde pour synchroniser automatiquement
 */
function updateCommandQuantityAutomatic() {
    return new Promise((resolve, reject) => {
        if (!currentCommandedetId || !ajaxUrl) {
            reject(new Error('Variables manquantes pour mise à jour automatique'));
            return;
        }

        const totals = { 'm²': 0, 'ml': 0, 'u': 0 };
        const unitCounts = { 'm²': 0, 'ml': 0, 'u': 0 };
        
        const rows = document.querySelectorAll('#detailsTableBody tr');
        rows.forEach(row => {
            const totalCell = row.querySelector('[id^="total_"]');
            if (totalCell) {
                const value = parseFloat(totalCell.getAttribute('data-value')) || 0;
                const unit = totalCell.getAttribute('data-unit') || '';
                
                if (unit && totals.hasOwnProperty(unit) && value > 0) {
                    totals[unit] += value;
                    unitCounts[unit]++;
                }
            }
        });
        
        // Déterminer l'unité principale
        let mainUnit = 'm²';
        let maxValue = 0;
        for (const [unit, total] of Object.entries(totals)) {
            if (total > maxValue) {
                maxValue = total;
                mainUnit = unit;
            }
        }
        
        if (maxValue === 0) {
            let maxCount = 0;
            for (const [unit, count] of Object.entries(unitCounts)) {
                if (count > maxCount) {
                    maxCount = count;
                    mainUnit = unit;
                }
            }
        }
        
        const newQuantity = totals[mainUnit];
        
        if (newQuantity === 0) {
            reject(new Error('Aucune quantité calculée'));
            return;
        }
        
        console.log(`🔄 Mise à jour automatique: ${newQuantity.toFixed(3)} ${mainUnit}`);
        
        const formData = new FormData();
        formData.append('action', 'update_command_quantity');
        formData.append('commandedet_id', currentCommandedetId);
        formData.append('new_quantity', newQuantity.toFixed(3));
        formData.append('unit', mainUnit);
        formData.append('token', detailsToken);
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Quantité mise à jour automatiquement:', newQuantity.toFixed(3), mainUnit);
                currentTotalQuantity = newQuantity;
                resolve({
                    newQuantity: newQuantity,
                    unit: mainUnit
                });
            } else {
                reject(new Error('Erreur serveur: ' + (data.error || 'Erreur inconnue')));
            }
        })
        .catch(error => {
            console.error('❌ Erreur communication:', error);
            reject(error);
        });
    });
}

/**
 * Mettre à jour la quantité de la commande (avec confirmation utilisateur)
 * Version originale avec demande de confirmation
 */
function updateCommandQuantity() {
    if (!currentCommandedetId || !ajaxUrl) {
        return;
    }

    const totals = { 'm²': 0, 'ml': 0, 'u': 0 };
    const unitCounts = { 'm²': 0, 'ml': 0, 'u': 0 };
    
    const rows = document.querySelectorAll('#detailsTableBody tr');
    rows.forEach(row => {
        const totalCell = row.querySelector('[id^="total_"]');
        if (totalCell) {
            const value = parseFloat(totalCell.getAttribute('data-value')) || 0;
            const unit = totalCell.getAttribute('data-unit') || '';
            
            if (unit && totals.hasOwnProperty(unit) && value > 0) {
                totals[unit] += value;
                unitCounts[unit]++;
            }
        }
    });
    
    // Déterminer l'unité principale
    let mainUnit = 'm²';
    let maxValue = 0;
    for (const [unit, total] of Object.entries(totals)) {
        if (total > maxValue) {
            maxValue = total;
            mainUnit = unit;
        }
    }
    
    if (maxValue === 0) {
        let maxCount = 0;
        for (const [unit, count] of Object.entries(unitCounts)) {
            if (count > maxCount) {
                maxCount = count;
                mainUnit = unit;
            }
        }
    }
    
    const newQuantity = totals[mainUnit];
    
    if (newQuantity === 0) {
        showValidationMessage('Aucune quantité calculée. Veuillez saisir au moins une ligne de détail.', 'error');
        return;
    }
    
    const message = `Voulez-vous mettre à jour la quantité de la ligne de commande ?\n\n` +
                   `Quantité actuelle: ${currentTotalQuantity}\n` +
                   `Nouvelle quantité: ${newQuantity.toFixed(3)} ${mainUnit}`;
    
    if (confirm(message)) {
        const formData = new FormData();
        formData.append('action', 'update_command_quantity');
        formData.append('commandedet_id', currentCommandedetId);
        formData.append('new_quantity', newQuantity.toFixed(3));
        formData.append('unit', mainUnit);
        formData.append('token', detailsToken);
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showValidationMessage('Quantité mise à jour avec succès !', 'success');
                currentTotalQuantity = newQuantity;
                document.getElementById('detailsTotalQuantity').textContent = newQuantity.toFixed(3);
                validateTotals(totals, mainUnit);
            } else {
                showValidationMessage('Erreur lors de la mise à jour: ' + (data.error || 'Erreur inconnue'), 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showValidationMessage('Erreur de communication avec le serveur', 'error');
        });
    }
}

/**
 * Vider tous les détails
 */
function clearAllDetails() {
    if (confirm('Êtes-vous sûr de vouloir vider toutes les lignes ?')) {
        document.getElementById('detailsTableBody').innerHTML = '';
        rowCounter = 0;
        addDetailsRow(); // Ajouter une ligne vide
        calculateTotals();
    }
}

/**
 * Navigation au clavier
 */
function handleKeyNavigation(event, rowId, colIndex) {
    if (event.key === 'Tab') {
        event.preventDefault();
        
        // Navigation horizontale (Tab)
        let nextRowId = rowId;
        let nextColIndex = colIndex + 1;
        
        // Si on est à la fin de la ligne, passer à la ligne suivante
        if (nextColIndex > 4) {
            nextRowId = rowId + 1;
            nextColIndex = 0;
            
            // Si on est à la dernière ligne, ajouter une nouvelle ligne
            if (!document.getElementById(`pieces_${nextRowId}`)) {
                addDetailsRow();
                nextRowId = rowCounter;
            }
        }
        
        // Focus sur la cellule suivante
        const nextInput = getInputByPosition(nextRowId, nextColIndex);
        if (nextInput) {
            nextInput.focus();
            nextInput.select();
        }
    } else if (event.key === 'Enter') {
        event.preventDefault();
        
        // Navigation verticale (Enter)
        let nextRowId = rowId + 1;
        let nextColIndex = colIndex;
        
        // Si on est à la dernière ligne, ajouter une nouvelle ligne
        if (!document.getElementById(`pieces_${nextRowId}`)) {
            addDetailsRow();
            nextRowId = rowCounter;
        }
        
        // Focus sur la cellule de la ligne suivante, même colonne
        const nextInput = getInputByPosition(nextRowId, nextColIndex);
        if (nextInput) {
            nextInput.focus();
            nextInput.select();
        }
    }
}

/**
 * Obtenir l'input à une position donnée
 */
function getInputByPosition(rowId, colIndex) {
    const inputIds = [
        `pieces_${rowId}`,
        `longueur_${rowId}`,
        `largeur_${rowId}`,
        '', // Colonne calculée, pas d'input
        `description_${rowId}`
    ];
    
    if (colIndex === 3) colIndex = 4; // Skip calculated column
    
    return document.getElementById(inputIds[colIndex]);
}

/**
 * Sauvegarder les détails avec régénération automatique du PDF
 * Version FormData native (recommandée pour Dolibarr)
 */
function saveDetails() {
    // Vérifier si on est sur une page avec PDF pour adapter le comportement
    const hasPDFGeneration = isPDFGenerationPage();
    if (!currentCommandedetId || !ajaxUrl) {
        showValidationMessage('Erreur: ID de ligne manquant ou URL AJAX non définie', 'error');
        return;
    }

    const rows = document.querySelectorAll('#detailsTableBody tr');
    const details = [];
    
    console.log('🔍 Collecte des données depuis', rows.length, 'lignes');
    
    // Collecter et valider les données
    rows.forEach((row, index) => {
        const piecesInput = row.querySelector('input[id^="pieces_"]');
        const longueurInput = row.querySelector('input[id^="longueur_"]');
        const largeurInput = row.querySelector('input[id^="largeur_"]');
        const descriptionInput = row.querySelector('input[id^="description_"]');
        
        if (piecesInput) {
            const pieces = parseFloat(piecesInput.value) || 0;
            const longueur = parseFloat(longueurInput.value) || 0;
            const largeur = parseFloat(largeurInput.value) || 0;
            let description = descriptionInput ? descriptionInput.value : '';
            
            // Nettoyer la description
            description = String(description)
                .replace(/[\r\n\t]/g, ' ')
                .replace(/"/g, "'")
                .trim()
                .substring(0, 255);
            
            if (pieces > 0) {
                const totalCell = row.querySelector('[id^="total_"]');
                let totalValue = 0;
                let unit = 'u';
                
                if (totalCell) {
                    totalValue = parseFloat(totalCell.getAttribute('data-value')) || 0;
                    unit = totalCell.getAttribute('data-unit') || 'u';
                }
                
                details.push({
                    pieces: Number(pieces),
                    longueur: (longueur > 0) ? Number(longueur) : null,
                    largeur: (largeur > 0) ? Number(largeur) : null,
                    total_value: Number(totalValue),
                    unit: String(unit),
                    description: String(description)
                });
                
                console.log(`📋 Ligne ${index + 1}:`, details[details.length - 1]);
            }
        }
    });
    
    if (details.length === 0) {
        showValidationMessage('Veuillez saisir au moins une ligne de détail.', 'error');
        return;
    }

    console.log('📤 Données validées:', details.length, 'lignes');

    isLoading = true;
    showValidationMessage('Sauvegarde en cours...', 'info');

    // Créer FormData avec champs natifs (méthode Dolibarr standard)
    const formData = new FormData();
    formData.append('action', 'save_details');
    formData.append('commandedet_id', String(currentCommandedetId));
    formData.append('token', detailsToken);
    formData.append('nb_details', String(details.length));
    
    // Ajouter chaque détail comme champs séparés
    details.forEach((detail, index) => {
        formData.append(`detail[${index}][pieces]`, String(detail.pieces));
        formData.append(`detail[${index}][longueur]`, detail.longueur ? String(detail.longueur) : '');
        formData.append(`detail[${index}][largeur]`, detail.largeur ? String(detail.largeur) : '');
        formData.append(`detail[${index}][total_value]`, String(detail.total_value));
        formData.append(`detail[${index}][unit]`, detail.unit);
        formData.append(`detail[${index}][description]`, detail.description);
    });
    
    // Debug FormData (uniquement les clés pour ne pas surcharger)
    console.log('💾 Envoi FormData natif:', {
        commandedet_id: currentCommandedetId,
        nb_details: details.length,
        token: detailsToken.substring(0,10) + '...',
        formdata_keys: Array.from(formData.keys())
    });
    
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
        // Pas de Content-Type - le navigateur le définit automatiquement pour FormData
    })
    .then(response => {
        console.log('📥 Réponse reçue:', {
            status: response.status,
            statusText: response.statusText,
            contentType: response.headers.get('content-type')
        });
        
        return response.text().then(text => {
            console.log('📄 Réponse brute (premiers 500 chars):', text.substring(0, 500));
            
            if (!response.ok) {
                try {
                    const errorData = JSON.parse(text);
                    console.error('❌ Erreur serveur:', errorData);
                    
                    let errorMessage = `HTTP ${response.status}: ${errorData.error || response.statusText}`;
                    if (errorData.debug) {
                        console.error('🔍 Debug serveur:', errorData.debug);
                        errorMessage += '\n\nDétails techniques en console.';
                    }
                    
                    throw new Error(errorMessage);
                } catch (parseError) {
                    console.error('❌ Réponse d\'erreur non-JSON:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}\n\nRéponse: ${text.substring(0, 200)}`);
                }
            }
            
            return text;
        });
    })
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('❌ Erreur parsing réponse succès:', parseError);
            console.error('📄 Texte complet:', text);
            throw new Error('Réponse serveur non-JSON: ' + text.substring(0, 100));
        }
        
        isLoading = false;
        
        if (data.success) {
            showValidationMessage(`Détails sauvegardés avec succès ! (${data.nb_details || details.length} lignes)`, 'success');
            
            console.log('✅ Sauvegarde réussie:', data);
            
            // Étape 2: Mettre à jour automatiquement la quantité de la ligne de commande
            console.log('🔄 Mise à jour automatique de la quantité...');
            updateCommandQuantityAutomatic()
                .then(() => {
                    console.log('✅ Quantité mise à jour automatiquement');
                    
                    // Étape 3: Fermer le modal
                    closeDetailsModal();
                    
                    // Étape 4: Déclencher la régénération PDF ou actualisation
                    if (hasPDFGeneration) {
                        console.log('🔄 Tentative de régénération PDF...');
                        showValidationMessage('Régénération du PDF en cours...', 'info');
                        
                        // Attendre un peu puis déclencher la régénération PDF
                        setTimeout(() => {
                            const pdfRegenerated = triggerPDFRegeneration();
                            
                            if (!pdfRegenerated) {
                                // Si la régénération PDF a échoué, actualiser normalement
                                console.log('🔄 Fallback: actualisation normale');
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            }
                            // Si la régénération PDF réussit, la page sera automatiquement actualisée
                            
                        }, 800); // Délai pour que l'utilisateur voit le message
                    } else {
                        // Page sans PDF : actualisation normale
                        showValidationMessage('Sauvegarde terminée ! Actualisation de la page...', 'success');
                        setTimeout(() => {
                            console.log('🔄 Actualisation de la page...');
                            window.location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('❌ Erreur mise à jour quantité:', error);
                    
                    // Continuer quand même avec la régénération PDF ou actualisation
                    closeDetailsModal();
                    
                    if (hasPDFGeneration) {
                        setTimeout(() => {
                            const pdfRegenerated = triggerPDFRegeneration();
                            if (!pdfRegenerated) {
                                setTimeout(() => window.location.reload(), 1000);
                            }
                        }, 500);
                    } else {
                        showValidationMessage('Détails sauvegardés ! Actualisation de la page...', 'info');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                });
        } else {
            showValidationMessage('Erreur lors de la sauvegarde: ' + (data.error || 'Erreur inconnue'), 'error');
            console.error('❌ Erreur sauvegarde:', data);
            
            if (data.debug) {
                console.error('🔍 Debug serveur:', data.debug);
            }
        }
    })
    .catch(error => {
        isLoading = false;
        console.error('❌ Erreur complète:', error);
        showValidationMessage('Erreur de communication: ' + error.message, 'error');
    });
}

/**
 * Afficher un message de validation
 */
function showValidationMessage(message, type) {
    const messageDiv = document.getElementById('detailsValidationMessage');
    if (messageDiv) {
        messageDiv.className = 'details-validation-message details-validation-' + type;
        messageDiv.textContent = message;
        messageDiv.style.display = 'block';
    }
}

/**
 * Effacer le message de validation
 */
function clearValidationMessage() {
    const messageDiv = document.getElementById('detailsValidationMessage');
    if (messageDiv) {
        messageDiv.style.display = 'none';
        messageDiv.textContent = '';
    }
}

/**
 * Exporter en CSV
 */
function exportToCSV() {
    if (!currentCommandedetId || !ajaxUrl) {
        return;
    }

    const url = ajaxUrl + '?action=export_details_csv&commandedet_id=' + encodeURIComponent(currentCommandedetId) + '&token=' + encodeURIComponent(detailsToken);
    
    // Télécharger le fichier
    const a = document.createElement('a');
    a.href = url;
    a.download = 'details_commande_' + currentCommandedetId + '_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// Exposer les fonctions nécessaires globalement
window.openDetailsModal = openDetailsModal;
window.closeDetailsModal = closeDetailsModal;
window.addDetailsRow = addDetailsRow;
window.removeDetailsRow = removeDetailsRow;
window.calculateRowTotal = calculateRowTotal;
window.sortDetailsTable = sortDetailsTable;
window.updateCommandQuantity = updateCommandQuantity;
window.updateCommandQuantityAutomatic = updateCommandQuantityAutomatic;
window.clearAllDetails = clearAllDetails;
window.handleKeyNavigation = handleKeyNavigation;
window.saveDetails = saveDetails;
window.exportToCSV = exportToCSV;

// Exposer les nouvelles fonctions PDF
window.triggerPDFRegeneration = triggerPDFRegeneration;
window.isPDFGenerationPage = isPDFGenerationPage;
window.testPDFButtonDetection = testPDFButtonDetection;