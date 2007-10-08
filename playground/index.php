<?php
require_once('playground.php');

$dbh = new PDO('mysql:host=localhost;dbname=test', 'jwage', 'elite1baller');
$conn = Doctrine_Manager::connection($dbh);
$manager = Doctrine_Manager::getInstance();
$manager->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_ALL);

// Build models from schema
//$import = new Doctrine_Import_Schema();
//$import->generateBaseClasses(true);
//$import->importSchema('schema.yml', 'yml', 'test_models');

// Export models schema to database
//Doctrine::exportSchema('test_models');

// Load model classes
Doctrine::loadModels('test_models');

// Load data fixtures
$data = new Doctrine_Data();
$data->importData('fixtures.yml');