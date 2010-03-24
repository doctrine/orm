<?php

require __DIR__ . '/../../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine', __DIR__ . '/../../lib');
$classLoader->register();

// Variable $configuration is defined inside cli-config.php
require __DIR__ . '/cli-config.php';

$cli = new \Doctrine\Common\CLI\CLIController($configuration);
$cli->run($_SERVER['argv']);