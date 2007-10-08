<?php
require_once('playground.php');

$dbh = new PDO('mysql:host=localhost;dbname=test', 'jwage', 'elite1baller');
$conn = Doctrine_Manager::connection($dbh);
$manager = Doctrine_Manager::getInstance();
$manager->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_ALL);

Doctrine::loadModels('test_models');

$data = new Doctrine_Data();
$data->importData('fixtures.yml');