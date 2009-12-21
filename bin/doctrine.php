<?php

require_once __DIR__ . '/../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine');
$classLoader->setIncludePath(__DIR__ . '/../lib');
$classLoader->register();

$configuration = new \Doctrine\Common\Cli\Configuration();

$cli = new \Doctrine\Common\Cli\CliController($configuration);
$cli->run($_SERVER['argv']);