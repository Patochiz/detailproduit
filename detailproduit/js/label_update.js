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
 * \file    js/label_update.js
 * \ingroup detailproduit
 * \brief   JavaScript for label update modal (product_type = 9)
 */

// Variables globales pour le modal de label
let currentLabelCommandedetId = null;
let currentLabelSocid = null;
let currentLabelProductLabel = '';
let labelAjaxUrl = '';
let isLabelLoading = false;

console.log('📦 label_update.js chargé');

// === EXPOSITION DES FONCTIONS GLOBALEMENT DÈS LE CHARGEMENT ===
// Ceci est CRITIQUE pour que le onclick dans le HTML fonctionne

/**
 * Ouvrir le modal de mise à jour de label
 * EXPOSÉE IMMÉDIATEMENT pour être disponible dans les onclick
 */
window.openLabelUpdateModal = function(commandedetId, socid, productLabel) {
    console.log('🔄 openLabelUpdateModal appelée avec:', {
        commandedetId: commandedetId,
        socid: socid,
        productLabel: productLabel
    });
    
    if (isLabelLoading) {
        console.log('⚠️ Chargement en cours, opération annulée');
        return;
    }

    // Vérifier que le modal existe
    let modal = document.getElementById('labelUpdateModal');
    if (!modal) {
        console.error('❌ Modal labelUpdateModal non trouvé, création...');
        createLabelUpdateModal();
        modal = document.getElementById('labelUpdateModal');
        
        if (!modal) {
            alert('Erreur: Le modal de mise à jour de label n\'a pas pu être créé.');
            return;
        }
    }

    currentLabelCommandedetId = commandedetId;
    currentLabelSocid = socid;
    currentLabelProductLabel = productLabel || 'Service';
    
    // Réinitialiser le formulaire
    document.getElementById('labelNCommande').value = '';
    document.getElementById('labelDateCommande').value = '';
    document.getElementById('labelContact').value = '';
    document.getElementById('labelRefCommande').value = '';
    
    // Charger les données existantes
    loadLabelDataInternal();
    
    // Charger la liste des contacts
    loadThirdpartyContactsInternal();
    
    console.log('✅ Affichage du modal');
    modal.style.display = 'block';
};

/**
 * Fermer le modal de mise à jour de label
 */
window.closeLabelUpdateModal = function() {
    console.log('🔄 Fermeture du modal de label');
    const modal = document.getElementById('labelUpdateModal');
    if (modal) {
        modal.style.display = 'none';
    }
    clearLabelValidationMessage();
    currentLabelCommandedetId = null;
    currentLabelSocid = null;
};

/**
 * Sauvegarder la mise à jour du label
 */
window.saveLabelUpdate = function() {
    console.log('💾 Appel saveLabelUpdate...');
    saveLabelUpdateInternal();
};

console.log('✅ Fonctions label exposées globalement:', {
    openLabelUpdateModal: typeof window.openLabelUpdateModal,
    closeLabelUpdateModal: typeof window.closeLabelUpdateModal,
    saveLabelUpdate: typeof window.saveLabelUpdate
});

/**
 * Initialisation du modal de mise à jour de label au chargement
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 DOMContentLoaded - Initialisation du module de mise à jour de label...');
    
    // Créer le modal de mise à jour de label
    createLabelUpdateModal();
    
    // URL AJAX pour les labels
    const baseUrl = findBaseUrl();
    labelAjaxUrl = baseUrl + '/custom/detailproduit/ajax/label_handler.php';
    
    console.log('✅ Module label initialisé:', {
        labelAjaxUrl: labelAjaxUrl,
        modalExists: document.getElementById('labelUpdateModal') ? 'OUI' : 'NON'
    });
});

/**
 * Créer le modal de mise à jour de label dans le DOM
 */
function createLabelUpdateModal() {
    if (document.getElementById('labelUpdateModal')) {
        console.log('ℹ️ Modal labelUpdateModal déjà existant');
        return; // Modal déjà créé
    }

    console.log('🏗️ Création du modal labelUpdateModal...');

    const modalHTML = `
        <div id="labelUpdateModal" class="details-modal">
            <div class="details-modal-content" style="max-width: 600px;">
                <div class="details-modal-header">
                    <h3>Modifier le label du service</h3>
                    <button class="details-modal-close" onclick="closeLabelUpdateModal()">&times;</button>
                </div>
                
                <div class="details-modal-body">
                    <div class="label-form">
                        <div class="label-form-group">
                            <label for="labelNCommande">N° de commande</label>
                            <input type="text" 
                                   id="labelNCommande" 
                                   class="label-form-input" 
                                   placeholder="Saisir le numéro de commande">
                        </div>
                        
                        <div class="label-form-group">
                            <label for="labelDateCommande">Date de commande</label>
                            <input type="date" 
                                   id="labelDateCommande" 
                                   class="label-form-input">
                        </div>
                        
                        <div class="label-form-group">
                            <label for="labelContact">De</label>
                            <select id="labelContact" class="label-form-input">
                                <option value="">-- Sélectionner un contact --</option>
                            </select>
                        </div>
                        
                        <div class="label-form-group">
                            <label for="labelRefCommande">Référence</label>
                            <input type="text" 
                                   id="labelRefCommande" 
                                   class="label-form-input" 
                                   placeholder="Saisir la référence">
                        </div>
                        
                        <div class="label-preview" id="labelPreview" style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 4px; font-style: italic; color: #666;">
                            <strong>Aperçu du label :</strong><br>
                            <span id="labelPreviewText">Le label sera généré automatiquement</span>
                        </div>
                    </div>

                    <div id="labelValidationMessage" class="details-validation-message"></div>
                </div>

                <div class="details-modal-footer">
                    <button class="details-btn" onclick="closeLabelUpdateModal()">Annuler</button>
                    <button class="details-btn details-btn-success" onclick="saveLabelUpdate()">💾 Valider</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
    console.log('✅ Modal labelUpdateModal créé');

    // Fermer le modal en cliquant à l'extérieur
    const modal = document.getElementById('labelUpdateModal');
    modal.onclick = function(event) {
        if (event.target === modal) {
            closeLabelUpdateModal();
        }
    };
    
    // Ajouter les écouteurs pour la mise à jour en temps réel de l'aperçu
    ['labelNCommande', 'labelDateCommande', 'labelContact', 'labelRefCommande'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updateLabelPreviewInternal);
            field.addEventListener('change', updateLabelPreviewInternal);
        }
    });
}

/**
 * Charger les données de label existantes
 */
function loadLabelDataInternal() {
    if (!currentLabelCommandedetId || !labelAjaxUrl) {
        console.error('❗ Variables critiques manquantes pour loadLabelData');
        return;
    }

    isLabelLoading = true;
    showLabelValidationMessage('Chargement des données...', 'info');

    const formData = new URLSearchParams();
    formData.append('action', 'get_label_data');
    formData.append('commandedet_id', currentLabelCommandedetId);
    formData.append('token', detailsToken);
    
    console.log('🔄 loadLabelData - Requête AJAX:', {
        url: labelAjaxUrl,
        commandedet_id: currentLabelCommandedetId
    });
    
    fetch(labelAjaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => {
        console.log('📥 loadLabelData - Réponse:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    })
    .then(data => {
        isLabelLoading = false;
        clearLabelValidationMessage();

        if (data.success && data.data) {
            console.log('✅ Données chargées:', data.data);
            
            document.getElementById('labelNCommande').value = data.data.n_commande || '';
            document.getElementById('labelDateCommande').value = data.data.date_commande || '';
            document.getElementById('labelContact').value = data.data.contact || '';
            document.getElementById('labelRefCommande').value = data.data.ref_commande || '';
            
            updateLabelPreviewInternal();
        } else {
            console.log('ℹ️ Aucune donnée existante');
        }
    })
    .catch(error => {
        isLabelLoading = false;
        console.error('❌ Erreur loadLabelData:', error);
        showLabelValidationMessage('Erreur lors du chargement: ' + error.message, 'error');
    });
}

/**
 * Charger la liste des contacts du tiers (hors ADR)
 */
function loadThirdpartyContactsInternal() {
    if (!currentLabelSocid || !labelAjaxUrl) {
        console.error('❗ Variables critiques manquantes pour loadThirdpartyContacts');
        return;
    }

    const formData = new URLSearchParams();
    formData.append('action', 'get_thirdparty_contacts');
    formData.append('socid', currentLabelSocid);
    formData.append('token', detailsToken);
    
    console.log('🔄 loadThirdpartyContacts - Requête:', {
        url: labelAjaxUrl,
        socid: currentLabelSocid
    });
    
    fetch(labelAjaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => {
        console.log('📥 loadThirdpartyContacts - Réponse:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success && data.contacts) {
            console.log('✅ Contacts chargés:', data.contacts.length);
            
            const selectContact = document.getElementById('labelContact');
            
            const firstOption = selectContact.options[0];
            selectContact.innerHTML = '';
            selectContact.appendChild(firstOption);
            
            data.contacts.forEach(contact => {
                const option = document.createElement('option');
                option.value = contact.id;
                option.textContent = contact.name;
                selectContact.appendChild(option);
            });
        } else {
            console.log('ℹ️ Aucun contact trouvé');
            showLabelValidationMessage('Aucun contact disponible pour ce tiers', 'warning');
        }
    })
    .catch(error => {
        console.error('❌ Erreur loadThirdpartyContacts:', error);
        showLabelValidationMessage('Erreur lors du chargement des contacts: ' + error.message, 'error');
    });
}

/**
 * Mettre à jour l'aperçu du label en temps réel
 */
function updateLabelPreviewInternal() {
    const nCommande = document.getElementById('labelNCommande').value.trim();
    const dateCommande = document.getElementById('labelDateCommande').value;
    const contactId = document.getElementById('labelContact').value;
    const refCommande = document.getElementById('labelRefCommande').value.trim();
    
    let contactName = '';
    if (contactId) {
        const selectContact = document.getElementById('labelContact');
        const selectedOption = selectContact.options[selectContact.selectedIndex];
        contactName = selectedOption ? selectedOption.textContent : '';
    }
    
    const labelParts = [];
    
    if (nCommande) {
        labelParts.push("Commande n° " + nCommande);
    }
    
    if (dateCommande) {
        const dateParts = dateCommande.split('-');
        if (dateParts.length === 3) {
            const dateFormatted = dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0];
            labelParts.push("du " + dateFormatted);
        } else {
            labelParts.push("du " + dateCommande);
        }
    }
    
    if (contactName) {
        labelParts.push("de " + contactName);
    }
    
    if (refCommande) {
        labelParts.push("Réf. : " + refCommande);
    }
    
    const previewText = labelParts.length > 0 
        ? labelParts.join(' ') 
        : 'Le label sera généré automatiquement';
    
    document.getElementById('labelPreviewText').textContent = previewText;
}

/**
 * Sauvegarder la mise à jour du label
 */
function saveLabelUpdateInternal() {
    if (!currentLabelCommandedetId || !labelAjaxUrl) {
        showLabelValidationMessage('Erreur: ID de ligne manquant ou URL AJAX non définie', 'error');
        return;
    }

    const nCommande = document.getElementById('labelNCommande').value.trim();
    const dateCommande = document.getElementById('labelDateCommande').value;
    const contactId = document.getElementById('labelContact').value;
    const refCommande = document.getElementById('labelRefCommande').value.trim();
    
    if (!nCommande && !dateCommande && !contactId && !refCommande) {
        showLabelValidationMessage('Veuillez remplir au moins un champ', 'error');
        return;
    }
    
    console.log('📤 Sauvegarde label:', {
        commandedet_id: currentLabelCommandedetId,
        n_commande: nCommande,
        date_commande: dateCommande,
        contact: contactId,
        ref_commande: refCommande
    });

    isLabelLoading = true;
    showLabelValidationMessage('Sauvegarde en cours...', 'info');

    const formData = new FormData();
    formData.append('action', 'save_label_update');
    formData.append('commandedet_id', currentLabelCommandedetId);
    formData.append('n_commande', nCommande);
    formData.append('date_commande', dateCommande);
    formData.append('contact', contactId);
    formData.append('ref_commande', refCommande);
    formData.append('token', detailsToken);
    
    fetch(labelAjaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📥 Réponse:', response.status);
        
        return response.text().then(text => {
            if (!response.ok) {
                try {
                    const errorData = JSON.parse(text);
                    throw new Error(errorData.error || response.statusText);
                } catch (parseError) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
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
            console.error('❌ Erreur parsing:', parseError);
            throw new Error('Réponse serveur non-JSON');
        }
        
        isLabelLoading = false;
        
        if (data.success) {
            showLabelValidationMessage('Label mis à jour avec succès !', 'success');
            console.log('✅ Sauvegarde réussie:', data.new_label);
            
            setTimeout(() => {
                closeLabelUpdateModal();
                window.location.reload();
            }, 1500);
        } else {
            showLabelValidationMessage('Erreur: ' + (data.error || 'Erreur inconnue'), 'error');
        }
    })
    .catch(error => {
        isLabelLoading = false;
        console.error('❌ Erreur:', error);
        showLabelValidationMessage('Erreur de communication: ' + error.message, 'error');
    });
}

/**
 * Afficher un message de validation
 */
function showLabelValidationMessage(message, type) {
    const messageDiv = document.getElementById('labelValidationMessage');
    if (messageDiv) {
        messageDiv.className = 'details-validation-message details-validation-' + type;
        messageDiv.textContent = message;
        messageDiv.style.display = 'block';
    }
}

/**
 * Effacer le message de validation
 */
function clearLabelValidationMessage() {
    const messageDiv = document.getElementById('labelValidationMessage');
    if (messageDiv) {
        messageDiv.style.display = 'none';
        messageDiv.textContent = '';
    }
}
