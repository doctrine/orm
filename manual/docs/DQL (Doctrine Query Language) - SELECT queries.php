SELECT statement syntax:
<code>
SELECT
    [ALL | DISTINCT]
    <select_expr>, ...
    [FROM <components>
    [WHERE <where_condition>]
    [GROUP BY <groupby_expr>
      [ASC | DESC], ... ]
    [HAVING <where_condition>]
    [ORDER BY <orderby_expr>
      [ASC | DESC], ...]
    [LIMIT <row_count> OFFSET <offset>}]
</code>


The SELECT statement is used for the retrieval of data from one or more components.

* Each //select_expr// indicates a column or an aggregate function value that you want to retrieve. There must be at least one //select_expr// in every SELECT statement.
<code>
SELECT a.name, a.amount FROM Account a
</code>

* An asterisk can be used for selecting all columns from given component. Even when using an asterisk the executed sql queries never actually use it
(Doctrine converts asterisk to appropriate column names, hence leading to better performance on some databases).
<code>
SELECT a.* FROM Account a
</code>
* FROM clause //components// indicates the component or components from which to retrieve records.
<code>
SELECT a.* FROM Account a

SELECT u.*, p.*, g.* FROM User u LEFT JOIN u.Phonenumber p LEFT JOIN u.Group g
</code>
* The WHERE clause, if given, indicates the condition or conditions that the records must satisfy to be selected. //where_condition// is an expression that evaluates to true for each row to be selected. The statement selects all rows if there is no WHERE clause.
<code>
SELECT a.* FROM Account a WHERE a.amount > 2000
</code>
* In the WHERE clause, you can use any of the functions and operators that DQL supports, except for aggregate (summary) functions

* The HAVING clause can be used for narrowing the results with aggregate functions
<code>
SELECT u.* FROM User u LEFT JOIN u.Phonenumber p HAVING COUNT(p.id) > 3
</code>
* The ORDER BY clause can be used for sorting the results
<code>
SELECT u.* FROM User u ORDER BY u.name
</code>
* The LIMIT and OFFSET clauses can be used for efficiently limiting the number of records to a given //row_count//
<code>
SELECT u.* FROM User u LIMIT 20
</code>



