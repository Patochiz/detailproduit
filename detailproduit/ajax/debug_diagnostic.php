<?php
/**
 * Fichier de diagnostic pour le module detailproduit
 * À placer dans le dossier ajax/ pour tester l'inclusion de main.inc.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC MODULE DETAILPRODUIT ===\n\n";

echo "1. INFORMATIONS SYSTEME\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Script: " . __FILE__ . "\n";
echo "Dossier: " . __DIR__ . "\n";
echo "Chemin réel: " . realpath(__DIR__) . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'UNDEFINED') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'UNDEFINED') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'UNDEFINED') . "\n\n";

echo "2. RECHERCHE DE main.inc.php\n";

// Chemins à tester
$test_paths = array(
    __DIR__ . "/../../../main.inc.php",           // Standard depuis custom/module/ajax/
    __DIR__ . "/../../../../main.inc.php",        // Si un niveau de plus
    __DIR__ . "/../../main.inc.php",              // Si structure différente
    dirname(dirname(dirname(__DIR__))) . "/main.inc.php",  // Méthode alternative
);

if (isset($_SERVER['DOCUMENT_ROOT'])) {
    $test_paths[] = $_SERVER['DOCUMENT_ROOT'] . "/main.inc.php";
    $test_paths[] = $_SERVER['DOCUMENT_ROOT'] . "/doli/main.inc.php";
    $test_paths[] = $_SERVER['DOCUMENT_ROOT'] . "/dolibarr/main.inc.php";
}

foreach ($test_paths as $path) {
    $real_path = realpath($path);
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    
    echo "Test: $path\n";
    echo "  Réel: " . ($real_path ?: 'INEXISTANT') . "\n";
    echo "  Existe: " . ($exists ? 'OUI' : 'NON') . "\n";
    echo "  Lisible: " . ($readable ? 'OUI' : 'NON') . "\n";
    
    if ($exists && $readable) {
        echo "  *** CANDIDAT VALIDE ***\n";
    }
    echo "\n";
}

echo "3. RECHERCHE PAR REMONTEE\n";

$current_dir = __DIR__;
$max_levels = 10;

for ($i = 0; $i < $max_levels; $i++) {
    $test_path = $current_dir . "/main.inc.php";
    $exists = file_exists($test_path);
    
    echo "Niveau $i: $test_path\n";
    echo "  Existe: " . ($exists ? 'OUI' : 'NON') . "\n";
    
    if ($exists) {
        echo "  *** TROUVE PAR REMONTEE ***\n";
        break;
    }
    
    $parent_dir = dirname($current_dir);
    if ($parent_dir === $current_dir) {
        echo "  Racine atteinte\n";
        break;
    }
    $current_dir = $parent_dir;
}

echo "\n4. TEST D'INCLUSION\n";

// Essayer d'inclure le premier fichier trouvé
$main_found = false;
$main_path = '';

foreach ($test_paths as $path) {
    if (file_exists($path) && is_readable($path)) {
        $main_path = realpath($path);
        $main_found = true;
        break;
    }
}

if ($main_found) {
    echo "Tentative d'inclusion: $main_path\n";
    
    // Sauvegarder l'état des variables avant inclusion
    $vars_before = array_keys(get_defined_vars());
    
    try {
        $result = @include_once $main_path;
        
        if ($result) {
            echo "Inclusion: REUSSIE\n";
            
            // Vérifier les variables importantes
            $vars_after = array_keys(get_defined_vars());
            $new_vars = array_diff($vars_after, $vars_before);
            
            echo "Nouvelles variables: " . implode(', ', $new_vars) . "\n";
            
            echo "Variables importantes:\n";
            echo "  \$db: " . (isset($db) ? 'DEFINI (' . get_class($db) . ')' : 'NON DEFINI') . "\n";
            echo "  \$user: " . (isset($user) ? 'DEFINI (' . get_class($user) . ')' : 'NON DEFINI') . "\n";
            echo "  \$conf: " . (isset($conf) ? 'DEFINI (' . get_class($conf) . ')' : 'NON DEFINI') . "\n";
            echo "  DOL_DOCUMENT_ROOT: " . (defined('DOL_DOCUMENT_ROOT') ? DOL_DOCUMENT_ROOT : 'NON DEFINI') . "\n";
            echo "  MAIN_DB_PREFIX: " . (defined('MAIN_DB_PREFIX') ? MAIN_DB_PREFIX : 'NON DEFINI') . "\n";
            
            if (isset($user) && $user->id) {
                echo "  User ID: " . $user->id . "\n";
                echo "  User Login: " . $user->login . "\n";
            }
            
            // Test fonction isModEnabled
            if (function_exists('isModEnabled')) {
                echo "  Module detailproduit: " . (isModEnabled('detailproduit') ? 'ACTIVE' : 'INACTIF') . "\n";
            } else {
                echo "  Fonction isModEnabled: NON DISPONIBLE\n";
            }
            
        } else {
            echo "Inclusion: ECHEC (include retourné false)\n";
        }
        
    } catch (Exception $e) {
        echo "Inclusion: EXCEPTION - " . $e->getMessage() . "\n";
    }
    
} else {
    echo "Aucun fichier main.inc.php valide trouvé\n";
}

echo "\n5. PERMISSIONS ET SECURITE\n";

// Vérifier les permissions du fichier actuel
$current_perms = fileperms(__FILE__);
echo "Permissions fichier actuel: " . decoct($current_perms & 0777) . "\n";

// Vérifier le propriétaire
$current_owner = fileowner(__FILE__);
echo "Propriétaire fichier actuel: " . $current_owner . "\n";

// Vérifier si on peut lire les fichiers PHP du répertoire parent
$parent_dir = dirname(__DIR__);
$parent_files = glob($parent_dir . "/*.php");
echo "Fichiers PHP dans le répertoire parent: " . count($parent_files) . "\n";

echo "\n6. VARIABLES D'ENVIRONNEMENT\n";

$env_vars = array('HTTP_HOST', 'SERVER_NAME', 'REQUEST_SCHEME', 'HTTPS');
foreach ($env_vars as $var) {
    echo "$var: " . ($_SERVER[$var] ?? 'UNDEFINED') . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
?>