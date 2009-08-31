<?php

require __DIR__ . '/../../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader();
$classLoader->setBasePath('Doctrine', __DIR__ . '/../../lib');

$cli = new \Doctrine\ORM\Tools\Cli();
$cli->run($_SERVER['argv']);