<?php  ?>
A transaction isolation level sets the default transactional behaviour.
As the name 'isolation level' suggests, the setting determines how isolated each transation is,
or what kind of locks are associated with queries inside a transaction.
The four availible levels are (in ascending order of strictness):
<br \><br \>
<i>READ UNCOMMITTED</i>:  Barely transactional, this setting allows for so-called 'dirty reads',
where queries inside one transaction are affected by uncommitted changes in another transaction.
<br \><br \>
<i>READ COMMITTED</i>: Committed updates are visible within another transaction.
 This means identical queries within a transaction can return differing results. This is the default in some DBMS's.
<br \> <br \>
<i>REPEATABLE READ</i>: Within a transaction, all reads are consistent. This is the default of Mysql INNODB engine.
<br \><br \>
<i>SERIALIZABLE</i>: Updates are not permitted in other transactions if a transaction has run an ordinary SELECT query.
<br \><br \>
<?php
renderCode("<?php
\$tx = \$conn->transaction; // get the transaction module

// sets the isolation level to READ COMMITTED
\$tx->setIsolation('READ COMMITTED');

// sets the isolation level to SERIALIZABLE
\$tx->setIsolation('SERIALIZABLE');

// Some drivers (like Mysql) support the fetching of current transaction
// isolation level. It can be done as follows:
\$level = \$tx->getIsolation();
?>");
?>
