<?php
require_once('playground.php');
require_once('connection.php');
require_once('models.php');
require_once('data.php');

$action = isset($_REQUEST['action']) ? $_REQUEST['action']:'client';

if ($action == 'server') {
    $config = array();
    
    $server = new Doctrine_Resource_Server($config);
    $server->run($_REQUEST);
} else {
    $config = array('url' => 'http://localhost/~jwage/doctrine_trunk/playground/index.php?action=server');
    
    $client = new Doctrine_Resource_Client($config);
    $record = $client->newRecord('User');
    $record->name = 'jon wage';
    $record->loginname = 'test';
    $record->save();
    
    print_r($record->toArray());
}