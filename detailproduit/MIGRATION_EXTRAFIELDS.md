# 🔄 Migration vers les extrafields - Module DetailProduit

## 📋 Vue d'ensemble

Le module **DetailProduit** a été modifié pour utiliser les **extrafields** au lieu d'une table séparée `llx_commandedet_details`. Cette approche est plus conforme aux standards Dolibarr et plus maintenable.

### 🎯 Objectifs de la migration

1. **Extrafield `detail`** : Format d'affichage lisible "Nbr x longueur x largeur (quantité unité) description"
2. **Extrafield `detailjson`** : Stockage complet des données au format JSON
3. **Suppression** de la dépendance à la table `llx_commandedet_details`

---

## 🛠️ Étapes de migration

### 1️⃣ Créer les extrafields

Vous devez créer deux extrafields dans l'interface d'administration de Dolibarr :

#### **Extrafield `detailjson`**
- **Nom** : `detailjson`
- **Libellé** : `Détails JSON`
- **Type** : `Text long`
- **Élément** : `OrderLine` (Ligne de commande)
- **Description** : `Données JSON complètes des détails produit`
- **Visible** : Non (utilisation interne uniquement)

#### **Extrafield `detail`**
- **Nom** : `detail`
- **Libellé** : `Détails produit`
- **Type** : `HTML`
- **Élément** : `OrderLine` (Ligne de commande)
- **Description** : `Affichage formaté des détails produit`
- **Visible** : Oui (dans les listes et fiches)

### 2️⃣ Navigation dans l'interface Dolibarr

```
Administration → Modules/Applications → Modules
→ Chercher "Extrafields"
→ Cliquer sur "Configuration"
→ Sélectionner "Order lines" (Lignes de commande)
→ Cliquer "New attribute"
```

### 3️⃣ Configuration détaillée

#### Pour `detailjson` :
```
Code: detailjson
Label: Détails JSON
Type: Text long
Elementtype: commandedet
Size: 
CSS: 
Default value: 
Visible on list: No
Visible on form: No
Required: No
Always editable: No
```

#### Pour `detail` :
```
Code: detail
Label: Détails produit
Type: HTML
Elementtype: commandedet
Size: 
CSS: 
Default value: 
Visible on list: Yes
Visible on form: Yes
Required: No
Always editable: No
```

---

## 🔍 Vérification de l'installation

### Script de test inclus

Utilisez le script `test_extrafields.php` pour vérifier la configuration :

```bash
# Accéder au script via navigateur
https://votre-dolibarr.com/custom/detailproduit/test_extrafields.php
```

### Vérifications manuelles

1. **Table extrafields** :
```sql
SHOW COLUMNS FROM llx_commandedet_extrafields;
-- Doit contenir : fk_object, detailjson, detail
```

2. **Test de sauvegarde** :
   - Utiliser le popup détails sur une ligne de commande
   - Vérifier que les données sont sauvegardées dans les extrafields
   - Contrôler le format d'affichage

---

## 📊 Format des données

### Format JSON (`detailjson`)
```json
[
  {
    "pieces": 20,
    "longueur": 3000,
    "largeur": 300,
    "total_value": 1.8,
    "unit": "m²",
    "description": "Test ABD"
  },
  {
    "pieces": 10,
    "longueur": 2000,
    "largeur": null,
    "total_value": 20,
    "unit": "ml",
    "description": "Test linéaire"
  }
]
```

### Format d'affichage (`detail`)
```html
20 x 3000 x 300 (1.80 m²) Test ABD<br>
10 x 2000 (20.00 ml) Test linéaire
```

---

## ⚠️ Migration des données existantes

Si vous avez des données dans l'ancienne table `llx_commandedet_details` :

### Option 1 : Script de migration (inclus)
```bash
# Accéder au script
https://votre-dolibarr.com/custom/detailproduit/migrate_to_extrafields.php
```

### Option 2 : Migration manuelle (pour peu de données)
1. Noter les détails existants
2. Supprimer les anciennes données
3. Re-saisir via le nouveau popup

### Option 3 : Migration SQL directe
```sql
-- Exemple pour une ligne spécifique
UPDATE llx_commandedet_extrafields 
SET detailjson = '[{"pieces":20,"longueur":3000,"largeur":300,"total_value":1.8,"unit":"m²","description":"Test"}]',
    detail = '20 x 3000 x 300 (1.80 m²) Test'
WHERE fk_object = 123;
```

---

## 🔧 Dépannage

### Problèmes courants

#### ❌ "Colonnes manquantes"
**Solution** : Créer les extrafields manquants via l'interface Dolibarr

#### ❌ "Erreur de sauvegarde"
**Solution** : Vérifier les permissions et la structure de la table extrafields

#### ❌ "Format JSON invalide"
**Solution** : Contrôler que les données ne contiennent pas de caractères spéciaux non échappés

### Logs de débogage

Activer les logs Dolibarr pour suivre les opérations :
```php
// Dans conf.php
$dolibarr_main_prod = 0;  // Mode debug
```

Consulter les logs :
```bash
tail -f /path/to/dolibarr/documents/dolibarr.log
```

---

## 📈 Avantages de la migration

1. **Conformité Dolibarr** : Utilisation des standards du framework
2. **Maintenabilité** : Plus de table séparée à gérer
3. **Performance** : Moins de jointures SQL
4. **Flexibilité** : Format JSON extensible
5. **Interface** : Intégration native avec l'interface Dolibarr

---

## 🔮 Évolutions futures

Le format JSON permet d'ajouter facilement de nouveaux champs :
- Photos des pièces
- Codes-barres
- Certifications
- Traçabilité
- Etc.

---

## 🆘 Support

En cas de problème :

1. **Test** : Utiliser `test_extrafields.php`
2. **Logs** : Consulter les logs Dolibarr  
3. **Base** : Vérifier la structure des extrafields
4. **Données** : Contrôler le format JSON

**Contact** : pgourmelen@diamant-industrie.com
