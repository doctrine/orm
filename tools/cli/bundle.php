<?php
/**
 * Small command line script to bundle Doctrine classes.
 */
if (count($argv) < 2) {
    echo "Usage: bundle.php [Target directory] <Library directory>\n\n".
         "Note: If the library directory is ommited, the path will be deducted\n";
    exit(1);
} else if (count($argv) == 3) {
    $doctrineBaseDir = $argv[2];
} else {
    $doctrineBaseDir = str_replace('tools/cli', 'lib', $_SERVER['PWD'], $Cnt);

    if ($Cnt != 1) {
        echo "Can't find library directory, please specify it as an argument\n";
        exit(1);
    }
}

$targetDir = $argv[1];

echo "Target directory: $targetDir\n";
echo "Base directory: $doctrineBaseDir\n\n";

set_include_path(get_include_path() . PATH_SEPARATOR . $doctrineBaseDir);

require_once 'Doctrine.php';
require_once 'Doctrine/Compiler.php';

spl_autoload_register(array('Doctrine', 'autoload'));

echo "Bundling classes ..." . PHP_EOL;

Doctrine_Compiler::compile($targetDir);

echo "Bundle complete." . PHP_EOL;

exit(0);
?>
