<?php
require_once('playground.php');

$dbh = new PDO('mysql:host=localhost;dbname=test', 'jwage', 'elite1baller');
$conn = Doctrine_Manager::connection($dbh);
$manager = Doctrine_Manager::getInstance();

//Doctrine::loadModels('test_models');

$import = new Doctrine_Import_Schema();
$import->generateBaseClasses(true);
$import->importSchema('schema.yml', 'yml', 'test_models');