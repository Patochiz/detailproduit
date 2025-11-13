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
 * \file    core/hooks/detailproduit.class.php
 * \ingroup detailproduit
 * \brief   Hook file for detailproduit module
 */

/**
 * Class ActionsDetailproduit
 */
class ActionsDetailproduit
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * @var bool Flag pour éviter les doublons d'inclusion
     */
    private static $assets_included = false;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Execute action
     *
     * @param array         $parameters Array of parameters
     * @param CommonObject  $object     The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string        $action     Current action (if set). Generally create or edit or null
     * @param HookManager   $hookmanager Hook manager propagated to allow calling another hook
     * @return int                      Return integer < 0 on error, 0 on success, 1 to replace standard code
     */
    public function getNomUrl($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        return 0;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        // Gérer les actions AJAX pour les détails produit
        if (in_array($action, array('get_details', 'save_details', 'update_command_quantity', 'export_details_csv'))) {
            require_once DOL_DOCUMENT_ROOT.'/custom/detailproduit/ajax/details_handler.php';
            exit();
        }

        return 0;
    }

    /**
     * Méthode utilitaire pour inclure les assets (CSS/JS) une seule fois
     */
    private function includeAssets()
    {
        if (self::$assets_included) {
            return '';
        }

        self::$assets_included = true;

        $output = '';
        
        // CSS
        $output .= '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/detailproduit/css/details_popup.css', 1).'">';
        
        // Variables JavaScript globales injectées de manière robuste
        $output .= '<script type="text/javascript">';
        $output .= '// Variables globales pour le module detailproduit' . "\n";
        $output .= 'window.DOL_URL_ROOT = "'.DOL_URL_ROOT.'";' . "\n";
        $output .= 'window.token = "'.newToken().'";' . "\n";
        $output .= 'window.newtoken = "'.newToken().'";' . "\n";
        
        // Méta tag pour le token CSRF (fallback)
        $output .= 'if (document.head) {' . "\n";
        $output .= '  var metaToken = document.createElement("meta");' . "\n";
        $output .= '  metaToken.name = "csrf-token";' . "\n";
        $output .= '  metaToken.content = "'.newToken().'";' . "\n";
        $output .= '  document.head.appendChild(metaToken);' . "\n";
        $output .= '}' . "\n";
        
        // Debug pour vérifier l'injection
        $output .= 'console.log("🔧 Module detailproduit - Variables injectées:", {' . "\n";
        $output .= '  DOL_URL_ROOT: window.DOL_URL_ROOT,' . "\n";
        $output .= '  token: window.token ? window.token.substring(0,10) + "..." : "UNDEFINED",' . "\n";
        $output .= '  newtoken: window.newtoken ? window.newtoken.substring(0,10) + "..." : "UNDEFINED"' . "\n";
        $output .= '});' . "\n";
        $output .= '</script>';

        // JavaScript pour la mise à jour de label (product_type = 1) - doit être chargé EN PREMIER
        $output .= '<script type="text/javascript" src="'.dol_buildpath('/detailproduit/js/label_update.js', 1).'"></script>';

        // JavaScript principal - doit être chargé APRÈS label_update.js
        $output .= '<script type="text/javascript" src="'.dol_buildpath('/detailproduit/js/details_popup.js', 1).'"></script>';
        
        return $output;
    }

    /**
     * Hook pour ajouter du contenu dans l'en-tête des pages
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process
     * @param   string          $action         Current action
     * @param   HookManager     $hookmanager    Hook manager
     * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        // Contextes où le module doit être actif
        $active_contexts = array('ordercard', 'ordersuppliercard', 'invoicecard', 'propalcard');
        
        // Vérifier si on est sur une page de commande
        $is_order_page = false;
        
        // Méthode 1: Vérifier le contexte
        if (isset($parameters['context']) && in_array($parameters['context'], $active_contexts)) {
            $is_order_page = true;
        }
        
        // Méthode 2: Vérifier l'URL
        if (!$is_order_page && isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '/commande/card.php') !== false || 
                strpos($uri, '/order/card.php') !== false ||
                strpos($uri, '/fourn/commande/card.php') !== false) {
                $is_order_page = true;
            }
        }
        
        // Méthode 3: Vérifier le script actuel
        if (!$is_order_page && isset($_SERVER['SCRIPT_NAME'])) {
            $script = $_SERVER['SCRIPT_NAME'];
            if (strpos($script, '/commande/card.php') !== false || 
                strpos($script, '/order/card.php') !== false ||
                strpos($script, '/fourn/commande/card.php') !== false) {
                $is_order_page = true;
            }
        }

        if ($is_order_page) {
            $this->resprints .= $this->includeAssets();
        }

        return 0;
    }

    /**
     * Hook alternatif pour les pages où addHtmlHeader ne fonctionne pas
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process
     * @param   string          $action         Current action
     * @param   HookManager     $hookmanager    Hook manager
     * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        // Inclure les assets si pas encore fait
        $active_contexts = array('ordercard', 'ordersuppliercard');
        
        if (isset($parameters['context']) && in_array($parameters['context'], $active_contexts)) {
            $this->resprints .= $this->includeAssets();
        }

        return 0;
    }

    /**
     * Hook pour ajouter du contenu après les lignes de tableau
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process
     * @param   string          $action         Current action
     * @param   HookManager     $hookmanager    Hook manager
     * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printTablesLineFooter($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        // Ajouter un script pour initialiser les boutons après chargement du tableau
        if (isset($parameters['context']) && $parameters['context'] == 'ordercard' && 
            is_object($object) && $object->element == 'commande') {
            
            // Vérifier les permissions
            if (!$user->hasRight('commande', 'lire')) {
                return 0;
            }

            // S'assurer que les assets sont inclus
            $this->resprints .= $this->includeAssets();

            // Script d'initialisation
            $this->resprints .= '<script type="text/javascript">';
            $this->resprints .= 'document.addEventListener("DOMContentLoaded", function() {';
            $this->resprints .= '    console.log("🔄 Initialisation des boutons détails...");';
            $this->resprints .= '    // Attendre que le DOM soit complètement chargé';
            $this->resprints .= '    setTimeout(function() {';
            $this->resprints .= '        if (typeof addDetailsButtonsToExistingLines === "function") {';
            $this->resprints .= '            addDetailsButtonsToExistingLines();';
            $this->resprints .= '        } else {';
            $this->resprints .= '            console.error("❌ Fonction addDetailsButtonsToExistingLines non trouvée");';
            $this->resprints .= '        }';
            $this->resprints .= '    }, 500);';
            $this->resprints .= '});';
            $this->resprints .= '</script>';
        }

        return 0;
    }

    /**
     * Hook pour ajouter du contenu après le body
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process
     * @param   string          $action         Current action
     * @param   HookManager     $hookmanager    Hook manager
     * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
     */
    public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        // Fallback: s'assurer que les assets sont inclus sur les pages de commande
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '/commande/card.php') !== false || 
                strpos($uri, '/order/card.php') !== false ||
                strpos($uri, '/fourn/commande/card.php') !== false) {
                
                $this->resprints .= $this->includeAssets();
                
                // CSS supplémentaire pour l'intégration
                $this->resprints .= '<style type="text/css">';
                $this->resprints .= '/* Styles spécifiques pour l\'intégration Dolibarr */';
                $this->resprints .= '.details-btn-open { margin-left: 5px !important; }';
                $this->resprints .= '.details-summary { font-style: italic; }';
                $this->resprints .= '</style>';
            }
        }

        return 0;
    }

    /**
     * Hook de fin de page - dernier recours pour l'inclusion
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process
     * @param   string          $action         Current action
     * @param   HookManager     $hookmanager    Hook manager
     * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        // Dernier recours: inclure les assets sur les pages de commande
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '/commande/card.php') !== false || 
                strpos($uri, '/order/card.php') !== false ||
                strpos($uri, '/fourn/commande/card.php') !== false) {
                
                $this->resprints .= $this->includeAssets();
                
                // Script de vérification final
                $this->resprints .= '<script type="text/javascript">';
                $this->resprints .= '// Vérification finale du module detailproduit';
                $this->resprints .= 'document.addEventListener("DOMContentLoaded", function() {';
                $this->resprints .= '    console.log("🔍 Vérification finale module detailproduit");';
                $this->resprints .= '    if (typeof addDetailsButtonsToExistingLines === "undefined") {';
                $this->resprints .= '        console.error("❌ Module detailproduit non chargé correctement");';
                $this->resprints .= '    } else {';
                $this->resprints .= '        console.log("✅ Module detailproduit chargé");';
                $this->resprints .= '        setTimeout(addDetailsButtonsToExistingLines, 1000);';
                $this->resprints .= '    }';
                $this->resprints .= '});';
                $this->resprints .= '</script>';
            }
        }

        return 0;
    }

    /**
     * Hook pour ajouter le bouton Détails après chaque ligne de commande
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process
     * @param   string          $action         Current action
     * @param   HookManager     $hookmanager    Hook manager
     * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printObjectLine($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        // Vérifier qu'on est sur une ligne de commande
        if (isset($parameters['line']) && isset($parameters['object']) && 
            in_array($parameters['object']->element, array('commande', 'order'))) {
            
            $line = $parameters['line'];
            
            // Vérifier les permissions
            if (!$user->hasRight('commande', 'lire')) {
                return 0;
            }

            dol_include_once('/detailproduit/class/commandedetdetails.class.php');
            $details_obj = new CommandeDetDetails($this->db);
            
            // Récupérer le résumé des détails existants
            $summary = $details_obj->getSummaryForDisplay($line->rowid);
            
            // Ajouter un script pour initialiser le bouton après chargement de la page
            $this->resprints .= '<script type="text/javascript">';
            $this->resprints .= 'document.addEventListener("DOMContentLoaded", function() {';
            $this->resprints .= '    if (typeof addDetailsButtonToLine === "function") {';
            $this->resprints .= '        addDetailsButtonToLine('.((int) $line->rowid).', null);';
            $this->resprints .= '    }';
            $this->resprints .= '});';
            $this->resprints .= '</script>';
        }

        return 0;
    }

    /**
     * Hook pour ajouter des boutons d'actions
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process
     * @param   string          $action         Current action
     * @param   HookManager     $hookmanager    Hook manager
     * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs, $db;

        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        // Ajouter le bouton Détails dans les actions de ligne de commande
        if (isset($parameters['context']) && $parameters['context'] == 'ordercard' && 
            is_object($object) && $object->element == 'commande') {
            
            if (isset($parameters['line']) && 
                $user->hasRight('commande', 'lire')) {
                
                $line = $parameters['line'];
                
                // Déterminer le type de produit (9 = sous-total, autre = standard)
                $product_type = isset($line->product_type) ? (int)$line->product_type : 0;
                $socid = isset($object->socid) ? (int)$object->socid : 0;
                
                // Bouton détails avec attributs data pour le type et socid
                $onclick_function = ($product_type == 9) 
                    ? "openLabelUpdateModal(".$line->rowid.", ".$socid.", '".dol_escape_js($line->product_label)."')" 
                    : "openDetailsModal(".$line->rowid.", ".$line->qty.", '".dol_escape_js($line->product_label)."')";
                
                $button_title = ($product_type == 9) 
                    ? 'Modifier le label' 
                    : $langs->trans('ProductDetails');
                
                $this->resprints .= '<a href="#" class="editfielda details-btn-open" ';
                $this->resprints .= 'data-product-type="'.$product_type.'" ';
                $this->resprints .= 'data-socid="'.$socid.'" ';
                $this->resprints .= 'data-rowid="'.$line->rowid.'" ';
                $this->resprints .= 'data-qty="'.$line->qty.'" ';
                $this->resprints .= 'data-label="'.dol_escape_htmltag($line->product_label).'" ';
                $this->resprints .= 'onclick="'.$onclick_function.'; return false;" ';
                $this->resprints .= 'title="'.$button_title.'" ';
                $this->resprints .= 'style="margin-left: 5px; font-size: 11px; padding: 2px 6px; background: #17a2b8; color: white; border-radius: 2px; text-decoration: none;">';
                $this->resprints .= '📋';
                $this->resprints .= '</a>';
                
                // Supprimer l'affichage du résumé (désactivé)
                // dol_include_once('/detailproduit/class/commandedetdetails.class.php');
                // $details_obj = new CommandeDetDetails($db);
                // $summary = $details_obj->getSummaryForDisplay($line->rowid);
                // if ($summary) {
                //     $this->resprints .= '<span class="details-summary details-has-content" style="font-size: 11px; color: #28a745; margin-left: 5px; font-weight: 500;">'.$summary.'</span>';
                // }
            }
        }

        return 0;
    }
}
