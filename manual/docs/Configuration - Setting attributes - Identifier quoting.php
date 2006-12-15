<?php ?>
You can quote the db identifiers (table and field names) with quoteIdentifier(). The delimiting style depends on which database driver is being used. NOTE: just because you CAN use delimited identifiers, it doesn't mean you SHOULD use them. In general, they end up causing way more problems than they solve. Anyway, it may be necessary when you have a reserved word as a field name (in this case, we suggest you to change it, if you can).

Some of the internal Doctrine methods generate queries. Enabling the "quote_identifier" attribute of Doctrine you can tell Doctrine to quote the identifiers in these generated queries. For all user supplied queries this option is irrelevant.

Portability is broken by using the following characters inside delimited identifiers: 
<br \> <br \>

<ul>
<li \>backtick (`) -- due to MySQL

<li \>double quote (") -- due to Oracle

<li \>brackets ([ or ]) -- due to Access
</ul>

Delimited identifiers are known to generally work correctly under the following drivers: 
<br \>
<ul>
<li \>Mssql

<li \>Mysql

<li \>Oracle

<li \>Pgsql

<li \>Sqlite

<li \>Firebird
</ul>


When using the quoteIdentifiers option, all of the field identifiers will be automatically quoted in the resulting SQL statements:
<br \><br \>
<?php
renderCode("<?php
\$conn->setAttribute('quote_identifiers', true);
?>");
?>
<br \><br \>

 
will result in a SQL statement that all the field names are quoted with the backtick '`' operator (in MySQL). 
<br \>
<div class='sql'>SELECT * FROM `sometable` WHERE `id` = '123'</div>
<br \>
as opposed to:
<br \>
<div class='sql'>SELECT * FROM sometable WHERE id='123'</div>
