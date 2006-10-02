<?php
/**
 * Small command line script to bundle Doctrine classes.
 */
if (count($argv) < 2) {
    echo "Usage: bundle.php <Doctrine basedir> <Target dir>";
    exit(1);
}

$doctrineBaseDir = $argv[1];
$targetDir = $argv[2];

set_include_path(get_include_path() . PATH_SEPARATOR . $doctrineBaseDir);

require_once 'Doctrine.php';
require_once 'Doctrine/Compiler.php';

spl_autoload_register(array('Doctrine', 'autoload'));

echo "Bundling classes ..." . PHP_EOL;

Doctrine_Compiler::compile($targetDir);

echo "Bundle complete." . PHP_EOL;

exit(0);
?>