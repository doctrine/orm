<?php
error_reporting(E_ALL | E_STRICT);
ini_set('max_execution_time', 900);
ini_set('date.timezone', 'GMT+0');

set_include_path(
    dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'tests' .
    PATH_SEPARATOR . get_include_path()
);

require_once 'DoctrineTest.php';
require_once dirname(__FILE__) . '/../../lib/Doctrine.php';

function autoload($className)
{
    if (class_exists($className, false)) {
        return false;
    }

    $class = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR
           . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    if (file_exists($class)) {
        require_once($class);

        return true;
    }

    return false;
}

spl_autoload_register('autoload');
spl_autoload_register(array('Doctrine', 'autoload'));
spl_autoload_register(array('DoctrineTest','autoload'));

$test = new DoctrineTest();
//TICKET test cases
$queryTests = new GroupTest('Query tests', 'queries');
$queryTests->addTestCase(new Doctrine_Query_Scanner_TestCase());
$queryTests->addTestCase(new Doctrine_Query_LanguageRecognition_TestCase());

$test->addTestCase($queryTests);


$test->run();

echo memory_get_peak_usage() / 1024 . "\n";
