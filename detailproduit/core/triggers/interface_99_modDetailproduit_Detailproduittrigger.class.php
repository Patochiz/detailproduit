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
 * \file        core/triggers/interface_99_modDetailproduit_Detailproduittrigger.class.php
 * \ingroup     detailproduit
 * \brief       Trigger file for DetailProduit module - Cleanup orphaned data
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 * Class InterfaceDetailproduitTrigger
 */
class InterfaceDetailproduitTrigger extends DolibarrTriggers
{
    /**
     * @var string Nom du module
     */
    public $name = 'InterfaceDetailproduitTrigger';

    /**
     * @var string Description du trigger
     */
    public $description = 'Trigger pour nettoyer automatiquement les données détailproduit orphelines';

    /**
     * @var string Version du trigger
     */
    public $version = '1.0.0';

    /**
     * @var string Picto du trigger
     */
    public $picto = 'technic';

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
     * Function called when a Dolibarr business event is done.
     *
     * @param string $action Event action code
     * @param CommonObject $object Object concerned
     * @param User $user Object user
     * @param Translate $langs Object langs
     * @param Conf $conf Object conf
     * @return int <0 if KO, 0 if no triggered action, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (!isModEnabled('detailproduit')) {
            return 0;
        }

        dol_syslog(get_class($this)."::runTrigger action=".$action, LOG_DEBUG);

        switch ($action) {
            // Trigger déclenché AVANT la suppression d'une ligne de commande
            case 'LINEORDER_DELETE':
            case 'ORDERLINE_DELETE':
                if (is_object($object) && isset($object->rowid)) {
                    $this->cleanupDetailProduitData($object->rowid);
                }
                break;

            // Trigger déclenché AVANT la suppression d'une commande entière
            case 'ORDER_DELETE':
                if (is_object($object) && isset($object->id)) {
                    $this->cleanupDetailProduitDataForOrder($object->id);
                }
                break;

            default:
                // Pas d'action pour les autres triggers
                break;
        }

        return 0;
    }

    /**
     * Nettoyer les données detailproduit pour une ligne de commande supprimée
     *
     * @param int $commandedet_id ID de la ligne de commande
     * @return int >0 if OK, <0 if KO
     */
    private function cleanupDetailProduitData($commandedet_id)
    {
        dol_syslog(get_class($this)."::cleanupDetailProduitData commandedet_id=".$commandedet_id, LOG_DEBUG);

        $error = 0;

        // Supprimer les détails de la table llx_commandedet_details
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet_details";
        $sql .= " WHERE fk_commandedet = ".((int) $commandedet_id);

        $resql = $this->db->query($sql);
        if (!$resql) {
            dol_syslog(get_class($this)."::cleanupDetailProduitData Erreur suppression détails: ".$this->db->lasterror(), LOG_ERR);
            $error++;
        } else {
            $nb_deleted = $this->db->affected_rows($resql);
            if ($nb_deleted > 0) {
                dol_syslog(get_class($this)."::cleanupDetailProduitData ".$nb_deleted." détails supprimés pour ligne ".$commandedet_id, LOG_INFO);
            }
        }

        // Nettoyer l'extrafield detail
        $sql = "UPDATE ".MAIN_DB_PREFIX."commandedet_extrafields";
        $sql .= " SET detail = NULL";
        $sql .= " WHERE fk_object = ".((int) $commandedet_id);

        $resql = $this->db->query($sql);
        if (!$resql) {
            dol_syslog(get_class($this)."::cleanupDetailProduitData Erreur nettoyage extrafield: ".$this->db->lasterror(), LOG_ERR);
            $error++;
        } else {
            dol_syslog(get_class($this)."::cleanupDetailProduitData Extrafield 'detail' nettoyé pour ligne ".$commandedet_id, LOG_INFO);
        }

        return ($error ? -1 : 1);
    }

    /**
     * Nettoyer les données detailproduit pour toutes les lignes d'une commande supprimée
     *
     * @param int $order_id ID de la commande
     * @return int >0 if OK, <0 if KO
     */
    private function cleanupDetailProduitDataForOrder($order_id)
    {
        dol_syslog(get_class($this)."::cleanupDetailProduitDataForOrder order_id=".$order_id, LOG_DEBUG);

        $error = 0;

        // Récupérer toutes les lignes de la commande qui vont être supprimées
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commandedet";
        $sql .= " WHERE fk_commande = ".((int) $order_id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $commandedet_ids = array();
            while ($obj = $this->db->fetch_object($resql)) {
                $commandedet_ids[] = $obj->rowid;
            }
            $this->db->free($resql);

            // Nettoyer les données pour chaque ligne
            foreach ($commandedet_ids as $commandedet_id) {
                $result = $this->cleanupDetailProduitData($commandedet_id);
                if ($result < 0) {
                    $error++;
                }
            }

            if (count($commandedet_ids) > 0) {
                dol_syslog(get_class($this)."::cleanupDetailProduitDataForOrder Nettoyage effectué pour ".count($commandedet_ids)." lignes de la commande ".$order_id, LOG_INFO);
            }
        } else {
            dol_syslog(get_class($this)."::cleanupDetailProduitDataForOrder Erreur récupération lignes: ".$this->db->lasterror(), LOG_ERR);
            $error++;
        }

        return ($error ? -1 : 1);
    }
}