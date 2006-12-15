<?php
$dbh  = new PDO('dsn','username','pw');
$conn = Doctrine_Manager::getInstance()->openConnection($dbh);

$fields = array('id' => array(
                    'type' => 'integer',
                    'autoincrement' => true),
                'name' => array(
                    'type' => 'string', 
                    'fixed' => true, 
                    'length' => 8)
                );
// the following option is mysql specific and
// skipped by other drivers
$options = array('type' => 'MYISAM');

$conn->export->createTable('mytable', $fields);

// on mysql this executes query:
// CREATE TABLE mytable (id INT AUTO_INCREMENT PRIMARY KEY,
//        name CHAR(8));
?>
