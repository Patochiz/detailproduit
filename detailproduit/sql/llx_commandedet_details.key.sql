-- Index pour optimiser les performances de la table llx_commandedet_details
-- Copyright (C) 2025 Patrice GOURMELEN <pgourmelen@diamant-industrie.com>

-- Index principal sur fk_commandedet (recherche par ligne de commande)
ALTER TABLE llx_commandedet_details ADD INDEX idx_commandedet_details_fk_commandedet (fk_commandedet);

-- Index sur rang pour l'ordre d'affichage
ALTER TABLE llx_commandedet_details ADD INDEX idx_commandedet_details_rang (fk_commandedet, rang);

-- Index sur unit pour les regroupements par unité
ALTER TABLE llx_commandedet_details ADD INDEX idx_commandedet_details_unit (fk_commandedet, unit);

-- Index sur date_creation pour les tris temporels
ALTER TABLE llx_commandedet_details ADD INDEX idx_commandedet_details_date_creation (date_creation);

-- Clé étrangère vers llx_commandedet (si contraintes activées)
-- ALTER TABLE llx_commandedet_details ADD CONSTRAINT fk_commandedet_details_commandedet 
-- FOREIGN KEY (fk_commandedet) REFERENCES llx_commandedet(rowid) ON DELETE CASCADE;
