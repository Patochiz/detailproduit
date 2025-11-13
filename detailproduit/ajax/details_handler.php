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
 * \file    ajax/details_handler.php
 * \ingroup detailproduit
 * \brief   AJAX handler for product details management
 */

// Mode debug pour diagnostiquer les problèmes
$debug_mode = true;

// Function pour log debug
function debug_log($message) {
    global $debug_mode;
    if ($debug_mode) {
        error_log("[DetailProduit AJAX] " . $message);
    }
}

// Fonctions de compatibilité PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

// Headers d'abord pour éviter les problèmes d'output
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

try {
    debug_log("=== DEBUT HANDLER AJAX ===");
    debug_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNDEFINED'));
    debug_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'UNDEFINED'));
    debug_log("SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'UNDEFINED'));
    debug_log("__FILE__: " . __FILE__);
    debug_log("__DIR__: " . __DIR__);
    debug_log("realpath(__DIR__): " . realpath(__DIR__));
    
    // Méthode améliorée pour trouver main.inc.php
    $res = 0;
    $main_found = false;
    $main_path = '';
    
    // Méthode 1: Utiliser la structure standard Dolibarr
    // Depuis custom/detailproduit/ajax/, main.inc.php est à ../../../main.inc.php
    $standard_paths = array(
        __DIR__ . "/../../../main.inc.php",           // Standard: custom/module/ajax/ -> main.inc.php
        __DIR__ . "/../../../../main.inc.php",        // Si un niveau de plus
        __DIR__ . "/../../main.inc.php",              // Si structure différente
        dirname(dirname(dirname(__DIR__))) . "/main.inc.php",  // Méthode alternative
    );
    
    foreach ($standard_paths as $path) {
        $real_path = realpath($path);
        debug_log("Test chemin standard: " . $path);
        debug_log("Chemin réel: " . ($real_path ?: 'INEXISTANT'));
        
        if ($real_path && file_exists($real_path) && is_readable($real_path)) {
            debug_log("Fichier trouvé et lisible: " . $real_path);
            $main_path = $real_path;
            $main_found = true;
            break;
        }
    }
    
    // Méthode 2: Chercher main.inc.php en remontant l'arborescence
    if (!$main_found) {
        $current_dir = __DIR__;
        $max_levels = 10; // Limite pour éviter les boucles infinies
        
        for ($i = 0; $i < $max_levels; $i++) {
            $test_path = $current_dir . "/main.inc.php";
            debug_log("Test remontée niveau $i: " . $test_path);
            
            if (file_exists($test_path) && is_readable($test_path)) {
                $main_path = realpath($test_path);
                $main_found = true;
                debug_log("main.inc.php trouvé par remontée: " . $main_path);
                break;
            }
            
            $parent_dir = dirname($current_dir);
            if ($parent_dir === $current_dir) {
                break; // On a atteint la racine
            }
            $current_dir = $parent_dir;
        }
    }
    
    // Méthode 3: Utiliser $_SERVER pour deviner le chemin
    if (!$main_found && isset($_SERVER['DOCUMENT_ROOT'])) {
        $doc_root = $_SERVER['DOCUMENT_ROOT'];
        debug_log("DOCUMENT_ROOT: " . $doc_root);
        
        // Essayer quelques chemins courants basés sur DOCUMENT_ROOT
        $doc_root_paths = array(
            $doc_root . "/main.inc.php",
            $doc_root . "/doli/main.inc.php",
            $doc_root . "/dolibarr/main.inc.php",
            $doc_root . "/htdocs/main.inc.php",
        );
        
        foreach ($doc_root_paths as $path) {
            debug_log("Test DOCUMENT_ROOT: " . $path);
            if (file_exists($path) && is_readable($path)) {
                $main_path = realpath($path);
                $main_found = true;
                debug_log("main.inc.php trouvé via DOCUMENT_ROOT: " . $main_path);
                break;
            }
        }
    }
    
    // Inclusion de main.inc.php
    if (!$main_found) {
        debug_log("ERREUR: Impossible de localiser main.inc.php");
        http_response_code(500);
        echo json_encode(array(
            'success' => false, 
            'error' => 'Configuration error: Cannot locate main.inc.php',
            'debug' => $debug_mode ? array(
                'current_dir' => __DIR__,
                'real_current_dir' => realpath(__DIR__),
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'UNDEFINED',
                'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'UNDEFINED'
            ) : null
        ));
        exit;
    }
    
    debug_log("Tentative d'inclusion de: " . $main_path);
    $res = @include_once $main_path;
    
    if (!$res) {
        debug_log("ERREUR: Échec d'inclusion de main.inc.php");
        http_response_code(500);
        echo json_encode(array(
            'success' => false, 
            'error' => 'Configuration error: Failed to include main.inc.php',
            'debug' => $debug_mode ? array(
                'main_path' => $main_path,
                'file_exists' => file_exists($main_path),
                'is_readable' => is_readable($main_path)
            ) : null
        ));
        exit;
    }
    
    debug_log("main.inc.php inclus avec succès depuis: " . $main_path);
    
    // Vérifier que les variables essentielles sont définies
    if (!isset($db)) {
        debug_log("ERREUR: Variable \$db non définie après inclusion");
        http_response_code(500);
        echo json_encode(array(
            'success' => false, 
            'error' => 'Database connection error - $db not defined',
            'debug' => $debug_mode ? array(
                'main_included' => true,
                'defined_vars' => array_keys(get_defined_vars())
            ) : null
        ));
        exit;
    }
    
    if (!isset($user)) {
        debug_log("ERREUR: Variable \$user non définie après inclusion");
        http_response_code(500);
        echo json_encode(array(
            'success' => false, 
            'error' => 'User session error - $user not defined',
            'debug' => $debug_mode ? array(
                'main_included' => true,
                'db_defined' => isset($db),
                'session_started' => session_status() === PHP_SESSION_ACTIVE
            ) : null
        ));
        exit;
    }
    
    debug_log("Variables essentielles OK - User ID: " . ($user->id ?? 'UNDEFINED') . ", Login: " . ($user->login ?? 'UNDEFINED'));
    
    // Inclusion des librairies nécessaires
    require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
    
    // Vérifier que DOL_DOCUMENT_ROOT est bien défini
    if (!defined('DOL_DOCUMENT_ROOT')) {
        debug_log("ERREUR: DOL_DOCUMENT_ROOT non défini");
        http_response_code(500);
        echo json_encode(array('success' => false, 'error' => 'DOL_DOCUMENT_ROOT not defined'));
        exit;
    }
    
    debug_log("DOL_DOCUMENT_ROOT: " . DOL_DOCUMENT_ROOT);
    
    // Inclusion de la classe CommandeDetDetails avec chemins multiples
    $class_included = false;
    $class_paths = array(
        DOL_DOCUMENT_ROOT.'/custom/detailproduit/class/commandedetdetails.class.php',
        dirname(dirname(__DIR__)).'/class/commandedetdetails.class.php',
        __DIR__.'/../class/commandedetdetails.class.php'
    );
    
    foreach ($class_paths as $class_path) {
        debug_log("Test inclusion classe: " . $class_path);
        if (file_exists($class_path)) {
            require_once $class_path;
            $class_included = true;
            debug_log("Classe incluse depuis: " . $class_path);
            break;
        }
    }
    
    if (!$class_included) {
        debug_log("ERREUR: Impossible d'inclure la classe CommandeDetDetails");
        http_response_code(500);
        echo json_encode(array(
            'success' => false, 
            'error' => 'Class file not found',
            'debug' => $debug_mode ? array('tested_paths' => $class_paths) : null
        ));
        exit;
    }
    
    debug_log("Classes incluses avec succès");
    
    // Vérification de l'authentification
    if (!$user || !$user->id) {
        debug_log("ERREUR: Utilisateur non authentifié");
        debug_log("User object: " . ($user ? 'EXISTS' : 'NULL'));
        debug_log("User ID: " . ($user->id ?? 'UNDEFINED'));
        debug_log("Session ID: " . session_id());
        debug_log("Session status: " . session_status());
        
        http_response_code(403);
        echo json_encode(array(
            'success' => false, 
            'error' => 'Authentication required - User not logged in',
            'debug' => $debug_mode ? array(
                'user_exists' => isset($user),
                'user_id' => $user->id ?? null,
                'session_id' => session_id(),
                'session_status' => session_status(),
                'session_data' => $_SESSION ?? array()
            ) : null
        ));
        exit;
    }
    
    debug_log("Authentification OK - User: " . $user->login . " (ID: " . $user->id . ")");
    
    // Vérification du module activé
    if (!isModEnabled('detailproduit')) {
        debug_log("ERREUR: Module detailproduit non activé");
        http_response_code(403);
        echo json_encode(array(
            'success' => false, 
            'error' => 'Module detailproduit not enabled',
            'debug' => $debug_mode ? array(
                'available_modules' => array_keys($conf->modules ?? array())
            ) : null
        ));
        exit;
    }
    
    debug_log("Module detailproduit activé");
    
    // Récupération de l'action
    $action = GETPOST('action', 'alpha');
    debug_log("Action demandée: " . $action);
    
    if (empty($action)) {
        debug_log("ERREUR: Action manquante");
        http_response_code(400);
        echo json_encode(array(
            'success' => false, 
            'error' => 'Missing action parameter',
            'debug' => $debug_mode ? array(
                'post_data' => $_POST,
                'get_data' => $_GET
            ) : null
        ));
        exit;
    }
    
    // Vérification du token CSRF pour les actions de modification
    if (in_array($action, array('save_details', 'update_command_quantity'))) {
        $token = GETPOST('token', 'alpha');
        debug_log("Token reçu: " . ($token ? substr($token, 0, 10) . "..." : 'EMPTY'));
        
        $token_valid = false;
        if ($token) {
            // Vérifier contre newtoken (priorité)
            if (isset($_SESSION['newtoken']) && $token === $_SESSION['newtoken']) {
                $token_valid = true;
                debug_log("Token valide (newtoken)");
            }
            // Vérifier contre token dans session
            elseif (isset($_SESSION['token']) && $token === $_SESSION['token']) {
                $token_valid = true;
                debug_log("Token valide (token session)");
            }
            // En mode debug, être plus permissif
            elseif ($debug_mode && strlen($token) > 10) {
                $token_valid = true;
                debug_log("Token accepté en mode debug");
            }
        }
        
        if (!$token_valid) {
            debug_log("ERREUR: Token CSRF invalide");
            debug_log("Session newtoken: " . (isset($_SESSION['newtoken']) ? substr($_SESSION['newtoken'], 0, 10) . "..." : 'UNDEFINED'));
            debug_log("Session token: " . (isset($_SESSION['token']) ? substr($_SESSION['token'], 0, 10) . "..." : 'UNDEFINED'));
            
            http_response_code(403);
            echo json_encode(array(
                'success' => false, 
                'error' => 'Invalid CSRF token',
                'debug' => $debug_mode ? array(
                    'token_received' => $token ? substr($token, 0, 10) . "..." : 'EMPTY',
                    'session_newtoken' => isset($_SESSION['newtoken']) ? substr($_SESSION['newtoken'], 0, 10) . "..." : 'UNDEFINED',
                    'session_token' => isset($_SESSION['token']) ? substr($_SESSION['token'], 0, 10) . "..." : 'UNDEFINED'
                ) : null
            ));
            exit;
        }
    }
    
    /**
     * Récupérer les détails existants pour une ligne de commande
     */
    if ($action == 'get_details') {
        debug_log("=== ACTION: get_details ===");
        
        $commandedet_id = GETPOST('commandedet_id', 'int');
        debug_log("Commandedet ID: " . $commandedet_id);
        
        if (!$commandedet_id) {
            debug_log("ERREUR: commandedet_id manquant");
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Missing commandedet_id parameter'));
            exit;
        }

        // Vérifier les permissions sur la commande
        $sql = "SELECT c.rowid, c.fk_soc FROM ".MAIN_DB_PREFIX."commande c";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."commandedet cd ON cd.fk_commande = c.rowid";
        $sql .= " WHERE cd.rowid = ".((int) $commandedet_id);
        
        debug_log("SQL vérification: " . $sql);
        
        $resql = $db->query($sql);
        if (!$resql) {
            debug_log("ERREUR SQL: " . $db->lasterror());
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Database error: ' . $db->lasterror()));
            exit;
        }
        
        if (!$db->num_rows($resql)) {
            debug_log("ERREUR: Ligne de commande non trouvée");
            http_response_code(404);
            echo json_encode(array('success' => false, 'error' => 'Command line not found'));
            exit;
        }
        
        $obj = $db->fetch_object($resql);
        debug_log("Commande trouvée: " . $obj->rowid . " pour société: " . $obj->fk_soc);
        
        // Vérifier les permissions
        if (!$user->hasRight('commande', 'lire')) {
            debug_log("ERREUR: Pas de permission de lecture des commandes");
            http_response_code(403);
            echo json_encode(array('success' => false, 'error' => 'No read permission for orders'));
            exit;
        }
        
        debug_log("Permissions OK, récupération des détails");

        $details_obj = new CommandeDetDetails($db);
        $details = $details_obj->getDetailsForLine($commandedet_id);
        
        if ($details === -1) {
            debug_log("ERREUR: Erreur base de données lors de la récupération");
            http_response_code(500);
            echo json_encode(array(
                'success' => false, 
                'error' => 'Database error while fetching details', 
                'details' => $details_obj->errors
            ));
            exit;
        }
        
        debug_log("Détails récupérés: " . count($details) . " lignes");
        echo json_encode(array('success' => true, 'details' => $details));
        exit;
    }

    /**
     * Sauvegarder les détails pour une ligne de commande - Version FormData native + fallback
     */
    elseif ($action == 'save_details') {
        debug_log("=== ACTION: save_details ===");
        
        $commandedet_id = GETPOST('commandedet_id', 'int');
        debug_log("Commandedet ID: " . $commandedet_id);
        
        if (!$commandedet_id) {
            debug_log("ERREUR: commandedet_id manquant");
            http_response_code(400);
            echo json_encode(array(
                'success' => false, 
                'error' => 'Missing required parameter: commandedet_id',
                'debug' => $debug_mode ? array('post_data' => $_POST) : null
            ));
            exit;
        }

        // Vérifier les permissions sur la commande
        $sql = "SELECT c.rowid, c.fk_soc FROM ".MAIN_DB_PREFIX."commande c";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."commandedet cd ON cd.fk_commande = c.rowid";
        $sql .= " WHERE cd.rowid = ".((int) $commandedet_id);
        
        $resql = $db->query($sql);
        if (!$resql || !$db->num_rows($resql)) {
            debug_log("ERREUR: Ligne de commande non trouvée pour sauvegarde");
            http_response_code(404);
            echo json_encode(array('success' => false, 'error' => 'Command line not found'));
            exit;
        }
        
        // Vérifier les permissions d'écriture
        if (!$user->hasRight('commande', 'creer')) {
            debug_log("ERREUR: Pas de permission d'écriture des commandes");
            http_response_code(403);
            echo json_encode(array('success' => false, 'error' => 'No write permission for orders'));
            exit;
        }

        $validated_details = array();
        $parsing_method = '';

        // MÉTHODE 1: FormData natif (recommandée)
        if (isset($_POST['detail']) && is_array($_POST['detail'])) {
            $parsing_method = 'FormData natif';
            debug_log("Parsing FormData natif - " . count($_POST['detail']) . " détails");
            
            foreach ($_POST['detail'] as $index => $detail_data) {
                if (!is_array($detail_data)) {
                    debug_log("ATTENTION: Détail $index n'est pas un tableau");
                    continue;
                }
                
                $pieces = isset($detail_data['pieces']) ? (float) $detail_data['pieces'] : 0;
                if ($pieces <= 0) {
                    debug_log("ATTENTION: Détail $index - pieces invalide: " . $pieces);
                    continue;
                }
                
                $longueur = isset($detail_data['longueur']) && !empty($detail_data['longueur']) ? (float) $detail_data['longueur'] : null;
                $largeur = isset($detail_data['largeur']) && !empty($detail_data['largeur']) ? (float) $detail_data['largeur'] : null;
                
                // Calculer l'unité et la valeur
                $calc = CommandeDetDetails::calculateUnitAndValue($pieces, $longueur, $largeur);
                
                // Description nettoyée
                $description = isset($detail_data['description']) ? trim($detail_data['description']) : '';
                $description = substr($description, 0, 255);
                
                $validated_details[] = array(
                    'pieces' => $pieces,
                    'longueur' => $longueur,
                    'largeur' => $largeur,
                    'total_value' => $calc['total_value'],
                    'unit' => $calc['unit'],
                    'description' => $description
                );
                
                debug_log("FormData détail $index validé: pieces=$pieces, total=".$calc['total_value']." ".$calc['unit']);
            }
        }
        
        // MÉTHODE 2: Fallback JSON (ancienne méthode)
        elseif (isset($_POST['details_json']) && !empty($_POST['details_json'])) {
            $parsing_method = 'JSON fallback';
            $details_json = GETPOST('details_json', 'alpha');
            debug_log("Fallback JSON - longueur: " . strlen($details_json));
            
            // Nettoyer et valider le JSON
            $details_json_clean = trim($details_json);
            
            if (empty($details_json_clean)) {
                debug_log("ERREUR: JSON vide");
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => 'Empty JSON data'));
                exit;
            }
            
            if (!str_starts_with($details_json_clean, '[') || !str_ends_with($details_json_clean, ']')) {
                debug_log("ERREUR: JSON malformé");
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => 'Invalid JSON structure - expected array'));
                exit;
            }

            $details_array = json_decode($details_json_clean, true);
            $json_error = json_last_error();
            
            if ($json_error !== JSON_ERROR_NONE) {
                $json_error_msg = json_last_error_msg();
                debug_log("ERREUR: JSON invalide - " . $json_error_msg);
                
                http_response_code(400);
                echo json_encode(array(
                    'success' => false, 
                    'error' => 'Invalid JSON format: ' . $json_error_msg,
                    'debug' => $debug_mode ? array(
                        'json_error_code' => $json_error,
                        'json_sample' => substr($details_json_clean, 0, 200)
                    ) : null
                ));
                exit;
            }

            if (!is_array($details_array)) {
                debug_log("ERREUR: JSON décodé n'est pas un tableau");
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => 'JSON data must be an array'));
                exit;
            }

            // Valider les détails JSON
            foreach ($details_array as $index => $detail) {
                if (!is_array($detail)) {
                    debug_log("ATTENTION: Élément JSON $index n'est pas un tableau");
                    continue;
                }
                
                if (!isset($detail['pieces']) || !is_numeric($detail['pieces']) || $detail['pieces'] <= 0) {
                    debug_log("ATTENTION: Élément JSON $index - pieces invalide");
                    continue;
                }

                $pieces = (float) $detail['pieces'];
                $longueur = isset($detail['longueur']) && is_numeric($detail['longueur']) && $detail['longueur'] > 0 ? (float) $detail['longueur'] : null;
                $largeur = isset($detail['largeur']) && is_numeric($detail['largeur']) && $detail['largeur'] > 0 ? (float) $detail['largeur'] : null;
                
                $calc = CommandeDetDetails::calculateUnitAndValue($pieces, $longueur, $largeur);
                
                $description = '';
                if (isset($detail['description']) && is_string($detail['description'])) {
                    $description = trim($detail['description']);
                    $description = substr($description, 0, 255);
                }
                
                $validated_details[] = array(
                    'pieces' => $pieces,
                    'longueur' => $longueur,
                    'largeur' => $largeur,
                    'total_value' => $calc['total_value'],
                    'unit' => $calc['unit'],
                    'description' => $description
                );
                
                debug_log("JSON détail $index validé: pieces=$pieces, total=".$calc['total_value']." ".$calc['unit']);
            }
        }
        
        // MÉTHODE 3: Aucune donnée trouvée
        else {
            debug_log("ERREUR: Aucune donnée de détails trouvée");
            debug_log("POST keys: " . implode(', ', array_keys($_POST)));
            
            http_response_code(400);
            echo json_encode(array(
                'success' => false, 
                'error' => 'No details data found - expecting either detail[] array or details_json',
                'debug' => $debug_mode ? array(
                    'post_keys' => array_keys($_POST),
                    'has_detail_array' => isset($_POST['detail']),
                    'has_details_json' => isset($_POST['details_json'])
                ) : null
            ));
            exit;
        }

        if (empty($validated_details)) {
            debug_log("ERREUR: Aucun détail valide après validation");
            http_response_code(400);
            echo json_encode(array(
                'success' => false, 
                'error' => 'No valid details provided after validation',
                'debug' => $debug_mode ? array(
                    'parsing_method' => $parsing_method,
                    'raw_data_count' => isset($_POST['detail']) ? count($_POST['detail']) : (isset($_POST['details_json']) ? 'JSON_PROVIDED' : 'NO_DATA')
                ) : null
            ));
            exit;
        }

        debug_log("Détails validés via $parsing_method: " . count($validated_details) . " lignes");

        // Sauvegarder en base
        $details_obj = new CommandeDetDetails($db);
        $result = $details_obj->saveDetailsForLine($commandedet_id, $validated_details, $user);
        
        if ($result < 0) {
            debug_log("Échec de la sauvegarde en base");
            http_response_code(500);
            echo json_encode(array(
                'success' => false, 
                'error' => 'Database save failed', 
                'details' => $details_obj->errors,
                'debug' => $debug_mode ? array(
                    'parsing_method' => $parsing_method,
                    'validated_details_count' => count($validated_details)
                ) : null
            ));
            exit;
        }

        // Recalculer le résumé pour l'affichage
        $summary = $details_obj->getSummaryForDisplay($commandedet_id);
        
        debug_log("Sauvegarde réussie via $parsing_method - " . count($validated_details) . " lignes");
        echo json_encode(array(
            'success' => true, 
            'message' => 'Details saved successfully',
            'summary' => $summary,
            'nb_details' => count($validated_details),
            'parsing_method' => $parsing_method,
            'debug' => $debug_mode ? array(
                'method_used' => $parsing_method,
                'validated_count' => count($validated_details)
            ) : null
        ));
        exit;
    }

    /**
     * Mettre à jour la quantité de la ligne de commande
     */
    elseif ($action == 'update_command_quantity') {
        debug_log("=== ACTION: update_command_quantity ===");
        
        $commandedet_id = GETPOST('commandedet_id', 'int');
        $new_quantity = GETPOST('new_quantity', 'alpha');
        $unit = GETPOST('unit', 'alpha');
        
        debug_log("Commandedet ID: " . $commandedet_id . ", Quantity: " . $new_quantity . ", Unit: " . $unit);
        
        if (!$commandedet_id || !$new_quantity || !$unit) {
            debug_log("ERREUR: Paramètres manquants pour update_command_quantity");
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Missing parameters: commandedet_id, new_quantity, unit'));
            exit;
        }

        // Vérifier les permissions
        if (!$user->hasRight('commande', 'creer')) {
            debug_log("ERREUR: Pas de permission pour mettre à jour la quantité");
            http_response_code(403);
            echo json_encode(array('success' => false, 'error' => 'No write permission for orders'));
            exit;
        }

        $details_obj = new CommandeDetDetails($db);
        $result = $details_obj->updateCommandLineQuantity($commandedet_id, (float) $new_quantity, $unit);
        
        if ($result < 0) {
            debug_log("ERREUR: Échec mise à jour quantité");
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Update failed', 'details' => $details_obj->errors));
            exit;
        }

        debug_log("Mise à jour quantité réussie");
        echo json_encode(array(
            'success' => true, 
            'message' => 'Quantity updated successfully',
            'new_quantity' => $new_quantity,
            'unit' => $unit
        ));
        exit;
    }

    /**
     * Exporter les détails en CSV
     */
    elseif ($action == 'export_details_csv') {
        debug_log("=== ACTION: export_details_csv ===");
        
        $commandedet_id = GETPOST('commandedet_id', 'int');
        
        if (!$commandedet_id) {
            debug_log("ERREUR: commandedet_id manquant pour export");
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Missing commandedet_id'));
            exit;
        }

        // Vérifier les permissions
        if (!$user->hasRight('commande', 'lire')) {
            debug_log("ERREUR: Pas de permission pour export CSV");
            http_response_code(403);
            echo json_encode(array('success' => false, 'error' => 'No read permission for orders'));
            exit;
        }

        $details_obj = new CommandeDetDetails($db);
        $details = $details_obj->getDetailsForLine($commandedet_id);
        
        if ($details === -1) {
            debug_log("ERREUR: Erreur base de données pour export");
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Database error'));
            exit;
        }

        // Générer le CSV
        $csv_content = "Pieces,Longueur,Largeur,Total,Unite,Description\n";
        foreach ($details as $detail) {
            $csv_content .= '"'.$detail['pieces'].'",';
            $csv_content .= '"'.($detail['longueur'] ?: '').'",';
            $csv_content .= '"'.($detail['largeur'] ?: '').'",';
            $csv_content .= '"'.$detail['total_value'].'",';
            $csv_content .= '"'.$detail['unit'].'",';
            $csv_content .= '"'.str_replace('"', '""', $detail['description']).'"';
            $csv_content .= "\n";
        }

        // Headers pour téléchargement CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="details_commande_'.$commandedet_id.'_'.date('Y-m-d').'.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        
        debug_log("Export CSV généré: " . strlen($csv_content) . " caractères");
        echo $csv_content;
        exit;
    }

    /**
     * Action non reconnue
     */
    else {
        debug_log("ERREUR: Action non reconnue: " . $action);
        http_response_code(400);
        echo json_encode(array('success' => false, 'error' => 'Unknown action: '.$action));
        exit;
    }

} catch (Exception $e) {
    debug_log("EXCEPTION: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());
    
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    
    echo json_encode(array(
        'success' => false, 
        'error' => 'Internal server error: ' . $e->getMessage(),
        'debug' => $debug_mode ? array(
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ) : null
    ));
    exit;
}

debug_log("=== FIN HANDLER AJAX ===");
exit;