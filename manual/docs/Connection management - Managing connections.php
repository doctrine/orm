<?php ?>
From the start Doctrine has been designed to work with multiple connections. Unless separately specified Doctrine always uses the current connection 
for executing the queries. The following example uses openConnection() second argument as an optional
connection alias.
<br \><br \>
<?php
renderCode("<?php
// Doctrine_Manager controls all the connections

\$manager = Doctrine_Manager::getInstance();

// open first connection
 
\$conn = \$manager->openConnection(new PDO('dsn','username','password'), 'connection 1');
?>
");
?>
<br \><br \>
For convenience Doctrine_Manager provides static method connection() which opens new connection when arguments are given to it and returns the current
connection when no arguments have been speficied.
<br \><br \>
<?php
renderCode("<?php
// open first connection
 
\$conn = Doctrine_Manager::connection(new PDO('dsn','username','password'), 'connection 1');

\$conn2 = Doctrine_Manager::connection();

// \$conn2 == \$conn
?>
");
?>


<br \><br \>
The current connection is the lastly opened connection. 
<br \><br \>
<?php
renderCode("<?php
// open second connection

\$conn2 = \$manager->openConnection(new PDO('dsn2','username2','password2'), 'connection 2');

\$manager->getCurrentConnection(); // \$conn2
?>");
?>
<br \><br \>
You can change the current connection by calling setCurrentConnection(). 
<br \><br \>
<?php
renderCode("<?php
\$manager->setCurrentConnection('connection 1');

\$manager->getCurrentConnection(); // \$conn
?>
");
?>
<br \><br \>
You can iterate over the opened connection by simple passing the manager object to foreach clause. This is possible since Doctrine_Manager implements
special IteratorAggregate interface.
<br \><br \>
<?php
renderCode("<?php
// iterating through connections

foreach(\$manager as \$conn) {

}
?>");
?>
