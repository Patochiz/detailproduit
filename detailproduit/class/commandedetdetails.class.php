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
 * \file        class/commandedetdetails.class.php
 * \ingroup     detailproduit
 * \brief       This file is a CRUD class file for CommandeDetDetails (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class pour gérer les détails des lignes de commande via les extrafields
 * Version modifiée pour utiliser les extrafields au lieu d'une table séparée
 */
class CommandeDetDetails extends CommonObject
{
    /**
     * @var string ID du module
     */
    public $module = 'detailproduit';

    /**
     * @var string ID pour construire le nom de fichier
     */
    public $element = 'commandedetdetails';

    /**
     * @var int ID de l'objet
     */
    public $id;

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les détails pour une ligne de commande depuis l'extrafield detailjson
     *
     * @param int $fk_commandedet ID de la ligne de commande
     * @return array|int Array des détails si OK, -1 si erreur
     */
    public function getDetailsForLine($fk_commandedet)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        // Récupérer l'extrafield detailjson
        $sql = "SELECT detailjson FROM " . MAIN_DB_PREFIX . "commandedet_extrafields";
        $sql .= " WHERE fk_object = " . ((int) $fk_commandedet);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $details = array();
        
        if ($this->db->num_rows($resql)) {
            $obj = $this->db->fetch_object($resql);
            $json_data = $obj->detailjson;
            
            if (!empty($json_data)) {
                $decoded_details = json_decode($json_data, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_details)) {
                    // Convertir le format JSON en format attendu
                    foreach ($decoded_details as $index => $detail) {
                        $details[] = array(
                            'rowid' => $index + 1, // ID fictif pour compatibilité
                            'fk_commandedet' => $fk_commandedet,
                            'pieces' => $detail['pieces'] ?? 0,
                            'longueur' => $detail['longueur'] ?? null,
                            'largeur' => $detail['largeur'] ?? null,
                            'total_value' => $detail['total_value'] ?? 0,
                            'unit' => $detail['unit'] ?? 'u',
                            'description' => $detail['description'] ?? '',
                            'rang' => $index + 1,
                            'date_creation' => time(), // Timestamp fictif
                            'tms' => time()
                        );
                    }
                    
                    dol_syslog(__METHOD__ . ' - Détails récupérés depuis extrafield: ' . count($details) . ' lignes', LOG_DEBUG);
                } else {
                    dol_syslog(__METHOD__ . ' - Erreur décodage JSON ou format invalide', LOG_WARNING);
                }
            }
        }
        
        $this->db->free($resql);
        return $details;
    }

    /**
     * Sauvegarder tous les détails pour une ligne de commande dans les extrafields
     * Met à jour les extrafields "detailjson" et "detail"
     *
     * @param int   $fk_commandedet ID de la ligne de commande
     * @param array $details_array  Array des détails à sauvegarder
     * @param User  $user          User qui sauvegarde
     * @return int                 Return integer <0 si erreur, >0 si OK
     */
    public function saveDetailsForLine($fk_commandedet, $details_array, User $user)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $this->db->begin();

        // Nettoyer et préparer les données pour JSON
        $clean_details = array();
        foreach ($details_array as $detail) {
            $clean_details[] = array(
                'pieces' => (float) $detail['pieces'],
                'longueur' => !empty($detail['longueur']) ? (float) $detail['longueur'] : null,
                'largeur' => !empty($detail['largeur']) ? (float) $detail['largeur'] : null,
                'total_value' => (float) $detail['total_value'],
                'unit' => (string) $detail['unit'],
                'description' => (string) ($detail['description'] ?? '')
            );
        }

        // Encoder en JSON
        $json_data = json_encode($clean_details, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = 'Erreur encodage JSON: ' . json_last_error_msg();
            $this->db->rollback();
            return -1;
        }

        // Générer le format d'affichage pour l'extrafield "detail"
        $formatted_detail = $this->generateFormattedDetail($clean_details);

        // Mettre à jour ou insérer l'extrafield
        $result = $this->updateExtrafields($fk_commandedet, $json_data, $formatted_detail);
        if ($result < 0) {
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();
        
        dol_syslog(__METHOD__ . ' - Détails sauvegardés dans extrafields: ' . count($details_array) . ' lignes', LOG_DEBUG);
        return 1;
    }

    /**
     * Générer le format d'affichage pour l'extrafield "detail"
     * Format: "Nbr x longueur x largeur (quantité unité) description"
     *
     * @param array $details_array Array des détails nettoyés
     * @return string              Format HTML avec <br> pour les sauts de ligne
     */
    private function generateFormattedDetail($details_array)
    {
        $formatted_lines = array();
        
        foreach ($details_array as $detail) {
            $pieces = (int) $detail['pieces'];
            $longueur = !empty($detail['longueur']) ? (int) $detail['longueur'] : null;
            $largeur = !empty($detail['largeur']) ? (int) $detail['largeur'] : null;
            $total_value = number_format($detail['total_value'], 2, '.', '');
            $unit = $detail['unit'];
            $description = $detail['description'] ?? '';
            
            // Échapper les caractères HTML dans la description
            $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
            
            // Construire la ligne selon le format : "Nbr x longueur x largeur (quantité unité) description"
            $line_parts = array();
            $line_parts[] = $pieces;
            
            if ($longueur !== null) {
                $line_parts[] = $longueur;
            }
            
            if ($largeur !== null) {
                $line_parts[] = $largeur;
            }
            
            $formatted_line = implode(' x ', $line_parts);
            $formatted_line .= ' (' . $total_value . ' ' . $unit . ')';
            
            if (!empty($description)) {
                $formatted_line .= ' ' . $description;
            }
            
            $formatted_lines[] = $formatted_line;
        }
        
        // Joindre toutes les lignes avec des balises <br> pour le format HTML
        return implode('<br>', $formatted_lines);
    }

    /**
     * Mettre à jour les extrafields dans la base de données
     *
     * @param int    $fk_commandedet    ID de la ligne de commande
     * @param string $json_data         Données JSON pour detailjson
     * @param string $formatted_detail  Données formatées pour detail
     * @return int                      Return integer <0 si erreur, >0 si OK
     */
    private function updateExtrafields($fk_commandedet, $json_data, $formatted_detail)
    {
        // Vérifier si l'enregistrement existe
        $sql = "SELECT fk_object FROM " . MAIN_DB_PREFIX . "commandedet_extrafields";
        $sql .= " WHERE fk_object = " . ((int) $fk_commandedet);
        
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error checking extrafields: ' . $this->db->lasterror();
            return -1;
        }
        
        $exists = ($this->db->num_rows($resql) > 0);
        $this->db->free($resql);
        
        if ($exists) {
            // Mettre à jour l'enregistrement existant
            $sql = "UPDATE " . MAIN_DB_PREFIX . "commandedet_extrafields";
            $sql .= " SET detailjson = '" . $this->db->escape($json_data) . "'";
            $sql .= ", detail = '" . $this->db->escape($formatted_detail) . "'";
            $sql .= " WHERE fk_object = " . ((int) $fk_commandedet);
        } else {
            // Insérer un nouvel enregistrement
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "commandedet_extrafields";
            $sql .= " (fk_object, detailjson, detail) VALUES";
            $sql .= " (" . ((int) $fk_commandedet) . ", '" . $this->db->escape($json_data) . "', '" . $this->db->escape($formatted_detail) . "')";
        }
        
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error updating extrafields: ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . $this->db->lasterror(), LOG_ERR);
            return -1;
        }
        
        return 1;
    }

    /**
     * Supprimer tous les détails pour une ligne de commande
     * Efface les extrafields "detailjson" et "detail"
     *
     * @param int $fk_commandedet ID de la ligne de commande
     * @return int                Return integer <0 si erreur, >0 si OK
     */
    public function deleteDetailsForLine($fk_commandedet)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = "UPDATE " . MAIN_DB_PREFIX . "commandedet_extrafields";
        $sql .= " SET detailjson = NULL, detail = NULL";
        $sql .= " WHERE fk_object = " . ((int) $fk_commandedet);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error clearing extrafields: ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     * Calculer les totaux par unité pour une ligne de commande
     * Basé sur les données extrafield
     *
     * @param int $fk_commandedet ID de la ligne de commande
     * @return array|int          Array des totaux par unité si OK, -1 si erreur
     */
    public function getTotalsByUnit($fk_commandedet)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $details = $this->getDetailsForLine($fk_commandedet);
        if ($details === -1) {
            return -1;
        }

        $totals = array();
        foreach ($details as $detail) {
            $unit = $detail['unit'];
            $value = (float) $detail['total_value'];
            
            if (!isset($totals[$unit])) {
                $totals[$unit] = array(
                    'total_value' => 0,
                    'nb_lines' => 0
                );
            }
            
            $totals[$unit]['total_value'] += $value;
            $totals[$unit]['nb_lines']++;
        }

        // Trier par valeur totale décroissante
        uasort($totals, function($a, $b) {
            return $b['total_value'] <=> $a['total_value'];
        });

        return $totals;
    }

    /**
     * Mettre à jour la quantité d'une ligne de commande avec le total calculé
     *
     * @param int    $fk_commandedet ID de la ligne de commande
     * @param float  $new_quantity   Nouvelle quantité
     * @param string $unit          Unité principale
     * @return int                  Return integer <0 si erreur, >0 si OK
     */
    public function updateCommandLineQuantity($fk_commandedet, $new_quantity, $unit)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = "UPDATE " . MAIN_DB_PREFIX . "commandedet";
        $sql .= " SET qty = " . ((float) $new_quantity);
        $sql .= " WHERE rowid = " . ((int) $fk_commandedet);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     * Obtenir un résumé des détails pour l'affichage
     *
     * @param int $fk_commandedet ID de la ligne de commande
     * @return string|int         String du résumé si OK, -1 si erreur
     */
    public function getSummaryForDisplay($fk_commandedet)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $totals = $this->getTotalsByUnit($fk_commandedet);
        if ($totals === -1) {
            return -1;
        }

        if (empty($totals)) {
            return '';
        }

        $summary_parts = array();
        foreach ($totals as $unit => $data) {
            $summary_parts[] = number_format($data['total_value'], 3, ',', ' ') . ' ' . $unit;
        }

        $total_pieces = 0;
        $details = $this->getDetailsForLine($fk_commandedet);
        if ($details !== -1) {
            foreach ($details as $detail) {
                $total_pieces += $detail['pieces'];
            }
        }

        return "📋 " . number_format($total_pieces, 0, ',', ' ') . " pièces (" . implode(' + ', $summary_parts) . ")";
    }

    /**
     * Calculer l'unité et la valeur selon les dimensions
     *
     * @param float $pieces   Nombre de pièces
     * @param float $longueur Longueur en mm (peut être null)
     * @param float $largeur  Largeur en mm (peut être null)
     * @return array          Array avec 'unit' et 'total_value'
     */
    public static function calculateUnitAndValue($pieces, $longueur, $largeur)
    {
        $pieces = (float) $pieces;
        $longueur = !empty($longueur) ? (float) $longueur : 0;
        $largeur = !empty($largeur) ? (float) $largeur : 0;

        if ($longueur > 0 && $largeur > 0) {
            // m² = Nb pièces × (Longueur/1000) × (Largeur/1000)
            $total_value = $pieces * ($longueur / 1000) * ($largeur / 1000);
            $unit = 'm²';
        } elseif ($longueur > 0 && $largeur == 0) {
            // ml = Nb pièces × (Longueur/1000)
            $total_value = $pieces * ($longueur / 1000);
            $unit = 'ml';
        } elseif ($longueur == 0 && $largeur > 0) {
            // ml = Nb pièces × (Largeur/1000)
            $total_value = $pieces * ($largeur / 1000);
            $unit = 'ml';
        } else {
            // u = Nb pièces
            $total_value = $pieces;
            $unit = 'u';
        }

        return array(
            'unit' => $unit,
            'total_value' => $total_value
        );
    }

    /**
     * Nettoyer les données orphelines - Version extrafields
     * Nettoie les extrafields qui n'ont plus de ligne de commande correspondante
     *
     * @return array Array avec les statistiques de nettoyage
     */
    public function cleanupOrphanedData()
    {
        dol_syslog(__METHOD__, LOG_DEBUG);
        
        $stats = array(
            'orphaned_extrafields_found' => 0,
            'orphaned_extrafields_cleaned' => 0,
            'errors' => array()
        );
        
        // Nettoyer les extrafields orphelins
        $sql = "SELECT ef.fk_object";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet_extrafields ef";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet cd ON cd.rowid = ef.fk_object";
        $sql .= " WHERE cd.rowid IS NULL AND (ef.detailjson IS NOT NULL OR ef.detail IS NOT NULL)";
        
        $resql = $this->db->query($sql);
        if ($resql) {
            $orphaned_extrafields = array();
            while ($obj = $this->db->fetch_object($resql)) {
                $orphaned_extrafields[] = $obj->fk_object;
            }
            $this->db->free($resql);
            
            $stats['orphaned_extrafields_found'] = count($orphaned_extrafields);
            
            // Nettoyer les extrafields orphelins
            if (count($orphaned_extrafields) > 0) {
                $sql_clean = "UPDATE " . MAIN_DB_PREFIX . "commandedet_extrafields";
                $sql_clean .= " SET detailjson = NULL, detail = NULL";
                $sql_clean .= " WHERE fk_object IN (" . implode(',', $orphaned_extrafields) . ")";
                
                $resql_clean = $this->db->query($sql_clean);
                if ($resql_clean) {
                    $stats['orphaned_extrafields_cleaned'] = $this->db->affected_rows($resql_clean);
                    dol_syslog(__METHOD__ . ' - ' . $stats['orphaned_extrafields_cleaned'] . ' extrafields orphelins nettoyés', LOG_INFO);
                } else {
                    $stats['errors'][] = 'Erreur nettoyage extrafields orphelins: ' . $this->db->lasterror();
                }
            }
        } else {
            $stats['errors'][] = 'Erreur recherche extrafields orphelins: ' . $this->db->lasterror();
        }
        
        return $stats;
    }

    /**
     * Vérifier l'intégrité des données extrafields
     *
     * @return array Array avec les statistiques sans modification
     */
    public function checkDataIntegrity()
    {
        dol_syslog(__METHOD__, LOG_DEBUG);
        
        $report = array(
            'total_extrafields_with_detailjson' => 0,
            'total_extrafields_with_detail' => 0,
            'orphaned_extrafields' => array(),
            'invalid_json_extrafields' => array(),
            'integrity_ok' => true
        );
        
        // Compter les extrafields avec detailjson
        $sql = "SELECT COUNT(*) as total FROM " . MAIN_DB_PREFIX . "commandedet_extrafields WHERE detailjson IS NOT NULL";
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $report['total_extrafields_with_detailjson'] = $obj->total;
            $this->db->free($resql);
        }
        
        // Compter les extrafields avec detail
        $sql = "SELECT COUNT(*) as total FROM " . MAIN_DB_PREFIX . "commandedet_extrafields WHERE detail IS NOT NULL";
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $report['total_extrafields_with_detail'] = $obj->total;
            $this->db->free($resql);
        }
        
        // Chercher les extrafields orphelins
        $sql = "SELECT ef.fk_object";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet_extrafields ef";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet cd ON cd.rowid = ef.fk_object";
        $sql .= " WHERE cd.rowid IS NULL AND (ef.detailjson IS NOT NULL OR ef.detail IS NOT NULL)";
        
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $report['orphaned_extrafields'][] = $obj->fk_object;
            }
            $this->db->free($resql);
        }
        
        // Vérifier les JSON invalides
        $sql = "SELECT fk_object, detailjson FROM " . MAIN_DB_PREFIX . "commandedet_extrafields";
        $sql .= " WHERE detailjson IS NOT NULL";
        
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                json_decode($obj->detailjson);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $report['invalid_json_extrafields'][] = array(
                        'fk_object' => $obj->fk_object,
                        'json_error' => json_last_error_msg()
                    );
                }
            }
            $this->db->free($resql);
        }
        
        // Déterminer si l'intégrité est OK
        $report['integrity_ok'] = (
            count($report['orphaned_extrafields']) == 0 && 
            count($report['invalid_json_extrafields']) == 0
        );
        
        return $report;
    }

    // =========================================================================
    // MÉTHODES OBSOLÈTES (maintenues pour compatibilité mais non utilisées)
    // =========================================================================

    /**
     * @deprecated Cette classe n'utilise plus de table séparée
     */
    public function create(User $user, $notrigger = false)
    {
        dol_syslog(__METHOD__ . ' - DEPRECATED: Cette méthode n\'est plus utilisée', LOG_WARNING);
        return -1;
    }

    /**
     * @deprecated Cette classe n'utilise plus de table séparée
     */
    public function fetch($id, $ref = null)
    {
        dol_syslog(__METHOD__ . ' - DEPRECATED: Cette méthode n\'est plus utilisée', LOG_WARNING);
        return -1;
    }

    /**
     * @deprecated Cette classe n'utilise plus de table séparée
     */
    public function update(User $user, $notrigger = false)
    {
        dol_syslog(__METHOD__ . ' - DEPRECATED: Cette méthode n\'est plus utilisée', LOG_WARNING);
        return -1;
    }

    /**
     * @deprecated Cette classe n'utilise plus de table séparée
     */
    public function delete(User $user, $notrigger = false)
    {
        dol_syslog(__METHOD__ . ' - DEPRECATED: Cette méthode n\'est plus utilisée', LOG_WARNING);
        return -1;
    }
}
