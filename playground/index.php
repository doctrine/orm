<?php
require_once('playground.php');

$action = isset($_REQUEST['action']) ? $_REQUEST['action']:'client';

if ($action == 'server') {
    require_once('connection.php');
    require_once('models.php');
    require_once('data.php');
    
    $config = array('name'      =>  'Doctrine_Resource_Test_Server',
                    'models'    =>  $tables);
    
    $server = Doctrine_Resource_Server::getInstance($config);
    $server->run($_REQUEST);
    
} else {
    $config = array('url' => 'http://localhost/~jwage/doctrine_trunk/playground/index.php?action=server');
    
    $client = Doctrine_Resource_Client::getInstance($config);
    
    //$table = $client->getTable('User');
    
    $query = new Doctrine_Resource_Query();
    
    $users = $query->from('User u, u.Phonenumber p, u.Address a, u.Book b, b.Author a')->execute();
    
    print_r($users);
}