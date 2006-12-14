<?php ?>
Opening a new database connection in Doctrine is very easy. If you wish to use PDO (www.php.net/PDO) you can just initalize a new PDO object:
<br \> <br \>
<?php
renderCode("<?php
\$dsn = 'mysql:dbname=testdb;host=127.0.0.1';
\$user = 'dbuser';
\$password = 'dbpass';

try {
    \$dbh = new PDO(\$dsn, \$user, \$password);
} catch (PDOException \$e) {
    echo 'Connection failed: ' . \$e->getMessage();
}
?>");
?>
<br \><br \>
If your database extension isn't supported by PDO you can use special Doctrine_Adapter class (if availible). The following example uses db2 adapter:
<br \><br \>
<?php
renderCode("<?php
\$dsn = 'db2:dbname=testdb;host=127.0.0.1';
\$user = 'dbuser';
\$password = 'dbpass';

try {
    \$dbh = Doctrine_Adapter::connect(\$dsn, \$user, \$password);
} catch (PDOException \$e) {
    echo 'Connection failed: ' . \$e->getMessage();
}
?>");
?>
<br \><br \>
The next step is opening a new Doctrine_Connection.
<br \><br \>
<?php
renderCode("<?php
\$conn = Doctrine_Manager::connection(\$dbh);
?>");
?>
