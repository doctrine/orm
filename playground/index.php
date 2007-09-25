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
    
    // Retrieve a models table object
    $table = $client->getTable('User');
    
    // Find record by identifier
    $user = $table->find(4);
    
    // 2 ways to create queries
    $query = new Doctrine_Resource_Query();
    $query->from('User u, u.Phonenumber p')->limit(2);
    
    // returns users
    $users = $query->execute();
    
    print_r($users->toArray());
    
    /*
        Array
        (
            [User_0] => Array
                (
                    [id] => 4
                    [name] => zYne
                    [loginname] => 
                    [password] => 
                    [type] => 0
                    [created] => 
                    [updated] => 
                    [email_id] => 1
                    [Phonenumber] => Array
                        (
                            [Phonenumber_0] => Array
                                (
                                    [id] => 2
                                    [phonenumber] => 123 123
                                    [entity_id] => 4
                                )

                        )

                )

            [User_1] => Array
                (
                    [id] => 5
                    [name] => Arnold Schwarzenegger
                    [loginname] => 
                    [password] => 
                    [type] => 0
                    [created] => 
                    [updated] => 
                    [email_id] => 2
                    [Phonenumber] => Array
                        (
                            [Phonenumber_0] => Array
                                (
                                    [id] => 3
                                    [phonenumber] => 123 123
                                    [entity_id] => 5
                                )

                            [Phonenumber_1] => Array
                                (
                                    [id] => 4
                                    [phonenumber] => 456 456
                                    [entity_id] => 5
                                )

                            [Phonenumber_2] => Array
                                (
                                    [id] => 5
                                    [phonenumber] => 789 789
                                    [entity_id] => 5
                                )

                        )

                )

        )
        */
    
    $user = new User();
    $user->name = 'Jonathan H. Wage';
    $user->save();
    
    print_r($user->toArray());
    
    /*
        Array
        (
            [id] => 12
            [name] => Jonathan H. Wage
            [loginname] => 
            [password] => 
            [type] => 0
            [created] => 
            [updated] => 
            [email_id] => 
        )
    */
}