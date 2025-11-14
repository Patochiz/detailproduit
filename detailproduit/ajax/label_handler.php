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
 * \file    ajax/label_handler.php
 * \ingroup detailproduit
 * \brief   AJAX handler for service label management
 */

// Mode debug
$debug_mode = true;

// Function pour log debug
function debug_log($message) {
    global $debug_mode;
    if ($debug_mode) {
        error_log("[DetailProduit Label AJAX] " . $message);
    }
}

// Headers
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
}

try {
    debug_log("=== DEBUT LABEL HANDLER AJAX ===");

    // Trouver main.inc.php
    $main_found = false;
    $main_path = '';

    $standard_paths = array(
        __DIR__ . "/../../../main.inc.php",
        __DIR__ . "/../../../../main.inc.php",
        __DIR__ . "/../../main.inc.php",
    );

    foreach ($standard_paths as $path) {
        $real_path = realpath($path);
        if ($real_path && file_exists($real_path) && is_readable($real_path)) {
            $main_path = $real_path;
            $main_found = true;
            break;
        }
    }

    if (!$main_found) {
        $current_dir = __DIR__;
        for ($i = 0; $i < 10; $i++) {
            $test_path = $current_dir . "/main.inc.php";
            if (file_exists($test_path) && is_readable($test_path)) {
                $main_path = realpath($test_path);
                $main_found = true;
                break;
            }
            $parent_dir = dirname($current_dir);
            if ($parent_dir === $current_dir) break;
            $current_dir = $parent_dir;
        }
    }

    if (!$main_found) {
        http_response_code(500);
        echo json_encode(array('success' => false, 'error' => 'Cannot locate main.inc.php'));
        exit;
    }

    $res = @include_once $main_path;

    if (!$res || !isset($db) || !isset($user)) {
        http_response_code(500);
        echo json_encode(array('success' => false, 'error' => 'Failed to include main.inc.php'));
        exit;
    }

    // Vérifier authentification
    if (!$user || !$user->id) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'error' => 'Authentication required'));
        exit;
    }

    // Vérifier module activé
    if (!isModEnabled('detailproduit')) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'error' => 'Module detailproduit not enabled'));
        exit;
    }

    // Récupérer l'action
    $action = GETPOST('action', 'alpha');
    debug_log("Action: " . $action);

    if (empty($action)) {
        http_response_code(400);
        echo json_encode(array('success' => false, 'error' => 'Missing action parameter'));
        exit;
    }

    // Vérification CSRF pour les actions de modification
    if (in_array($action, array('save_label_update'))) {
        $token = GETPOST('token', 'alpha');
        $token_valid = false;

        if ($token) {
            if (isset($_SESSION['newtoken']) && $token === $_SESSION['newtoken']) {
                $token_valid = true;
            } elseif (isset($_SESSION['token']) && $token === $_SESSION['token']) {
                $token_valid = true;
            } elseif ($debug_mode && strlen($token) > 10) {
                $token_valid = true;
            }
        }

        if (!$token_valid) {
            http_response_code(403);
            echo json_encode(array('success' => false, 'error' => 'Invalid CSRF token'));
            exit;
        }
    }

    /**
     * Récupérer les contacts du tiers (hors civilité "Adresse")
     */
    if ($action == 'get_thirdparty_contacts') {
        debug_log("=== ACTION: get_thirdparty_contacts ===");

        $socid = GETPOST('socid', 'int');
        debug_log("Socid reçu: " . $socid);

        if (!$socid || $socid <= 0) {
            debug_log("ERREUR: socid invalide ou manquant");
            http_response_code(400);
            echo json_encode(array(
                'success' => false, 
                'error' => 'Missing or invalid socid parameter',
                'debug' => array('socid' => $socid)
            ));
            exit;
        }

        // Vérifier permissions
        if (!$user->hasRight('societe', 'lire')) {
            debug_log("ERREUR: Pas de permission lecture tiers");
            http_response_code(403);
            echo json_encode(array('success' => false, 'error' => 'No read permission for thirdparties'));
            exit;
        }

        // Vérifier que le tiers existe
        $sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE rowid = ".((int) $socid);
        $resql_check = $db->query($sql_check);
        if (!$resql_check || $db->num_rows($resql_check) == 0) {
            debug_log("ERREUR: Tiers introuvable - ID: " . $socid);
            http_response_code(404);
            echo json_encode(array(
                'success' => false, 
                'error' => 'Thirdparty not found',
                'debug' => array('socid' => $socid)
            ));
            exit;
        }
        debug_log("Tiers trouvé - ID: " . $socid);

        // Récupérer les contacts du tiers
        // Plusieurs stratégies pour exclure les adresses :
        // 1. Civilité != 'ADR'
        // 2. Civilité != 'MR0' (code Dolibarr pour "Adresse")
        // 3. lastname != 'ADR' et lastname != 'Adresse'
        $sql = "SELECT c.rowid, c.lastname, c.firstname, c.civility, c.email";
        $sql .= " FROM ".MAIN_DB_PREFIX."socpeople c";
        $sql .= " WHERE c.fk_soc = ".((int) $socid);
        $sql .= " AND c.statut = 1";  // Actif uniquement
        // Exclure les adresses de livraison/facturation
        $sql .= " AND (c.civility IS NULL OR (c.civility != 'ADR' AND c.civility != 'MR0'))";
        $sql .= " AND (c.lastname NOT IN ('ADR', 'Adresse', 'ADRESSE'))";
        $sql .= " ORDER BY c.lastname, c.firstname";

        debug_log("SQL contacts: " . $sql);

        $resql = $db->query($sql);
        if (!$resql) {
            debug_log("ERREUR SQL: " . $db->lasterror());
            http_response_code(500);
            echo json_encode(array(
                'success' => false, 
                'error' => 'Database error',
                'debug' => array('sql_error' => $db->lasterror())
            ));
            exit;
        }

        $num_contacts = $db->num_rows($resql);
        debug_log("Nombre de contacts trouvés: " . $num_contacts);

        $contacts = array();
        $skipped = 0;
        
        while ($obj = $db->fetch_object($resql)) {
            // Construire le nom du contact
            $name_parts = array();
            
            if (!empty($obj->firstname)) {
                $name_parts[] = $obj->firstname;
            }
            if (!empty($obj->lastname)) {
                $name_parts[] = $obj->lastname;
            }
            
            $name = trim(implode(' ', $name_parts));
            
            // Si pas de nom, utiliser l'email ou un identifiant par défaut
            if (empty($name)) {
                if (!empty($obj->email)) {
                    $name = $obj->email;
                } else {
                    $name = 'Contact #' . $obj->rowid;
                }
            }

            // Vérifier que ce n'est pas une adresse déguisée
            $name_lower = strtolower($name);
            if (strpos($name_lower, 'adresse') !== false || 
                strpos($name_lower, 'livraison') !== false ||
                strpos($name_lower, 'facturation') !== false) {
                debug_log("Contact ignoré (ressemble à une adresse): " . $name);
                $skipped++;
                continue;
            }

            $contacts[] = array(
                'id' => (int)$obj->rowid,
                'name' => $name
            );
            
            debug_log("Contact ajouté: ID=" . $obj->rowid . ", Name=" . $name);
        }

        debug_log("Contacts valides: " . count($contacts) . " | Ignorés: " . $skipped);

        // Retourner la liste même si vide
        echo json_encode(array(
            'success' => true, 
            'contacts' => $contacts,
            'debug' => array(
                'socid' => $socid,
                'total_found' => $num_contacts,
                'valid_contacts' => count($contacts),
                'skipped' => $skipped
            )
        ));
        exit;
    }

    /**
     * Récupérer les données de label existantes
     */
    elseif ($action == 'get_label_data') {
        debug_log("=== ACTION: get_label_data ===");

        $commandedet_id = GETPOST('commandedet_id', 'int');
        debug_log("Commandedet ID: " . $commandedet_id);

        if (!$commandedet_id) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Missing commandedet_id parameter'));
            exit;
        }

        // Vérifier permissions
        if (!$user->hasRight('commande', 'lire')) {
            http_response_code(403);
            echo json_encode(array('success' => false, 'error' => 'No read permission for orders'));
            exit;
        }

        // Récupérer les données stockées dans les extrafields de la ligne
        $sql = "SELECT ref_chantier FROM ".MAIN_DB_PREFIX."commandedet_extrafields";
        $sql .= " WHERE fk_object = ".((int) $commandedet_id);

        debug_log("SQL get label data: " . $sql);

        $resql = $db->query($sql);
        if (!$resql) {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Database error: ' . $db->lasterror()));
            exit;
        }

        $data = array(
            'n_commande' => '',
            'date_commande' => '',
            'contact' => '',
            'ref_commande' => ''
        );

        if ($db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            $data['ref_commande'] = $obj->ref_chantier ?: '';
            debug_log("Ref chantier trouvée: " . $data['ref_commande']);
        }

        echo json_encode(array('success' => true, 'data' => $data));
        exit;
    }

    /**
     * Sauvegarder la mise à jour du label
     */
    elseif ($action == 'save_label_update') {
        debug_log("=== ACTION: save_label_update ===");

        $commandedet_id = GETPOST('commandedet_id', 'int');
        $n_commande = GETPOST('n_commande', 'alpha');
        $date_commande = GETPOST('date_commande', 'alpha');
        $contact_id = GETPOST('contact', 'int');
        $ref_chantier = GETPOST('ref_chantier', 'alpha');

        debug_log("Commandedet ID: " . $commandedet_id);
        debug_log("N Commande: " . $n_commande);
        debug_log("Date Commande: " . $date_commande);
        debug_log("Contact ID: " . $contact_id);
        debug_log("Ref Chantier: " . $ref_chantier);

        if (!$commandedet_id) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Missing commandedet_id parameter'));
            exit;
        }

        // Vérifier permissions
        if (!$user->hasRight('commande', 'creer')) {
            http_response_code(403);
            echo json_encode(array('success' => false, 'error' => 'No write permission for orders'));
            exit;
        }

        // Récupérer le nom du contact si ID fourni
        $contact_name = '';
        if ($contact_id) {
            $sql = "SELECT firstname, lastname FROM ".MAIN_DB_PREFIX."socpeople";
            $sql .= " WHERE rowid = ".((int) $contact_id);

            $resql = $db->query($sql);
            if ($resql && $db->num_rows($resql) > 0) {
                $obj = $db->fetch_object($resql);
                $contact_name = trim(($obj->firstname ? $obj->firstname . ' ' : '') . $obj->lastname);
                debug_log("Contact name: " . $contact_name);
            }
        }

        // Construire le nouveau label selon le format demandé
        // Format: "Commande [N° Commande] du [Date Commande] de [Contact Commande] ref : [Ref Chantier]"
        $label_parts = array();

        if (!empty($n_commande)) {
            $label_parts[] = "Commande " . $n_commande;
        }

        if (!empty($date_commande)) {
            // Formater la date au format français (JJ/MM/AAAA)
            $date_obj = DateTime::createFromFormat('Y-m-d', $date_commande);
            if ($date_obj) {
                $label_parts[] = "du " . $date_obj->format('d/m/Y');
            }
        }

        if (!empty($contact_name)) {
            $label_parts[] = "de " . $contact_name;
        }

        if (!empty($ref_chantier)) {
            $label_parts[] = "ref : " . $ref_chantier;
        }

        $new_label = implode(' ', $label_parts);
        debug_log("Nouveau label: " . $new_label);

        if (empty($new_label)) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Label cannot be empty'));
            exit;
        }

        // Mettre à jour la description de la ligne de commande
        $sql = "UPDATE ".MAIN_DB_PREFIX."commandedet";
        $sql .= " SET description = '".$db->escape($new_label)."'";
        $sql .= " WHERE rowid = ".((int) $commandedet_id);

        debug_log("SQL update label: " . $sql);

        $resql = $db->query($sql);
        if (!$resql) {
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => 'Failed to update label: ' . $db->lasterror()));
            exit;
        }

        // Stocker ref_chantier dans l'extrafield
        if (!empty($ref_chantier)) {
            // Vérifier si l'entrée existe déjà
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commandedet_extrafields";
            $sql .= " WHERE fk_object = ".((int) $commandedet_id);

            $resql = $db->query($sql);
            $exists = ($resql && $db->num_rows($resql) > 0);

            if ($exists) {
                // Mise à jour
                $sql = "UPDATE ".MAIN_DB_PREFIX."commandedet_extrafields";
                $sql .= " SET ref_chantier = '".$db->escape($ref_chantier)."'";
                $sql .= " WHERE fk_object = ".((int) $commandedet_id);
            } else {
                // Insertion
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."commandedet_extrafields";
                $sql .= " (fk_object, ref_chantier)";
                $sql .= " VALUES (".((int) $commandedet_id).", '".$db->escape($ref_chantier)."')";
            }

            debug_log("SQL update extrafield: " . $sql);

            $resql = $db->query($sql);
            if (!$resql) {
                debug_log("WARNING: Failed to update extrafield: " . $db->lasterror());
                // Ne pas faire échouer toute la sauvegarde pour autant
            }
        }

        debug_log("Label sauvegardé avec succès");
        echo json_encode(array(
            'success' => true,
            'message' => 'Label updated successfully',
            'new_label' => $new_label
        ));
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

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode(array(
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ));
    exit;
}

debug_log("=== FIN LABEL HANDLER AJAX ===");
exit;
