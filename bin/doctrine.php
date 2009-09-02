<?php

require 'Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader();

$cli = new \Doctrine\ORM\Tools\Cli();
$cli->run($_SERVER['argv']);