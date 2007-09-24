<?php
require_once('playground.php');
require_once('connection.php');
require_once('models.php');
require_once('data.php');

$action = isset($_REQUEST['action']) ? $_REQUEST['action']:'client';

if ($action == 'server') {
    $config = array('name'      =>  'Doctrine_Resource_Test_Server',
                    'models'    =>  $tables);
    
    $server = Doctrine_Resource_Server::getInstance($config);
    $server->run($_REQUEST);
    
} else {
    //$config = array('url' => 'http://localhost/~jwage/doctrine_trunk/playground/index.php?action=server');
    $config = array('url' => 'http://dev.centresource.com/jwage/hca/web/frontend_dev.php/main');
    
    $client = Doctrine_Resource_Client::getInstance($config);
    
    $user = $client->newRecord('sfGuardUser');
    
    $user->name = 'jonnnywage';
    $user->save();
    
    print_r($user->toArray());
}