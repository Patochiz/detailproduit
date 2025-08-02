# 📋 Module Détails Produit pour Dolibarr

> **Version 1.0** - Module pour la gestion détaillée des dimensions produits dans les commandes clients

## 🎯 Objectif du module

Ce module permet de détailler les dimensions de produits dans les commandes clients Dolibarr. Il est particulièrement utile pour les entreprises qui vendent des matériaux dimensionnels (tubes, tôles, barres, etc.) où une quantité globale doit être détaillée en plusieurs pièces de dimensions différentes.

### Exemple d'utilisation
- **Commande client** : 5000mm de tube acier
- **Détail** : 
  - 2 pièces × 2000mm × 50mm = 0,200 m²
  - 1 pièce × 1000mm = 1,000 ml  
  - 5 pièces = 5,000 u

## ✨ Fonctionnalités principales

### 🖥️ Interface utilisateur
- **Modal popup** avec mini-tableur pour saisir les détails
- **Navigation optimisée** : Tab (horizontal) / Entrée (vertical)
- **Tri des colonnes** par clic sur les en-têtes
- **Calculs automatiques** selon les dimensions saisies
- **Export CSV** des détails
- **Interface responsive** (mobile/desktop)

### 🧮 Calculs automatiques
- **m² (mètres carrés)** : Longueur ET Largeur → `Nb pièces × (Longueur/1000) × (Largeur/1000)`
- **ml (mètres linéaires)** : Longueur OU Largeur → `Nb pièces × (Dimension/1000)`
- **u (unités)** : Aucune dimension → `Nb pièces`
- **Unité principale** déterminée automatiquement (plus grande valeur totale)

### 🔗 Intégration Dolibarr
- **Boutons automatiques** sur les lignes de commande (📋)
- **Résumé affiché** : "X pièces (Y.YYY m² + Z.ZZZ ml)"
- **Mise à jour des quantités** de commande depuis les détails
- **Permissions intégrées** au système Dolibarr
- **Sécurisation CSRF** et validation serveur

## 🛠️ Installation

### Prérequis
- **Dolibarr** 13.0 ou supérieur
- **PHP** 7.4 ou supérieur  
- **MySQL/MariaDB** 5.6+ / 10.0+
- **Module Commandes** activé dans Dolibarr

### Installation automatique
1. Copier le dossier `detailproduit` dans `/custom/` de votre installation Dolibarr
2. Aller dans **Configuration → Modules/Applications**
3. Chercher "Détails Produit" et cliquer sur **Activer**
4. Configurer les permissions utilisateur si nécessaire

### Vérification de l'installation
Accédez à : `https://votre-dolibarr.com/custom/detailproduit/test_installation.php`

Ce script vérifie :
- ✅ Activation du module
- ✅ Structure de base de données  
- ✅ Fichiers et permissions
- ✅ Fonctions de calcul

## 📖 Utilisation

### 1. Créer une commande client
Créez une commande client normale avec vos produits et quantités.

### 2. Accéder aux détails
Sur chaque ligne de produit, cliquez sur le bouton **📋 Détails**.

### 3. Saisir les détails
Dans le popup qui s'ouvre :
- **Nb pièces** : Quantité de pièces (obligatoire)
- **Longueur** : Dimension en mm (optionnel)
- **Largeur** : Dimension en mm (optionnel)  
- **Description** : Commentaire libre (optionnel)
- **Total** : Calculé automatiquement selon l'unité

### 4. Navigation rapide
- **Tab** → Cellule suivante (horizontal)
- **Entrée** → Ligne suivante, même colonne (vertical)
- **Nouvelle ligne** automatique en fin de saisie

### 5. Fonctionnalités avancées
- **Tri** : Clic sur les en-têtes de colonnes
- **Vider tout** : Effacer toutes les lignes
- **Mettre à jour quantité** : Synchroniser avec la ligne de commande
- **Export CSV** : Télécharger les détails

## 🗄️ Structure de données

### Table `llx_commandedet_details`
```sql
- rowid (PK) - ID unique
- fk_commandedet (FK) - Référence ligne de commande
- pieces (decimal) - Nombre de pièces  
- longueur (decimal) - Longueur en mm (nullable)
- largeur (decimal) - Largeur en mm (nullable)
- total_value (decimal) - Valeur calculée
- unit (varchar) - Unité : 'm²', 'ml', 'u'
- description (text) - Description libre
- rang (int) - Ordre d'affichage
- date_creation - Date de création
- tms - Timestamp de modification
```

### Index de performance
```sql
CREATE INDEX idx_commandedet_details_fk_commandedet 
ON llx_commandedet_details (fk_commandedet);
```

## 🔧 Configuration

### Permissions utilisateur
Le module utilise les permissions standard de Dolibarr :
- **Lecture** : `$user->hasRight('commande', 'lire')`
- **Écriture** : `$user->hasRight('commande', 'creer')`

### Configuration avancée
Accédez à **Configuration → Modules → Détails Produit → Configuration** pour :
- Paramètres d'affichage
- Options d'export  
- Intégrations avec d'autres modules

## 🔌 API et intégrations

### Utilisation programmatique
```php
// Instancier la classe
dol_include_once('/detailproduit/class/commandedetdetails.class.php');
$details_obj = new CommandeDetDetails($db);

// Récupérer les détails d'une ligne
$details = $details_obj->getDetailsForLine($commandedet_id);

// Calculer unité et valeur
$calc = CommandeDetDetails::calculateUnitAndValue($pieces, $longueur, $largeur);

// Obtenir un résumé pour affichage
$summary = $details_obj->getSummaryForDisplay($commandedet_id);
```

### Intégration avec d'autres modules

#### Module Production
```php
// Récupérer plan de découpe optimisé
$details = $details_obj->getDetailsForLine($commandedet_id);
foreach ($details as $detail) {
    // Optimiser les chutes, calculer gaspillage
    planifier_decoupe($detail['pieces'], $detail['longueur'], $detail['largeur']);
}
```

#### Module Expédition
```php  
// Générer étiquettes par pièce
foreach ($details as $detail) {
    generer_etiquette($detail['description'], $detail['longueur'], $detail['largeur']);
}
```

## 🧪 Tests et débogage

### Mode debug
Activez le mode debug dans `js/details_popup.js` :
```javascript
const DEBUG_MODE = true;
```

### Vérifications console
```javascript
// Variables globales
console.log('DOL_URL_ROOT:', DOL_URL_ROOT);
console.log('token:', token);

// État du module  
console.log('Module détails chargé:', typeof openDetailsModal !== 'undefined');
```

### Tests SQL
```sql
-- Vérifier les données
SELECT cd.rowid, cd.pieces, cd.longueur, cd.largeur, cd.total_value, cd.unit
FROM llx_commandedet_details cd
WHERE cd.fk_commandedet = 123;

-- Statistiques d'utilisation
SELECT unit, COUNT(*) as nb_lignes, SUM(total_value) as total
FROM llx_commandedet_details 
GROUP BY unit;
```

## 📝 Données d'exemple

### Cas d'usage industriel
```
Commande: 10 000mm de tôle acier
Détails:
├── 3 pièces × 2000mm × 1000mm = 6,000 m² (Panneaux A)
├── 2 pièces × 1500mm × 800mm  = 2,400 m² (Panneaux B)  
├── 5 pièces × 800mm           = 4,000 ml (Barres de renfort)
└── 10 pièces                  = 10,000 u (Fixations)

Total principal: 8,400 m²
Détail complet: 8,400 m² + 4,000 ml + 10,000 u
```

## 🚀 Évolutions futures

### Version 1.1 (prévue)
- [ ] Calcul automatique des chutes
- [ ] Templates de découpe prédéfinis
- [ ] Import/export Excel
- [ ] Graphiques de répartition

### Version 1.2 (prévue)  
- [ ] Optimisation de découpe
- [ ] Intégration module Stock
- [ ] API REST complète
- [ ] Module mobile dédié

## 📞 Support

### Documentation
- **Manuel utilisateur** : `/detailproduit/docs/`
- **Test installation** : `/detailproduit/test_installation.php`
- **Exemples d'API** : `/detailproduit/demo_usage.php`

### Débogage courant

#### Boutons "📋" n'apparaissent pas
1. Vérifier que le module est activé
2. Vider le cache navigateur (Ctrl+F5)
3. Vérifier les permissions utilisateur
4. Consulter la console JavaScript (F12)

#### Modal ne s'ouvre pas
1. Vérifier les variables globales dans la console :
   ```javascript
   console.log(DOL_URL_ROOT, token);
   ```
2. Contrôler les erreurs JavaScript (F12)
3. Tester l'URL AJAX manuellement

#### Calculs incorrects
1. Utiliser le test d'installation pour valider les fonctions
2. Vérifier les données en base avec les requêtes SQL
3. Contrôler les arrondis et conversions d'unités

## 📄 Licence

Copyright (C) 2025 Patrice GOURMELEN - DIAMANT INDUSTRIE

Ce programme est un logiciel libre sous licence GNU GPL v3+.
Voir le fichier `COPYING` pour plus de détails.

## 🏢 Crédits

**Développé par** : DIAMANT INDUSTRIE  
**Contact** : pgourmelen@diamant-industrie.com  
**Site web** : www.diamant-industrie.com

---

## 📋 Checklist de déploiement

- [ ] Module copié dans `/custom/detailproduit/`
- [ ] Module activé dans Dolibarr
- [ ] Test d'installation réussi (80%+ de réussite)
- [ ] Permissions utilisateur configurées
- [ ] Test sur une commande client
- [ ] Vérification des calculs
- [ ] Formation des utilisateurs

**Statut : ✅ PRÊT POUR LA PRODUCTION**