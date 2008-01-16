<?php
error_reporting(E_ALL | E_STRICT);
ini_set('max_execution_time', 900);
ini_set('date.timezone', 'GMT+0');

require_once(dirname(__FILE__) . '/DoctrineTest.php');
require_once dirname(__FILE__) . '/../../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));
spl_autoload_register(array('DoctrineTest','autoload'));
function autoload($className)
{
	$class = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
           . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    print $class;
    if (file_exists($class)) {
        require_once($class);

        return true;
    }

    return false;
}

spl_autoload_register('autoload');

$test = new DoctrineTest();
//TICKET test cases
$queryTests = new GroupTest('Query tests', 'queries');
$queryTests->addTestCase(new Doctrine_Query_Scanner_TestCase());

$test->addTestCase($queryTests);


$test->run();

echo memory_get_peak_usage() / 1024 . "\n";
