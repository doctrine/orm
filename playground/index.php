<?php
require_once('playground.php');
require_once('connection.php');
require_once('models.php');
require_once('data.php');

Doctrine_Data::exportData('data/data.yml', 'yml', $tables);

//Doctrine_Data::importData('data/data.yml', 'yml', $tables);

//Doctrine_Data::exportData('data/test.yml', 'yml', $tables);