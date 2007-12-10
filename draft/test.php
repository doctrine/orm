<?php
require_once 'Doctrine.php';

spl_autoload_register(array('Doctrine', 'autoload'));

$n = 1000;

$start = microtime(true);
for ($i = 0; $i < $n; $i++) {
/*    $parser = new Doctrine_Query_Parser('SELECT u.name, u.age FROM User u WHERE u.id = ?');
    $parser->parse();*/
    $scanner = new Doctrine_Query_Scanner('SELECT u.name, u.age FROM User u WHERE u.id = ?');
    do {
        $token = $scanner->scan();
    } while ($token['type'] !== Doctrine_Query_Token::T_EOS);

}
$end = microtime(true);

printf("Parsed %d queries: %.3f ms per query\n", $n, ($end - $start) / $n * 1000);
