<?php

require 'Doctrine/Common/GlobalClassLoader.php';

$classLoader = new \Doctrine\Common\GlobalClassLoader();
$classLoader->register();

$cli = new \Doctrine\ORM\Tools\Cli\CliController();
$cli->run($_SERVER['argv']);