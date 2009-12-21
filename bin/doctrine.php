<?php

require 'Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine');
$classLoader->register();

$cli = new \Doctrine\ORM\Tools\Cli\CliController();
$cli->run($_SERVER['argv']);