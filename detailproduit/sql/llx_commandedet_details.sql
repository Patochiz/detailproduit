-- Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

--
-- Table structure for table llx_commandedet_details
-- Table pour stocker les détails de dimensions des lignes de commande
--

CREATE TABLE llx_commandedet_details (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  fk_commandedet int(11) NOT NULL,
  pieces decimal(24,8) NOT NULL DEFAULT 0,
  longueur decimal(24,8) DEFAULT NULL COMMENT 'Longueur en mm',
  largeur decimal(24,8) DEFAULT NULL COMMENT 'Largeur en mm',
  total_value decimal(24,8) NOT NULL DEFAULT 0 COMMENT 'Valeur calculée selon les dimensions',
  unit varchar(10) NOT NULL DEFAULT 'u' COMMENT 'Unité calculée: m², ml, u',
  description text,
  rang int(11) NOT NULL DEFAULT 0 COMMENT 'Ordre d\'affichage',
  date_creation datetime NOT NULL,
  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;
