<?php
$a = array('name' => 'userlist',
           'add' => array(
                    'quota' => array(
                        'type' => 'integer',
                        'unsigned' => 1
                        )
                    ),
            'remove' => array(
                    'file_limit' => array(),
                    'time_limit' => array()
                    ),
            'change' => array(
                    'name' => array(
                        'length' => '20',
                        'definition' => array(
                            'type' => 'text',
                            'length' => 20
                            )
                        )
                    ),
            'rename' => array(
                    'sex' => array(
                        'name' => 'gender',
                        'definition' => array(
                            'type' => 'text',
                            'length' => 1,
                            'default' => 'M'
                            )
                        )
                    )
            
            );

$dbh  = new PDO('dsn','username','pw');
$conn = Doctrine_Manager::getInstance()->openConnection($dbh);

$conn->export->alterTable('mytable', $a);
?>
