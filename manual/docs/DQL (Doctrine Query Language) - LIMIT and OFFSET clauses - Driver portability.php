DQL LIMIT clause is portable on all supported databases. Special attention have been paid to following facts:



*   Only Mysql, Pgsql and Sqlite implement LIMIT / OFFSET clauses natively



*   In Oracle / Mssql / Firebird LIMIT / OFFSET clauses need to be emulated in driver specific way



*  The limit-subquery-algorithm needs to execute to subquery separately in mysql, since mysql doesn't yet support
LIMIT clause in subqueries


*  Pgsql needs the order by fields to be preserved in SELECT clause, hence LS-algorithm needs to take this into consideration
when pgsql driver is used


*  Oracle only allows < 30 object identifiers (= table/column names/aliases), hence the limit subquery must use as short aliases as possible
and it must avoid alias collisions with the main query.

