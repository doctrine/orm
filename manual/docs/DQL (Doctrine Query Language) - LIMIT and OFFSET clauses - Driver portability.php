DQL LIMIT clause is portable on all supported databases. Special attention have been paid to following facts:
<br \><br \>
<li \>  Only Mysql, Pgsql and Sqlite implement LIMIT / OFFSET clauses natively
<br \><br \>
<li \>  In Oracle / Mssql / Firebird LIMIT / OFFSET clauses need to be emulated in driver specific way
<br \><br \>
<li \> The limit-subquery-algorithm needs to execute to subquery separately in mysql, since mysql doesn't yet support
LIMIT clause in subqueries<br \><br \>
<li \> Pgsql needs the order by fields to be preserved in SELECT clause, hence LS-algorithm needs to take this into consideration
when pgsql driver is used<br \><br \>
<li \> Oracle only allows < 30 object identifiers (= table/column names/aliases), hence the limit subquery must use as short aliases as possible
and it must avoid alias collisions with the main query.
