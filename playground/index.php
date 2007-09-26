<?php
require_once('playground.php');

if (isset($_REQUEST['server'])) {
    require_once('connection.php');
    require_once('models.php');
    require_once('data.php');
    
    $name = 'Doctrine_Resource_Playground';
    $config = array('models'    =>  $tables);
                    
    $server = Doctrine_Resource_Server::getInstance($name, $config);
    $server->run($_REQUEST);
    
} else {
    $url = 'http://localhost/~jwage/doctrine_trunk/playground/index.php?server';
    $config = array('format' => 'xml');
    
    // Instantiate a new client
    $client = Doctrine_Resource_Client::getInstance($url, $config);
    
    $query = new Doctrine_Resource_Query();
    
    $users = $query->from('User u, u.Phonenumber p, u.Email e, u.Address a')->execute();
    
    $json = Doctrine_Parser::load(Doctrine_Parser::dump($users->getFirst()->toArray(true, true), 'json'), 'json');
    
    print_r($json);
}