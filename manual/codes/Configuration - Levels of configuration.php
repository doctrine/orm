<?php
// setting a global level attribute
$manager = Doctrine_Manager::getInstance();

$manager->setAttribute(Doctrine::ATTR_VLD, false);

// setting a connection level attribute
// (overrides the global level attribute on this connection)

$conn = $manager->openConnection(new PDO('dsn', 'username', 'pw'));

$conn->setAttribute(Doctrine::ATTR_VLD, true);

// setting a table level attribute
// (overrides the connection/global level attribute on this table)

$table = $conn->getTable('User');

$table->setAttribute(Doctrine::ATTR_LISTENER, new UserListener());
?>
