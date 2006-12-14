<?php ?>
Lazy-connecting to database is handled via Doctrine_Db wrapper. When using Doctrine_Db instead of PDO / Doctrine_Adapter, lazy-connecting 
to database is being performed (that means Doctrine will only connect to database when needed). <br \><br \>This feature can be very useful
when using for example page caching, hence not actually needing a database connection on every request. Remember connecting to database is an expensive operation.
<br \> <br \>
<?php
renderCode("<?php
// we may use PDO / PEAR like DSN
// here we use PEAR like DSN
\$dbh = new Doctrine_Db('mysql://username:password@localhost/test');
// !! no actual database connection yet !!

// initalize a new Doctrine_Connection
\$conn = Doctrine_Manager::connection(\$dbh);
// !! no actual database connection yet !!

// connects database and performs a query
\$conn->query('FROM User u');

?>");
?>
