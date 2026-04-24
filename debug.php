<?php
/* MultiBrands Module Diagnostic Tool - v1.1.0
 * Place this file in your Dolibarr root and access via browser.
 * It will try to locate the multi-brands module and report status.
 */

header('Content-Type: text/html; charset=utf-8');

// Security: require a secret key or localhost access
$allowedKey = 'multibrands_diag_2024'; // Change this key for your installation
if (php_sapi_name() !== 'cli'
    && (!isset($_SERVER['REMOTE_ADDR']) || !in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')))
    && (!isset($_GET['key']) || $_GET['key'] !== $allowedKey)
) {
    http_response_code(403);
    die('Access denied. Append ?key='.$allowedKey.' to the URL or access from localhost.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>MultiBrands Module Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .ok { color: #0f0; }
        .err { color: #f00; }
        .warn { color: #ff0; }
        h1 { color: #0ff; }
        h2 { color: #0ff; margin-top: 30px; }
        pre { background: #222; padding: 10px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #333; }
    </style>
</head>
<body>
<h1>MultiBrands Module Diagnostic v1.1.0</h1>

<?php
function test($label, $condition, $errorMsg = '') {
    if ($condition) {
        echo "<span class='ok'>✅ $label</span><br>";
        return true;
    } else {
        echo "<span class='err'>❌ $label";
        if ($errorMsg) echo " — $errorMsg";
        echo "</span><br>";
        return false;
    }
}

// 1. PHP Basics
echo "<h2>1. PHP Environment</h2>";
test("PHP Version >= 7.4", version_compare(PHP_VERSION, '7.4.0', '>='), PHP_VERSION);
test("finfo extension", extension_loaded('finfo'));

// 2. Dolibarr detection
echo "<h2>2. Dolibarr Detection</h2>";
$mainPaths = [
    __DIR__.'/main.inc.php',
    __DIR__.'/../main.inc.php',
    __DIR__.'/../../main.inc.php',
    (empty($_SERVER['DOCUMENT_ROOT']) ? '' : $_SERVER['DOCUMENT_ROOT'].'/main.inc.php'),
];
$mainFound = false;
foreach ($mainPaths as $p) {
    if (!empty($p) && file_exists($p)) {
        test("main.inc.php found", true, $p);
        $mainFound = true;
        break;
    }
}
if (!$mainFound) {
    test("main.inc.php found", false, "Tried: " . implode(", ", array_filter($mainPaths)));
}

// 3. Module location
echo "<h2>3. Module Location</h2>";
$modulePaths = [
    __DIR__.'/custom/multi-brands/core/modules/modMultiBrands.class.php',
    __DIR__.'/../htdocs/custom/multi-brands/core/modules/modMultiBrands.class.php',
    __DIR__.'/../../htdocs/custom/multi-brands/core/modules/modMultiBrands.class.php',
    (empty($_SERVER['DOCUMENT_ROOT']) ? '' : $_SERVER['DOCUMENT_ROOT'].'/custom/multi-brands/core/modules/modMultiBrands.class.php'),
];
$moduleFound = false;
foreach ($modulePaths as $p) {
    if (!empty($p) && file_exists($p)) {
        test("Module descriptor found", true, $p);
        $moduleFound = true;
        $moduleRoot = dirname(dirname(dirname($p)));
        break;
    }
}
if (!$moduleFound) {
    test("Module descriptor found", false);
    $moduleRoot = null;
}

// 4. Module file structure
echo "<h2>4. Module File Structure</h2>";
if ($moduleRoot) {
    $required = [
        'core/modules/modMultiBrands.class.php',
        'class/multibrand.class.php',
        'lib/multibrands.lib.php',
        'admin/setup.php',
        'core/triggers/interface_99_modMultiBrands_MultiBrandsWorkflow.class.php',
        'core/substitutions/functions_multibrands.lib.php',
        'core/modules/propale/doc/pdf_azur_branded.modules.php',
        'core/modules/facture/doc/pdf_crabe_branded.modules.php',
        'core/modules/commande/doc/pdf_eratosthene_branded.modules.php',
        'langs/en_US/multibrands.lang',
    ];
    foreach ($required as $f) {
        $path = $moduleRoot.'/'.$f;
        test("$f", file_exists($path), file_exists($path) ? 'OK' : 'NOT FOUND');
    }
}

// 5. Try to load module
echo "<h2>5. Module Loading Test</h2>";
if ($moduleRoot && $mainFound) {
    // Try loading main.inc.php
    $res = @include_once __DIR__.'/main.inc.php';
    if (!$res) $res = @include_once __DIR__.'/../main.inc.php';
    if (!$res) $res = @include_once __DIR__.'/../../main.inc.php';
    
    if ($res && !empty($db)) {
        test("Dolibarr loaded", true);
        test("\$db initialized", is_object($db));
        test("\$user initialized", is_object($user));
        test("\$conf initialized", is_object($conf));
        test("\$langs initialized", is_object($langs));
        
        // Try loading module class
        $modFile = $moduleRoot.'/core/modules/modMultiBrands.class.php';
        if (file_exists($modFile)) {
            // Need DOL_DOCUMENT_ROOT defined for this to work
            if (defined('DOL_DOCUMENT_ROOT')) {
                test("DOL_DOCUMENT_ROOT defined", true, DOL_DOCUMENT_ROOT);
                try {
                    require_once $modFile;
                    test("modMultiBrands class loaded", class_exists('modMultiBrands'));
                    if (class_exists('modMultiBrands')) {
                        $mod = new modMultiBrands($db);
                        test("Module version", true, $mod->version);
                    }
                } catch (Exception $e) {
                    test("modMultiBrands class loaded", false, $e->getMessage());
                }
            } else {
                test("DOL_DOCUMENT_ROOT defined", false, "Cannot load module class without DOL_DOCUMENT_ROOT");
            }
        }
    } else {
        test("Dolibarr loaded", false, "Could not include main.inc.php properly");
    }
}

// 6. Database check
echo "<h2>6. Database Check</h2>";
if (!empty($db) && is_object($db)) {
    // Portable table existence check (works on MySQL and PostgreSQL)
    $tableName = MAIN_DB_PREFIX."multibrands_brands";
    $found = false;
    // Method 1: Try to select from table directly (works on all DBs)
    $sql = "SELECT 1 FROM ".$tableName." WHERE 1 = 0";
    $resql = $db->query($sql);
    if ($resql) {
        $found = true;
    } else {
        // Method 2: Fallback to information_schema for more detail
        $sql = "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_name = '".$db->escape($tableName)."'";
        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            $found = ($obj->cnt > 0);
        }
    }
    test("Table " . $tableName . " exists", $found);
    if ($found) {
        $sql2 = "SELECT COUNT(*) as cnt FROM ".$tableName;
        $resql2 = $db->query($sql2);
        if ($resql2) {
            $obj = $db->fetch_object($resql2);
            test("Brands defined", true, $obj->cnt . " brand(s)");
        }
    }
}

// 7. Paths Info
echo "<h2>7. System Paths</h2>";
echo "<pre>";
echo "__DIR__: " . __DIR__ . "\n";
echo "__FILE__: " . __FILE__ . "\n";
echo "DOCUMENT_ROOT: " . (empty($_SERVER['DOCUMENT_ROOT']) ? '(empty)' : $_SERVER['DOCUMENT_ROOT']) . "\n";
echo "SCRIPT_FILENAME: " . (empty($_SERVER['SCRIPT_FILENAME']) ? '(empty)' : $_SERVER['SCRIPT_FILENAME']) . "\n";
echo "REQUEST_URI: " . (empty($_SERVER['REQUEST_URI']) ? '(empty)' : $_SERVER['REQUEST_URI']) . "\n";
if (defined('DOL_DOCUMENT_ROOT')) {
    echo "DOL_DOCUMENT_ROOT: " . DOL_DOCUMENT_ROOT . "\n";
}
if (!empty($moduleRoot)) {
    echo "Module root: " . $moduleRoot . "\n";
}
echo "</pre>";

echo "<h2>8. PHP Error Log Location</h2>";
echo "<pre>";
echo "error_log: " . ini_get('error_log') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "</pre>";

// 9. Last errors from PHP
echo "<h2>9. Recent PHP Errors (if log readable)</h2>";
$logFile = ini_get('error_log');
if (!empty($logFile) && file_exists($logFile) && is_readable($logFile)) {
    $lines = file($logFile);
    $recent = array_slice($lines, -20);
    echo "<pre>";
    foreach ($recent as $line) {
        if (stripos($line, 'multi-brands') !== false || stripos($line, 'multibrands') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<span class='warn'>Cannot read error log. Check cPanel Metrics → Errors.</span>";
}
?>

<hr>
<p style="color:#888;">MultiBrands Diagnostic v1.1.0 — If this page loads, PHP is working. If sections show errors, those are your clues.</p>
</body>
</html>
