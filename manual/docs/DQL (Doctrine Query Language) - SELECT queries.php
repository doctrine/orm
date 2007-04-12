SELECT statement syntax:
<div class='sql'>
<pre>
SELECT
    [ALL | DISTINCT]
    <i>select_expr</i>, ...
    [FROM <i>components</i>
    [WHERE <i>where_condition</i>]
    [GROUP BY <i>groupby_expr</i>
      [ASC | DESC], ... ]
    [HAVING <i>where_condition</i>]
    [ORDER BY <i>orderby_expr</i>
      [ASC | DESC], ...]
    [LIMIT <i>row_count</i> OFFSET <i>offset</i>}]
</pre>
</div>
<br \>
The SELECT statement is used for the retrieval of data from one or more components.
<ul>
<li \>Each <i>select_expr</i> indicates a column or an aggregate function value that you want to retrieve. There must be at least one <i>select_expr</i> in every SELECT statement.
<div class='sql'>
<pre>
SELECT a.name, a.amount FROM Account a
</pre>
</div>

<li \>An asterisk can be used for selecting all columns from given component. Even when using an asterisk the executed sql queries never actually use it 
(Doctrine converts asterisk to appropriate column names, hence leading to better performance on some databases).
<div class='sql'>
<pre>
SELECT a.* FROM Account a
</pre>
</div>
<li \>FROM clause <i>components</i> indicates the component or components from which to retrieve records.
<div class='sql'>
<pre>
SELECT a.* FROM Account a

SELECT u.*, p.*, g.* FROM User u LEFT JOIN u.Phonenumber p LEFT JOIN u.Group g
</pre>
</div>
<li \>The WHERE clause, if given, indicates the condition or conditions that the records must satisfy to be selected. <i>where_condition</i> is an expression that evaluates to true for each row to be selected. The statement selects all rows if there is no WHERE clause.
<div class='sql'>
<pre>
SELECT a.* FROM Account a WHERE a.amount > 2000
</pre>
</div>
<li \>In the WHERE clause, you can use any of the functions and operators that DQL supports, except for aggregate (summary) functions

<li \>The HAVING clause can be used for narrowing the results with aggregate functions
<div class='sql'>
<pre>
SELECT u.* FROM User u LEFT JOIN u.Phonenumber p HAVING COUNT(p.id) > 3
</pre>
</div>
<li \>The ORDER BY clause can be used for sorting the results
<div class='sql'>
<pre>
SELECT u.* FROM User u ORDER BY u.name
</pre>
</div>
<li \>The LIMIT and OFFSET clauses can be used for efficiently limiting the number of records to a given <i>row_count</i>
<div class='sql'>
<pre>
SELECT u.* FROM User u LIMIT 20
</pre>
</div>
</ul>


