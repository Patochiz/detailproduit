# 📝 CHANGELOG - Migration vers extrafields

## 🎯 Version 2.0 - Migration extrafields (Janvier 2025)

### 🔄 Changements majeurs

#### **Remplacement de la table par des extrafields**
- **AVANT** : Utilisation de la table `llx_commandedet_details`
- **APRÈS** : Utilisation des extrafields `detailjson` et `detail`

#### **Nouveaux extrafields requis**
1. **`detailjson`** (Text long, invisible)
   - Stockage JSON complet des données
   - Usage interne pour la logique métier
   
2. **`detail`** (HTML, visible)
   - Format d'affichage : "Nbr x longueur x largeur (quantité unité) description"
   - Exemples :
     - `20 x 3000 x 300 (1.80 m²) ABD`
     - `10 x 2000 (20.00 ml) Test linéaire`

---

## 📁 Fichiers modifiés

### ✏️ Fichiers principaux modifiés

#### **`class/commandedetdetails.class.php`** - Refactorisation complète
- ✅ Remplacé l'accès table par extrafields
- ✅ Nouvelle méthode `getDetailsForLine()` via JSON
- ✅ Nouvelle méthode `saveDetailsForLine()` avec double sauvegarde
- ✅ Génération automatique du format d'affichage
- ✅ Méthodes CRUD marquées comme dépréciées
- ✅ Gestion robuste des erreurs JSON

#### **`ajax/details_handler.php`** - Compatible sans modification
- ✅ Utilise déjà la classe `CommandeDetDetails`
- ✅ Aucune modification nécessaire
- ✅ Gestion transparente des extrafields

#### **`js/details_popup.js`** - Compatible sans modification
- ✅ Interface utilisateur inchangée
- ✅ Même format de données FormData
- ✅ Expérience utilisateur identique

---

## 📁 Nouveaux fichiers

### 🆕 Fichiers d'installation et test

#### **`create_extrafields.php`** - Création automatique
- 🔧 Création automatique des extrafields requis
- 🔍 Vérification des extrafields existants
- 📊 Interface web conviviale
- ⚠️ Sécurité : admin uniquement

#### **`test_extrafields.php`** - Tests et validation
- 🧪 Test de sauvegarde/récupération
- 📊 Vérification format JSON et HTML
- 🔍 Diagnostic des problèmes
- 📋 Liste des lignes de commande disponibles

#### **`MIGRATION_EXTRAFIELDS.md`** - Documentation complète
- 📖 Guide pas à pas
- 🛠️ Instructions de création manuelle
- 🔧 Dépannage et solutions
- 📊 Exemples de formats

#### **`migrate_to_extrafields.php`** - Migration optionnelle
- 🔄 Migration automatique des données existantes
- 📊 Analyse avant migration
- 🗑️ Suppression sécurisée de l'ancienne table
- ⚠️ Avec sauvegarde recommandée

---

## 🔧 Actions requises pour l'utilisateur

### 1️⃣ **Création des extrafields** (OBLIGATOIRE)

**Option A - Automatique (recommandée)**
```bash
https://votre-dolibarr.com/custom/detailproduit/create_extrafields.php
```

**Option B - Manuelle**
1. Administration → Modules → Extrafields
2. Sélectionner "Order lines"
3. Créer `detailjson` (Text long, invisible)
4. Créer `detail` (HTML, visible)

### 2️⃣ **Test de fonctionnement**
```bash
https://votre-dolibarr.com/custom/detailproduit/test_extrafields.php
```

### 3️⃣ **Migration des données** (si nécessaire)
```bash
https://votre-dolibarr.com/custom/detailproduit/migrate_to_extrafields.php
```

---

## 📊 Avantages de la migration

### ✅ **Conformité Dolibarr**
- Utilisation des standards du framework
- Intégration native avec l'interface
- Respect des bonnes pratiques

### ✅ **Maintenabilité**
- Plus de table séparée à gérer
- Moins de code de jointure SQL
- Structure plus simple

### ✅ **Performance**
- Moins de requêtes SQL
- Données centralisées
- Cache Dolibarr utilisable

### ✅ **Flexibilité**
- Format JSON extensible
- Ajout facile de nouveaux champs
- Évolution future simplifiée

### ✅ **Interface utilisateur**
- Affichage natif dans les listes
- Pas de modification de l'interface
- Experience utilisateur identique

---

## 🔮 Rétrocompatibilité

### ✅ **Interface utilisateur**
- Popup identique
- Même fonctionnalités
- Même raccourcis clavier
- Mêmes validations

### ✅ **Format des données**
- Structure interne inchangée
- Calculs identiques
- Unités conservées
- Descriptions préservées

### ❌ **Base de données**
- **BREAKING CHANGE** : Plus d'utilisation de `llx_commandedet_details`
- Migration requise pour les données existantes
- Nouveaux extrafields obligatoires

---

## 🛠️ Dépannage

### ❓ **Problèmes courants**

#### "Extrafields manquants"
**Solution** : Exécuter `create_extrafields.php` ou créer manuellement

#### "Erreur de sauvegarde JSON"
**Solution** : Vérifier les permissions et la structure des extrafields

#### "Format d'affichage incorrect"
**Solution** : Tester avec `test_extrafields.php` et contrôler les données

### 📋 **Checklist de vérification**
- [ ] Extrafields `detailjson` et `detail` créés
- [ ] Test de sauvegarde réussi
- [ ] Affichage correct dans les listes
- [ ] Popup fonctionnel sur les lignes de commande
- [ ] Migration des anciennes données (si applicable)

---

## 📞 Support

**En cas de problème :**
1. Consulter `MIGRATION_EXTRAFIELDS.md`
2. Utiliser `test_extrafields.php` pour diagnostiquer
3. Vérifier les logs Dolibarr
4. Contacter : pgourmelen@diamant-industrie.com

---

## 🏷️ Version

- **Version précédente** : 1.x (table séparée)
- **Version actuelle** : 2.0 (extrafields)
- **Date de migration** : Janvier 2025
- **Compatibilité** : Dolibarr 17.0+
