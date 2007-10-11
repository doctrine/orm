<?php
require_once('config.php');

$cli = new Doctrine_Cli($config->getCliConfig());
$cli->run($_SERVER['argv']);