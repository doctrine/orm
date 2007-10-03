<?php
require_once('playground.php');

$dsn = 'mysql://jwage:elite1baller@localhost/doctrine_playground';
$conn = Doctrine_Manager::connection($dsn);
$manager = Doctrine_Manager::getInstance();

$models = Doctrine::loadModels('test_models');

$manager->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_ALL);

$conn->export->exportClasses($models);

$query = new Doctrine_Search_Query('Article');

$query->search('test');

$results = $query->execute();

print_r($results);