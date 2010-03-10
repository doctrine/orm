<?php

require_once 'Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine');
$classLoader->register();

$configFile = getcwd() . DIRECTORY_SEPARATOR . 'cli-config.php';

$configuration = null;
if (file_exists($configFile)) {
    if ( ! is_readable($configFile)) {
        trigger_error(
            'Configuration file [' . $configFile . '] does not have read permission.', E_ERROR
        );
    }

    require $configFile;
    
    foreach ($GLOBALS as $configCandidate) {
        if ($configCandidate instanceof \Doctrine\Common\Cli\Configuration) {
            $configuration = $configCandidate;
            break;
        }
    }
}

$configuration = ($configuration) ?: new \Doctrine\Common\Cli\Configuration();

$cli = new \Doctrine\Common\Cli\CliController($configuration);
$cli->run($_SERVER['argv']);