<?php
ini_set('max_execution_time', 900);

// include doctrine, and register it's autoloader
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'doctrine/Doctrine.php';

spl_autoload_register(array('Doctrine', 'autoload'));

$modelsPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'models';

// include the models
$models = new DirectoryIterator($modelsPath);
foreach($models as $key => $file) {
    if ($file->isFile() && ! $file->isDot()) {
        $e = explode('.', $file->getFileName());
        if (end($e) === 'php') {
          require_once $file->getPathname();
        }
    }
}

error_reporting(E_ALL | E_STRICT);

$dbh = new PDO('sqlite::memory:');
$conn = Doctrine_Manager::connection($dbh);
$manager = Doctrine_Manager::getInstance();

$manager->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_ALL);

$tables =   array('entity',
                  'entityReference',
                  'email',
                  'phonenumber',
                  'groupuser',
                  'album',
                  'song',
                  'element',
                  'error',
                  'description',
                  'address',
                  'account',
                  'task',
                  'resource',
                  'assignment',
                  'resourceType',
                  'resourceReference');

$conn->export->exportClasses($tables);

require_once('data.php');