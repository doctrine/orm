<?php ?>
Doctrine_Db allows both PEAR-like DSN (data source name) as well as PDO like DSN as constructor parameters.
<br \><br \>
Getting an instance of Doctrine_Db using PEAR-like DSN:
<br \><br \>
<?php
$str = "<?php
// using PEAR like dsn for connecting pgsql database

\$dbh = new Doctrine_Db('pgsql://root:password@localhost/mydb');

// using PEAR like dsn for connecting mysql database

\$dbh = new Doctrine_Db('mysql://root:password@localhost/test');
?>";
renderCode($str);
?>
<br \><br \>
Getting an instance of Doctrine_Db using PDO-like DSN (PDO mysql driver):
<br \><br \>
<?php
$str = "<?php
\$dbh = new Doctrine_Db('mysql:host=localhost;dbname=test', 
                        \$user, \$pass);
?>";
renderCode($str);
?>
<br \><br \>
Getting an instance of Doctrine_Db using PDO-like DSN (PDO sqlite with memory tables):
<br \> <br \>
<?php
$str = "<?php
\$dbh = new Doctrine_Db('sqlite::memory:');
?>";
renderCode($str);
?>
<br \><br \>

Handling connection errors:

<?php
$str = "<?php
try {
   \$dbh = new Doctrine_Db('mysql:host=localhost;dbname=test', 
                           \$user, \$pass);
   foreach (\$dbh->query('SELECT * FROM foo') as \$row) {
     print_r(\$row);
   }
   \$dbh = null;
} catch (PDOException \$e) {
   print 'Error!: ' . \$e->getMessage() . '<br />';
   die();
}
?>";
renderCode($str);
?>
