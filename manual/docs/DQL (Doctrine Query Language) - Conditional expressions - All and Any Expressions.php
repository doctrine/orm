Syntax:
<div class='sql'>
<pre>
operand comparison_operator ANY (subquery)
operand comparison_operator SOME (subquery)
operand comparison_operator ALL (subquery)
</pre>
</div>

An ALL conditional expression returns true if the comparison operation is true for all values
in the result of the subquery or the result of the subquery is empty. An ALL conditional expression
is false if the result of the comparison is false for at least one row, and is unknown if neither true nor
false.
<br \><br \>

<div class='sql'>
<pre>
FROM C WHERE C.col1 < ALL (FROM C2(col1))
</pre>
</div>

An ANY conditional expression returns true if the comparison operation is true for some
value in the result of the subquery. An ANY conditional expression is false if the result of the subquery
is empty or if the comparison operation is false for every value in the result of the subquery, and is
unknown if neither true nor false. 

<div class='sql'>
<pre>
FROM C WHERE C.col1 > ANY (FROM C2(col1))
</pre>
</div>

The keyword SOME is an alias for ANY. 
<div class='sql'>
<pre>
FROM C WHERE C.col1 > SOME (FROM C2(col1))
</pre>
</div>
<br \>
The comparison operators that can be used with ALL or ANY conditional expressions are =, <, <=, >, >=, <>. The
result of the subquery must be same type with the conditional expression.
<br \><br \>
NOT IN is an alias for <> ALL. Thus, these two statements are equal:
<br \><br \>
<div class='sql'>
<pre>
FROM C WHERE C.col1 <> ALL (FROM C2(col1));
FROM C WHERE C.col1 NOT IN (FROM C2(col1));
</pre>
</div>

