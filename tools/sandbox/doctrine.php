<?php

require_once __DIR__ . '/../../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader();
$classLoader->setIncludePath(__DIR__ . '/../../lib');
$classLoader->register();

// Variable $configuration is defined inside cli-config.php
require_once __DIR__ . '/cli-config.php';

$cli = new \Doctrine\Common\Cli\CliController($configuration);
$cli->run($_SERVER['argv']);