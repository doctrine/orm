#!/usr/bin/env php
<?php
chdir(dirname(__FILE__));

define('SANDBOX_PATH', dirname(__FILE__));
define('DOCTRINE_PATH', dirname(dirname(SANDBOX_PATH)) . DIRECTORY_SEPARATOR . 'lib');
define('DATA_FIXTURES_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'fixtures');
define('MODELS_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'models');
define('MIGRATIONS_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'migrations');
define('SQL_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'sql');
define('YAML_SCHEMA_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'schema');
define('DB_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'sandbox.db');
define('DSN', 'sqlite:///' . DB_PATH);

require_once(DOCTRINE_PATH . DIRECTORY_SEPARATOR . 'Doctrine.php');

spl_autoload_register(array('Doctrine', 'autoload'));

Doctrine_Manager::connection(DSN, 'sandbox');

Doctrine_Manager::getInstance()->setAttribute(Doctrine::ATTR_MODEL_LOADING, Doctrine::MODEL_LOADING_CONSERVATIVE);

// Configure Doctrine Cli
// Normally these are arguments to the cli tasks but if they are set here the arguments will be auto-filled
$config = array('data_fixtures_path'  =>  DATA_FIXTURES_PATH,
                'models_path'         =>  MODELS_PATH,
                'migrations_path'     =>  MIGRATIONS_PATH,
                'sql_path'            =>  SQL_PATH,
                'yaml_schema_path'    =>  YAML_SCHEMA_PATH);

$cli = new Doctrine_Cli($config);
$cli->run($_SERVER['argv']);