<?php
require_once('playground.php');

$action = isset($_REQUEST['action']) ? $_REQUEST['action']:'client';

if ($action == 'server') {
    require_once('connection.php');
    require_once('models.php');
    require_once('data.php');
    
    $name = 'Doctrine_Resource_Playground';
    $config = array('models'    =>  $tables);
                    
    $server = Doctrine_Resource_Server::getInstance($name, $config);
    $server->run($_REQUEST);
    
} else {
    $url = 'http://localhost/~jwage/doctrine_trunk/playground/index.php?action=server';
    $config = array();
    
    // Instantiate a new client
    $client = Doctrine_Resource_Client::getInstance($url, $config);
    
    $query = new Doctrine_Resource_Query();
    $users = $query->from('User u, u.Group g')->execute();
    
    print_r($users->toArray(true));
    
    /*
    $group = new Group();
    $group->name = 'Jon';
    $group->save();
    
    print_r($group->toArray());
    */
    
    //$client->printSchema();
    
    /*
    // Retrieve a models table object
    $table = $client->getTable('User');
    
    $user = new User();
    $user->name = 'Jon Wage';
    
    $user->Email->address = 'jonwage@gmail.com';
    
    $phone = $user->Phonenumber[0];
    $phone->phonenumber = '555-5555';
    
    $phone = $user->Phonenumber[1];
    $phone->phonenumber = '555-55555';
    
    $user->Phonenumber[2]->phonenumber = '555';
    
    $user->Account->amount = 50.00;
    
    $user->Account->amount = 25.25;
    
    $address = $user->Address[0];
    
    $address->address = '112 2nd Ave North';
    
    $album = $user->Album[0];
    $album->name = 'test album';
    
    $song = $album->Song[0];
    $song->title = 'test author';
    
    $user->save();
    
    print_r($user->toArray(true));
    */
}