<?php

require __DIR__ . '/../../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine', __DIR__ . '/../../lib');
$classLoader->register();

$cli = new \Doctrine\ORM\Tools\Cli\CliController();
$cli->run($_SERVER['argv']);