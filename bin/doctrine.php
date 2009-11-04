<?php

require 'Doctrine/Common/GlobalClassLoader.php';

$classLoader = new \Doctrine\Common\GlobalClassLoader();
$classLoader->register();

$cli = new \Doctrine\ORM\Tools\Cli();
$cli->run($_SERVER['argv']);