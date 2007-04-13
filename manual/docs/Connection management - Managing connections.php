<?php ?>
From the start Doctrine has been designed to work with multiple connections. Unless separately specified Doctrine always uses the current connection 
for executing the queries. The following example uses openConnection() second argument as an optional
connection alias.



<code type="php">
// Doctrine_Manager controls all the connections

\$manager = Doctrine_Manager::getInstance();

// open first connection
 
\$conn = \$manager->openConnection(new PDO('dsn','username','password'), 'connection 1');
?>
</code>



For convenience Doctrine_Manager provides static method connection() which opens new connection when arguments are given to it and returns the current
connection when no arguments have been speficied.



<code type="php">
// open first connection
 
\$conn = Doctrine_Manager::connection(new PDO('dsn','username','password'), 'connection 1');

\$conn2 = Doctrine_Manager::connection();

// \$conn2 == \$conn
?>
</code>





The current connection is the lastly opened connection. 



<code type="php">
// open second connection

\$conn2 = \$manager->openConnection(new PDO('dsn2','username2','password2'), 'connection 2');

\$manager->getCurrentConnection(); // \$conn2
?></code>



You can change the current connection by calling setCurrentConnection(). 



<code type="php">
\$manager->setCurrentConnection('connection 1');

\$manager->getCurrentConnection(); // \$conn
?>
</code>



You can iterate over the opened connection by simple passing the manager object to foreach clause. This is possible since Doctrine_Manager implements
special IteratorAggregate interface.



<code type="php">
// iterating through connections

foreach(\$manager as \$conn) {

}
?></code>

