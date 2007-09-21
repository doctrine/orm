<?php
require_once('playground.php');
require_once('connection.php');
require_once('models.php');
require_once('data.php');

$tables['Test'] = 'Test';

$import = new Doctrine_Import_Schema();
$import->importSchema('../tests/schema.yml', 'yml', 'test_models', $tables);