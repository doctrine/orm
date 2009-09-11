<?php

require __DIR__ . '/../../lib/Doctrine/Common/IsolatedClassLoader.php';

$classLoader = new \Doctrine\Common\IsolatedClassLoader('Doctrine');
$classLoader->setBasePath(__DIR__ . '/../../lib');
$classLoader->register();

$cli = new \Doctrine\ORM\Tools\Cli();
$cli->run($_SERVER['argv']);