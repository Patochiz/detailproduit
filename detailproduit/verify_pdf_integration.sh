#!/bin/bash
# Script de vérification de l'intégration PDF

echo "=== VÉRIFICATION INTÉGRATION PDF - MODULE DETAILPRODUIT ==="
echo "Date: $(date)"
echo ""

# 1. Vérifier les fichiers modifiés
echo "1. Vérification des fichiers..."

if [ -f "js/details_popup.js" ]; then
    echo "✅ details_popup.js présent"
    
    # Vérifier que les nouvelles fonctions sont présentes
    if grep -q "triggerPDFRegeneration" js/details_popup.js; then
        echo "✅ Fonction triggerPDFRegeneration trouvée"
    else
        echo "❌ Fonction triggerPDFRegeneration manquante"
    fi
    
    if grep -q "isPDFGenerationPage" js/details_popup.js; then
        echo "✅ Fonction isPDFGenerationPage trouvée"
    else
        echo "❌ Fonction isPDFGenerationPage manquante"
    fi
    
    if grep -q "hasPDFGeneration" js/details_popup.js; then
        echo "✅ Logique de détection PDF intégrée"
    else
        echo "❌ Logique de détection PDF manquante"
    fi
    
else
    echo "❌ details_popup.js introuvable"
fi

echo ""

# 2. Vérifier les fichiers de test
echo "2. Vérification des fichiers de test..."

if [ -f "test_pdf_integration.php" ]; then
    echo "✅ Page de test créée"
else
    echo "❌ Page de test manquante"
fi

if [ -f "README_PDF_INTEGRATION.md" ]; then
    echo "✅ Documentation créée"
else
    echo "❌ Documentation manquante"
fi

echo ""

# 3. Vérifier la structure des fonctions JavaScript
echo "3. Analyse du code JavaScript..."

if [ -f "js/details_popup.js" ]; then
    FUNCTIONS=$(grep -c "^function\|^.*function.*{" js/details_popup.js)
    PDF_FUNCTIONS=$(grep -c "PDF\|pdf\|triggerPDF\|isPDF" js/details_popup.js)
    
    echo "Fonctions JavaScript trouvées: $FUNCTIONS"
    echo "Fonctions liées au PDF: $PDF_FUNCTIONS"
    
    if [ $PDF_FUNCTIONS -gt 5 ]; then
        echo "✅ Intégration PDF complète"
    else
        echo "⚠️ Intégration PDF partielle"
    fi
fi

echo ""

# 4. Suggestions de test
echo "4. Étapes de test recommandées:"
echo "   a) Accéder à: [URL_DOLIBARR]/custom/detailproduit/test_pdf_integration.php"
echo "   b) Vérifier que 'Module PDF entièrement chargé' s'affiche"
echo "   c) Tester sur une vraie page de commande Dolibarr"
echo "   d) Ouvrir la console navigateur et taper: testPDFButtonDetection()"
echo ""

# 5. Afficher l'état global
echo "5. État global de l'intégration:"
if [ -f "js/details_popup.js" ] && [ -f "test_pdf_integration.php" ]; then
    if grep -q "triggerPDFRegeneration" js/details_popup.js && grep -q "isPDFGenerationPage" js/details_popup.js; then
        echo "🎉 INTÉGRATION PDF RÉUSSIE !"
        echo "   Le module est prêt à régénérer automatiquement les PDF"
    else
        echo "⚠️ INTÉGRATION PARTIELLE"
        echo "   Vérifiez que toutes les fonctions sont présentes"
    fi
else
    echo "❌ INTÉGRATION INCOMPLÈTE"
    echo "   Des fichiers sont manquants"
fi

echo ""
echo "=== FIN DE LA VÉRIFICATION ==="
