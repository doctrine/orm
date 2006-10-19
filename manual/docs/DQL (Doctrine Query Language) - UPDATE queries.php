UPDATE statement syntax:
<div class='sql'>
<pre>
UPDATE <i>component_name</i>
    SET <i>col_name1</i>=<i>expr1</i> [, <i>col_name2</i>=<i>expr2</i> ...]
    [WHERE <i>where_condition</i>]
    [ORDER BY ...]
    [LIMIT <i>record_count</i>]
</pre>
</div>
<ul>
<li \>The UPDATE statement updates columns of existing records in <i>component_name</i> with new values and returns the number of affected records.

<li \>The SET clause indicates which columns to modify and the values they should be given.

<li \>The optional WHERE clause specifies the conditions that identify which records to update.
Without WHERE clause, all records are updated.

<li \>The optional ORDER BY clause specifies the order in which the records are being updated.

<li \>The LIMIT clause places a limit on the number of records that can be updated. You can use LIMIT row_count to restrict the scope of the UPDATE. 
A LIMIT clause is a <b>rows-matched restriction</b> not a rows-changed restriction.
The statement stops as soon as it has found <i>record_count</i> rows that satisfy the WHERE clause, whether or not they actually were changed.
</ul>
