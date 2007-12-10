<?php
function autoload($className)
{
    if (class_exists($className, false)) {
        return false;
    }

    $class = dirname(__FILE__) . DIRECTORY_SEPARATOR
           . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    if (file_exists($class)) {
        require_once($class);

        return true;
    }

    return false;
}

spl_autoload_register('autoload');

$n = 1000;

$start = microtime(true);
for ($i = 0; $i < $n; $i++) {
    $parser = new Doctrine_Query_Parser('SELECT u.name, u.age FROM User u WHERE u.id = ?');
    $parser->parse();
}
$end = microtime(true);

printf("Parsed %d queries: %.3f ms per query\n", $n, ($end - $start) / $n * 1000);
