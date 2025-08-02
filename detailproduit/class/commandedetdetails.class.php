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
 * Class pour gérer les détails des lignes de commande
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
     * @var string Nom de la table SQL sans préfixe
     */
    public $table_element = 'commandedet_details';

    /**
     * @var int ID de l'objet
     */
    public $id;

    /**
     * @var array Champs de l'objet
     */
    public $fields = array(
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
        'fk_commandedet' => array('type'=>'integer:Commandedet:commande/class/commande.class.php', 'label'=>'CommandeLine', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1),
        'pieces' => array('type'=>'double(24,8)', 'label'=>'Pieces', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1),
        'longueur' => array('type'=>'double(24,8)', 'label'=>'Length', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>1),
        'largeur' => array('type'=>'double(24,8)', 'label'=>'Width', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'total_value' => array('type'=>'double(24,8)', 'label'=>'TotalValue', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1),
        'unit' => array('type'=>'varchar(10)', 'label'=>'Unit', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>1),
        'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>1),
        'rang' => array('type'=>'integer', 'label'=>'Position', 'enabled'=>'1', 'position'=>80, 'notnull'=>1, 'visible'=>1),
        'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>0),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>0),
    );

    public $rowid;
    public $fk_commandedet;
    public $pieces;
    public $longueur;
    public $largeur;
    public $total_value;
    public $unit;
    public $description;
    public $rang;
    public $date_creation;
    public $tms;

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
     * Create object into database
     *
     * @param  User $user      User qui crée
     * @param  bool $notrigger false=lancer les triggers après, true=désactiver les triggers
     * @return int             Return integer <0 si erreur, Id de l'objet créé si OK
     */
    public function create(User $user, $notrigger = false)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $error = 0;

        // Clean parameters
        if (isset($this->fk_commandedet)) {
            $this->fk_commandedet = (int) $this->fk_commandedet;
        }
        if (isset($this->pieces)) {
            $this->pieces = trim($this->pieces);
        }
        if (isset($this->longueur)) {
            $this->longueur = trim($this->longueur);
        }
        if (isset($this->largeur)) {
            $this->largeur = trim($this->largeur);
        }
        if (isset($this->total_value)) {
            $this->total_value = trim($this->total_value);
        }
        if (isset($this->unit)) {
            $this->unit = trim($this->unit);
        }
        if (isset($this->description)) {
            $this->description = trim($this->description);
        }
        if (isset($this->rang)) {
            $this->rang = (int) $this->rang;
        }

        // Insert request
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';
        $sql .= 'fk_commandedet,';
        $sql .= 'pieces,';
        $sql .= 'longueur,';
        $sql .= 'largeur,';
        $sql .= 'total_value,';
        $sql .= 'unit,';
        $sql .= 'description,';
        $sql .= 'rang,';
        $sql .= 'date_creation';
        $sql .= ') VALUES (';
        $sql .= ' '.((int) $this->fk_commandedet).',';
        $sql .= ' '.((float) $this->pieces).',';
        $sql .= ' '.($this->longueur !== null ? (float) $this->longueur : 'NULL').',';
        $sql .= ' '.($this->largeur !== null ? (float) $this->largeur : 'NULL').',';
        $sql .= ' '.((float) $this->total_value).',';
        $sql .= ' \''.$this->db->escape($this->unit).'\',';
        $sql .= ' '.($this->description ? '\''.$this->db->escape($this->description).'\'' : 'NULL').',';
        $sql .= ' '.((int) $this->rang).',';
        $sql .= ' \''.$this->db->idate(dol_now()).'\'';
        $sql .= ')';

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++; $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
        }

        if (!$error) {
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Load object in memory from the database
     *
     * @param int    $id   Id object
     * @param string $ref  Ref
     * @return int         Return integer <0 si erreur, 0 si non trouvé, >0 si OK
     */
    public function fetch($id, $ref = null)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.fk_commandedet,";
        $sql .= " t.pieces,";
        $sql .= " t.longueur,";
        $sql .= " t.largeur,";
        $sql .= " t.total_value,";
        $sql .= " t.unit,";
        $sql .= " t.description,";
        $sql .= " t.rang,";
        $sql .= " t.date_creation,";
        $sql .= " t.tms";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " WHERE t.rowid = ".((int) $id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            if ($numrows) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->fk_commandedet = $obj->fk_commandedet;
                $this->pieces = $obj->pieces;
                $this->longueur = $obj->longueur;
                $this->largeur = $obj->largeur;
                $this->total_value = $obj->total_value;
                $this->unit = $obj->unit;
                $this->description = $obj->description;
                $this->rang = $obj->rang;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->tms = $this->db->jdate($obj->tms);
            }
            $this->db->free($resql);

            if ($numrows) {
                return 1;
            } else {
                return 0;
            }
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param  User $user      User qui modifie
     * @param  bool $notrigger false=lancer les triggers après, true=désactiver les triggers
     * @return int             Return integer <0 si erreur, >0 si OK
     */
    public function update(User $user, $notrigger = false)
    {
        $error = 0;

        dol_syslog(__METHOD__, LOG_DEBUG);

        // Clean parameters
        if (isset($this->fk_commandedet)) {
            $this->fk_commandedet = (int) $this->fk_commandedet;
        }
        if (isset($this->pieces)) {
            $this->pieces = trim($this->pieces);
        }
        if (isset($this->longueur)) {
            $this->longueur = trim($this->longueur);
        }
        if (isset($this->largeur)) {
            $this->largeur = trim($this->largeur);
        }
        if (isset($this->total_value)) {
            $this->total_value = trim($this->total_value);
        }
        if (isset($this->unit)) {
            $this->unit = trim($this->unit);
        }
        if (isset($this->description)) {
            $this->description = trim($this->description);
        }
        if (isset($this->rang)) {
            $this->rang = (int) $this->rang;
        }

        // Update request
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET';
        $sql .= ' fk_commandedet='.((int) $this->fk_commandedet).',';
        $sql .= ' pieces='.((float) $this->pieces).',';
        $sql .= ' longueur='.($this->longueur !== null ? (float) $this->longueur : 'NULL').',';
        $sql .= ' largeur='.($this->largeur !== null ? (float) $this->largeur : 'NULL').',';
        $sql .= ' total_value='.((float) $this->total_value).',';
        $sql .= ' unit=\''.$this->db->escape($this->unit).'\',';
        $sql .= ' description='.($this->description ? '\''.$this->db->escape($this->description).'\'' : 'NULL').',';
        $sql .= ' rang='.((int) $this->rang);
        $sql .= ' WHERE rowid='.((int) $this->id);

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++; $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Delete object in database
     *
     * @param User $user       User qui supprime
     * @param bool $notrigger  false=lancer les triggers après, true=désactiver les triggers
     * @return int             Return integer <0 si erreur >0 si OK
     */
    public function delete(User $user, $notrigger = false)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $error = 0;

        $this->db->begin();

        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= ' WHERE rowid='.((int) $this->id);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++; $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Récupérer tous les détails pour une ligne de commande
     *
     * @param int $fk_commandedet ID de la ligne de commande
     * @return array|int Array des détails si OK, -1 si erreur
     */
    public function getDetailsForLine($fk_commandedet)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.fk_commandedet,";
        $sql .= " t.pieces,";
        $sql .= " t.longueur,";
        $sql .= " t.largeur,";
        $sql .= " t.total_value,";
        $sql .= " t.unit,";
        $sql .= " t.description,";
        $sql .= " t.rang,";
        $sql .= " t.date_creation,";
        $sql .= " t.tms";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " WHERE t.fk_commandedet = ".((int) $fk_commandedet);
        $sql .= " ORDER BY t.rang ASC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $details = array();
            while ($obj = $this->db->fetch_object($resql)) {
                $details[] = array(
                    'rowid' => $obj->rowid,
                    'fk_commandedet' => $obj->fk_commandedet,
                    'pieces' => $obj->pieces,
                    'longueur' => $obj->longueur,
                    'largeur' => $obj->largeur,
                    'total_value' => $obj->total_value,
                    'unit' => $obj->unit,
                    'description' => $obj->description,
                    'rang' => $obj->rang,
                    'date_creation' => $this->db->jdate($obj->date_creation),
                    'tms' => $this->db->jdate($obj->tms)
                );
            }
            $this->db->free($resql);
            return $details;
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Sauvegarder tous les détails pour une ligne de commande
     * Supprime les anciens détails et crée les nouveaux
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

        // Supprimer les anciens détails
        $result = $this->deleteDetailsForLine($fk_commandedet);
        if ($result < 0) {
            $this->db->rollback();
            return -1;
        }

        // Créer les nouveaux détails
        $rang = 1;
        foreach ($details_array as $detail) {
            $detail_obj = new CommandeDetDetails($this->db);
            $detail_obj->fk_commandedet = $fk_commandedet;
            $detail_obj->pieces = $detail['pieces'];
            $detail_obj->longueur = !empty($detail['longueur']) ? $detail['longueur'] : null;
            $detail_obj->largeur = !empty($detail['largeur']) ? $detail['largeur'] : null;
            $detail_obj->total_value = $detail['total_value'];
            $detail_obj->unit = $detail['unit'];
            $detail_obj->description = $detail['description'] ?? '';
            $detail_obj->rang = $rang++;

            $result = $detail_obj->create($user, true);
            if ($result < 0) {
                $this->errors = array_merge($this->errors, $detail_obj->errors);
                $this->db->rollback();
                return -1;
            }
        }

        $this->db->commit();
        return 1;
    }

    /**
     * Supprimer tous les détails pour une ligne de commande
     *
     * @param int $fk_commandedet ID de la ligne de commande
     * @return int                Return integer <0 si erreur, >0 si OK
     */
    public function deleteDetailsForLine($fk_commandedet)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= ' WHERE fk_commandedet = '.((int) $fk_commandedet);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     * Calculer les totaux par unité pour une ligne de commande
     *
     * @param int $fk_commandedet ID de la ligne de commande
     * @return array|int          Array des totaux par unité si OK, -1 si erreur
     */
    public function getTotalsByUnit($fk_commandedet)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = "SELECT";
        $sql .= " t.unit,";
        $sql .= " SUM(t.total_value) as total_value,";
        $sql .= " COUNT(*) as nb_lines";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " WHERE t.fk_commandedet = ".((int) $fk_commandedet);
        $sql .= " GROUP BY t.unit";
        $sql .= " ORDER BY total_value DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $totals = array();
            while ($obj = $this->db->fetch_object($resql)) {
                $totals[$obj->unit] = array(
                    'total_value' => $obj->total_value,
                    'nb_lines' => $obj->nb_lines
                );
            }
            $this->db->free($resql);
            return $totals;
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
            return -1;
        }
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

        // Récupération de la ligne de commande
        $sql = "UPDATE ".MAIN_DB_PREFIX."commandedet";
        $sql .= " SET qty = ".((float) $new_quantity);
        $sql .= " WHERE rowid = ".((int) $fk_commandedet);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
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
            $summary_parts[] = number_format($data['total_value'], 3, ',', ' ').' '.$unit;
        }

        $total_pieces = 0;
        $details = $this->getDetailsForLine($fk_commandedet);
        if ($details !== -1) {
            foreach ($details as $detail) {
                $total_pieces += $detail['pieces'];
            }
        }

        return "📋 ".number_format($total_pieces, 0, ',', ' ')." pièces (".implode(' + ', $summary_parts).")";
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
}
