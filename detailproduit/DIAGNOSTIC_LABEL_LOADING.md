# 🔍 GUIDE DE DIAGNOSTIC - label_update.js non chargé

## Problème
Le fichier `label_update.js` ne se charge pas, la fonction `openLabelUpdateModal` n'est pas trouvée.

## ✅ ÉTAPES DE RÉSOLUTION

### Étape 1: Vérifier l'upload du fichier

**Vérifiez que le nouveau fichier est bien sur le serveur:**

1. Connectez-vous en FTP/SSH au serveur
2. Allez dans `/home/diamanti/www/doli/custom/detailproduit/js/`
3. Vérifiez que `label_update.js` existe et a été modifié récemment (date)
4. Téléchargez le fichier et vérifiez qu'il contient bien ces lignes au début:

```javascript
window.openLabelUpdateModal = function(commandedetId, socid, productLabel) {
    console.log('🔄 openLabelUpdateModal appelée avec:', {
```

**Si le fichier n'est pas à jour sur le serveur:**
→ Uploadez le nouveau `label_update.js` depuis votre PC vers le serveur

---

### Étape 2: Vider les caches

**Cache navigateur:**
1. Appuyez sur **Ctrl + Shift + R** (ou Cmd + Shift + R sur Mac)
2. OU ouvrez les DevTools (F12) → Onglet Network → Cochez "Disable cache"
3. OU en navigation privée (Ctrl + Shift + N)

**Cache Dolibarr:**
1. Allez dans: Configuration → Autres → Purger le cache
2. OU supprimez manuellement `/home/diamanti/www/doli/documents/admin/temp/*`

---

### Étape 3: Tester le chargement direct du fichier

Dans votre navigateur, ouvrez directement:
```
https://diamant-industrie.com/doli/custom/detailproduit/js/label_update.js
```

**Résultats attendus:**
- ✅ Le fichier s'affiche avec du code JavaScript
- ✅ Vous voyez la première ligne: `/* Copyright (C) 2025`
- ✅ Vous voyez `window.openLabelUpdateModal = function`

**Si erreur 404:**
→ Le fichier n'est pas uploadé au bon endroit

**Si vous voyez l'ancien code:**
→ Problème de cache, essayez Ctrl + F5

---

### Étape 4: Tester avec la page de test

1. Uploadez le fichier `test_simple.html` dans le dossier `/home/diamanti/www/doli/custom/detailproduit/`
2. Ouvrez dans le navigateur: 
   ```
   https://diamant-industrie.com/doli/custom/detailproduit/test_simple.html
   ```
3. Vérifiez le résultat affiché

**Si "✅ SUCCESS":**
→ Le fichier se charge correctement en standalone, le problème vient de l'intégration Dolibarr

**Si "❌ ECHEC":**
→ Le fichier a un problème ou n'est pas au bon endroit

---

### Étape 5: Vérifier la console navigateur

1. Ouvrez la page de commande dans Dolibarr
2. Ouvrez la console (F12 → Console)
3. Recherchez ces messages:

**Messages attendus:**
```
📦 label_update.js chargé
✅ Fonctions label exposées globalement: {openLabelUpdateModal: "function", ...}
🔧 DOMContentLoaded - Initialisation du module de mise à jour de label...
✅ Module label initialisé: {labelAjaxUrl: "/doli/custom/detailproduit/ajax/label_handler.php", ...}
```

**Si vous NE voyez PAS "📦 label_update.js chargé":**
→ Le fichier ne se charge pas du tout

**Si vous voyez une erreur JavaScript:**
→ Copiez l'erreur complète et envoyez-la moi

---

### Étape 6: Vérifier le hook PHP

Vérifiez que le hook charge bien le fichier:

```bash
grep -n "label_update.js" /home/diamanti/www/doli/custom/detailproduit/core/hooks/detailproduit.class.php
```

**Résultat attendu (ligne ~148):**
```php
$label_update_url = dol_buildpath('/detailproduit/js/label_update.js', 1);
...
$output .= '<script type="text/javascript" src="'.$label_update_url.'"></script>';
```

---

### Étape 7: Vérifier l'ordre de chargement

Dans le source HTML de la page Dolibarr (Clic droit → Afficher le code source), cherchez:

```html
<script type="text/javascript" src="/doli/custom/detailproduit/js/label_update.js"></script>
<script type="text/javascript" src="/doli/custom/detailproduit/js/details_popup.js"></script>
```

**Vérifiez:**
- ✅ `label_update.js` doit être AVANT `details_popup.js`
- ✅ Les deux balises `<script>` doivent être présentes
- ✅ Le chemin doit être correct (sans 404)

---

## 🚨 CAS SPÉCIAUX

### Si le fichier ne se charge toujours pas après toutes ces étapes

**Option A: Forcer le rechargement avec un paramètre de version**

Modifiez le hook (`detailproduit.class.php` ligne ~153) :
```php
$output .= '<script type="text/javascript" src="'.$label_update_url.'?v='.time().'"></script>';
```

**Option B: Inliner temporairement le code**

Au lieu de charger un fichier externe, intégrez le code directement dans le hook:
```php
$output .= '<script type="text/javascript">';
$output .= file_get_contents(DOL_DOCUMENT_ROOT.'/custom/detailproduit/js/label_update.js');
$output .= '</script>';
```

---

## 📊 CHECKLIST COMPLÈTE

- [ ] Fichier `label_update.js` uploadé sur le serveur
- [ ] Date de modification du fichier récente
- [ ] Cache navigateur vidé (Ctrl + Shift + R)
- [ ] Cache Dolibarr purgé
- [ ] Fichier accessible directement via URL
- [ ] Console montre "📦 label_update.js chargé"
- [ ] Test avec `test_simple.html` réussi
- [ ] Hook PHP charge bien le fichier
- [ ] Ordre de chargement correct (label_update AVANT details_popup)

---

## 🆘 BESOIN D'AIDE

Si après avoir suivi toutes ces étapes le problème persiste, envoyez-moi:

1. **Console complète** (tous les messages, notamment ceux avec 📦, ✅, ❌)
2. **Résultat de l'URL directe** (https://diamant-industrie.com/doli/custom/detailproduit/js/label_update.js)
3. **Code source HTML** (recherchez "label_update" dans le source de la page)
4. **Erreurs éventuelles** (en rouge dans la console)

---

Date: 2025-11-14
Fichier généré pour diagnostic du module detailproduit
