# 📦 Module DetailProduit - Version 2.0

## 🎯 Description

Module Dolibarr pour la gestion détaillée des dimensions et quantités des produits dans les lignes de commande. 

Permet de saisir des détails précis (nombre de pièces, dimensions, descriptions) et calcule automatiquement les quantités totales en m², ml ou unités.

## ✨ Fonctionnalités

- 📋 **Popup de saisie** : Interface intuitive pour entrer les détails
- 🧮 **Calcul automatique** : Conversion dimensions → quantités (m², ml, u)
- 🔄 **Synchronisation** : Mise à jour automatique des lignes de commande
- 📊 **Affichage intégré** : Résumé visible dans les listes Dolibarr
- 🎯 **Navigation clavier** : Tab et Entrée pour la productivité
- 📤 **Export CSV** : Export des détails pour traitement externe

## 🆕 Nouveautés Version 2.0

### 🔄 Migration vers extrafields
- **BREAKING CHANGE** : Plus d'utilisation de table séparée
- Utilisation des extrafields standard Dolibarr
- Format d'affichage optimisé : "Nbr x longueur x largeur (quantité unité) description"
- Stockage JSON pour flexibilité future

### ✅ Avantages
- ✅ Conformité aux standards Dolibarr
- ✅ Intégration native dans l'interface
- ✅ Performances améliorées
- ✅ Maintenance simplifiée

---

## 🛠️ Installation

### 1️⃣ **Installation du module**
```bash
# Copier le dossier dans custom/
cp -r detailproduit /path/to/dolibarr/htdocs/custom/

# Activer le module
Administration → Modules → Rechercher "DetailProduit" → Activer
```

### 2️⃣ **Création des extrafields** (OBLIGATOIRE)

**Option A - Automatique (recommandée)**
```bash
# Accéder au script via navigateur
https://votre-dolibarr.com/custom/detailproduit/create_extrafields.php
```

**Option B - Manuelle**
1. Administration → Modules → Extrafields
2. Sélectionner "Order lines" (Lignes de commande)
3. Créer les extrafields :

| Code | Libellé | Type | Visible | Description |
|------|---------|------|---------|-------------|
| `detailjson` | Détails JSON | Text long | Non | Stockage JSON des données |
| `detail` | Détails produit | HTML | Oui | Affichage formaté |

### 3️⃣ **Test d'installation**
```bash
# Vérifier que tout fonctionne
https://votre-dolibarr.com/custom/detailproduit/test_extrafields.php
```

### 4️⃣ **Migration des données** (si applicable)
Si vous avez des données existantes :
```bash
# Script de migration optionnel
https://votre-dolibarr.com/custom/detailproduit/migrate_to_extrafields.php
```

---

## 🎮 Utilisation

### 📋 Saisie des détails
1. Ouvrir une commande
2. Cliquer sur le bouton **📋** à côté d'une ligne
3. Saisir les détails :
   - **Pièces** : Nombre d'éléments
   - **Longueur** : Dimension en mm (optionnel)
   - **Largeur** : Dimension en mm (optionnel)  
   - **Description** : Texte libre
4. Cliquer **💾 Sauvegarder**

### 🧮 Calculs automatiques
- **m²** : Pièces × (Longueur ÷ 1000) × (Largeur ÷ 1000)
- **ml** : Pièces × (Longueur ÷ 1000) OU Pièces × (Largeur ÷ 1000)
- **u** : Nombre de pièces seulement

### 📊 Exemples d'affichage
```
20 x 3000 x 300 (1.80 m²) Panneau ABD
10 x 2000 (20.00 ml) Profil DEF
5 (5.00 u) Accessoire XYZ
```

---

## 🔧 Configuration

### 📂 Structure des fichiers
```
detailproduit/
├── class/                    # Classes PHP
├── ajax/                     # Handlers AJAX
├── js/                       # JavaScript
├── css/                      # Styles
├── core/                     # Hooks et modules
├── langs/                    # Traductions
├── create_extrafields.php    # Création automatique extrafields
├── test_extrafields.php      # Tests et validation
└── migrate_to_extrafields.php # Migration optionnelle
```

### ⚙️ Paramètres
- **Permissions** : Basées sur les droits commandes Dolibarr
- **Contextes** : Pages de commandes, factures, propositions
- **Token CSRF** : Sécurité automatique

---

## 🔧 Dépannage

### ❌ Problèmes courants

#### "Extrafields manquants"
```bash
# Solution : Créer les extrafields
https://votre-dolibarr.com/custom/detailproduit/create_extrafields.php
```

#### "Bouton détails invisible"
- Vérifier que le module est activé
- Contrôler les permissions utilisateur
- Effacer le cache navigateur

#### "Erreur de sauvegarde"
- Vérifier les extrafields dans l'administration
- Consulter les logs Dolibarr
- Tester avec `test_extrafields.php`

### 📋 Checklist diagnostic
- [ ] Module activé
- [ ] Extrafields `detailjson` et `detail` créés
- [ ] Permissions utilisateur OK
- [ ] Test de sauvegarde réussi
- [ ] Affichage dans les listes

---

## 📚 Documentation

### 📖 Fichiers de documentation
- `MIGRATION_EXTRAFIELDS.md` : Guide de migration détaillé
- `CHANGELOG_EXTRAFIELDS.md` : Historique des modifications
- Scripts de test et diagnostic inclus

### 🔍 Scripts utilitaires
- `create_extrafields.php` : Création automatique des extrafields
- `test_extrafields.php` : Tests et validation
- `migrate_to_extrafields.php` : Migration depuis l'ancienne version

---

## 🆘 Support

### 📞 Contact
- **Email** : pgourmelen@diamant-industrie.com
- **Société** : DIAMANT INDUSTRIE

### 🔗 Ressources
- [Documentation Dolibarr](https://dolibarr.org)
- [Guide des extrafields](https://wiki.dolibarr.org/index.php/Extrafields)

---

## 📋 Informations techniques

### 🏷️ Version
- **Version actuelle** : 2.0
- **Compatibilité** : Dolibarr 17.0+
- **PHP** : 7.1+ requis

### 🔧 Technologies
- **Backend** : PHP, MySQL
- **Frontend** : JavaScript, CSS
- **Format de données** : JSON + HTML

### 📊 Licence
GNU General Public License v3.0

---

## 🚀 Évolutions futures

### 🔮 Roadmap
- 📸 Support des photos de pièces
- 📦 Codes-barres intégrés
- 🏷️ Système de tags
- 📊 Rapports avancés
- 🔄 API REST

Le format JSON des extrafields permet d'ajouter facilement de nouveaux champs sans migration complexe.
