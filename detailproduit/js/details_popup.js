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
 * Initialisation du module
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Initialisation du module detailproduit...');
    
    // Initialiser les variables depuis les globales PHP
    initializeGlobalVariables();
    
    // Créer le modal s'il n'existe pas
    createDetailsModal();
    
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
        const detailsButton = document.createElement('a');
        detailsButton.href = '#';
        detailsButton.className = 'details-btn-open';
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

        targetCell.appendChild(detailsButton);

        // Ajouter l'indicateur de résumé
        loadAndDisplaySummary(lineId, targetCell);
        
        console.log('✅ Bouton ajouté pour ligne ID:', lineId);
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
 * Charger et afficher le résumé des détails
 */
function loadAndDisplaySummary(lineId, targetCell) {
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
 * Mettre à jour la quantité de la commande
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
 * Sauvegarder les détails - Version corrigée avec validation JSON
 */
function saveDetails() {
    if (!currentCommandedetId || !ajaxUrl) {
        showValidationMessage('Erreur: ID de ligne manquant ou URL AJAX non définie', 'error');
        return;
    }

    const rows = document.querySelectorAll('#detailsTableBody tr');
    const details = [];
    
    console.log('🔍 Collecte des données depuis', rows.length, 'lignes');
    
    rows.forEach((row, index) => {
        const inputs = row.querySelectorAll('input');
        if (inputs.length >= 4) {
            const pieces = parseFloat(inputs[0].value) || 0;
            const longueur = parseFloat(inputs[1].value) || 0;
            const largeur = parseFloat(inputs[2].value) || 0;
            let description = inputs[3].value || '';
            
            // Nettoyer la description pour éviter les problèmes JSON
            description = description
                .replace(/[\r\n\t]/g, ' ')  // Remplacer les sauts de ligne par des espaces
                .replace(/"/g, "'")         // Remplacer les guillemets doubles par simples
                .replace(/\\/g, '/')        // Remplacer les backslashes
                .trim();                    // Supprimer les espaces en début/fin
            
            if (pieces > 0) {
                const totalCell = row.querySelector('[id^="total_"]');
                let totalValue = 0;
                let unit = 'u';
                
                if (totalCell) {
                    totalValue = parseFloat(totalCell.getAttribute('data-value')) || 0;
                    unit = totalCell.getAttribute('data-unit') || 'u';
                }
                
                // S'assurer que toutes les valeurs sont valides
                const detail = {
                    pieces: isNaN(pieces) ? 0 : Number(pieces),
                    longueur: (longueur > 0 && !isNaN(longueur)) ? Number(longueur) : null,
                    largeur: (largeur > 0 && !isNaN(largeur)) ? Number(largeur) : null,
                    total_value: isNaN(totalValue) ? 0 : Number(totalValue),
                    unit: unit || 'u',
                    description: description.substring(0, 255) // Limiter la longueur
                };
                
                details.push(detail);
                
                console.log(`📋 Ligne ${index + 1}:`, detail);
            }
        }
    });
    
    if (details.length === 0) {
        showValidationMessage('Veuillez saisir au moins une ligne de détail.', 'error');
        return;
    }

    console.log('📤 Données à sauvegarder:', details);

    // Nettoyer et valider chaque détail avant sérialisation
    const cleanDetails = details.map((detail, index) => {
        // S'assurer que toutes les propriétés sont bien définies
        const cleanDetail = {
            pieces: Number(detail.pieces) || 0,
            longueur: (detail.longueur && detail.longueur > 0) ? Number(detail.longueur) : null,
            largeur: (detail.largeur && detail.largeur > 0) ? Number(detail.largeur) : null,
            total_value: Number(detail.total_value) || 0,
            unit: String(detail.unit || 'u'),
            description: String(detail.description || '')
        };
        
        console.log(`🧹 Détail ${index + 1} nettoyé:`, cleanDetail);
        return cleanDetail;
    });

    // Tester la sérialisation JSON avant l'envoi
    let jsonString;
    try {
        jsonString = JSON.stringify(cleanDetails);
        
        // Vérifier que le JSON est valide en le reparsant
        const testParse = JSON.parse(jsonString);
        console.log('✅ JSON sérialisé et vérifié avec succès:', jsonString.length, 'caractères');
        console.log('📄 JSON preview:', jsonString.substring(0, 300) + '...');
        
        // Vérifier la structure
        if (!Array.isArray(testParse)) {
            throw new Error('Le JSON doit être un tableau');
        }
        
    } catch (jsonError) {
        console.error('❌ Erreur lors de la sérialisation JSON:', jsonError);
        console.error('🔍 Données problématiques:', cleanDetails);
        showValidationMessage('Erreur: Impossible de sérialiser les données. ' + jsonError.message, 'error');
        return;
    }

    isLoading = true;
    showValidationMessage('Sauvegarde en cours...', 'info');

    const formData = new FormData();
    formData.append('action', 'save_details');
    formData.append('commandedet_id', currentCommandedetId.toString());
    formData.append('details_json', jsonString);
    formData.append('token', detailsToken);
    
    console.log('💾 Envoi des données:', {
        commandedet_id: currentCommandedetId,
        nb_details: details.length,
        token: detailsToken.substring(0,10) + '...',
        json_length: jsonString.length
    });
    
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📥 Réponse reçue:', {
            status: response.status,
            statusText: response.statusText,
            headers: Object.fromEntries(response.headers.entries())
        });
        
        // Toujours récupérer le texte de la réponse, même en cas d'erreur
        return response.text().then(text => {
            console.log('📄 Texte de réponse brut:', text);
            
            if (!response.ok) {
                // Essayer de parser le JSON d'erreur
                try {
                    const errorData = JSON.parse(text);
                    console.error('❌ Erreur serveur détaillée:', errorData);
                    
                    let errorMessage = `HTTP ${response.status}: ${errorData.error || response.statusText}`;
                    if (errorData.debug) {
                        console.error('🔍 Debug serveur:', errorData.debug);
                        errorMessage += '\n\nDétails techniques en console.';
                    }
                    
                    throw new Error(errorMessage);
                } catch (parseError) {
                    console.error('❌ Impossible de parser la réponse d\'erreur:', parseError);
                    console.error('📄 Réponse brute:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}\n\nRéponse: ${text.substring(0, 200)}`);
                }
            }
            
            return text;
        });
    })
    .then(text => {
        console.log('📄 Texte de réponse:', text.substring(0, 300) + '...');
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('❌ Erreur parsing réponse:', parseError);
            console.error('📄 Texte complet:', text);
            throw new Error('Réponse serveur non-JSON: ' + text.substring(0, 100));
        }
        
        isLoading = false;
        
        if (data.success) {
            showValidationMessage(`Détails sauvegardés avec succès ! (${data.nb_details} lignes)`, 'success');
            
            // Mettre à jour l'affichage du résumé sur la ligne de commande
            if (data.summary) {
                updateLineDisplaySummary(currentCommandedetId, data.summary);
            }
            
            console.log('✅ Sauvegarde réussie');
            
            setTimeout(() => {
                closeDetailsModal();
            }, 1500);
        } else {
            showValidationMessage('Erreur lors de la sauvegarde: ' + (data.error || 'Erreur inconnue'), 'error');
            console.error('❌ Erreur sauvegarde:', data);
            
            // Afficher les détails de l'erreur en debug
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
 * Mettre à jour l'affichage du résumé sur la ligne de commande
 */
function updateLineDisplaySummary(lineId, summary) {
    const lineElement = document.getElementById('row-' + lineId);
    if (lineElement) {
        let summarySpan = lineElement.querySelector('.details-summary');
        if (summarySpan) {
            summarySpan.textContent = summary;
            summarySpan.classList.add('details-has-content');
        }
    }
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
window.clearAllDetails = clearAllDetails;
window.handleKeyNavigation = handleKeyNavigation;
window.saveDetails = saveDetails;
window.exportToCSV = exportToCSV;