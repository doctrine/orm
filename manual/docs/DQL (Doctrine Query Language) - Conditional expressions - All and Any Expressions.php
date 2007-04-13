Syntax:
<code>
operand comparison_operator ANY (subquery)
operand comparison_operator SOME (subquery)
operand comparison_operator ALL (subquery)
</code>

An ALL conditional expression returns true if the comparison operation is true for all values
in the result of the subquery or the result of the subquery is empty. An ALL conditional expression
is false if the result of the comparison is false for at least one row, and is unknown if neither true nor
false.




<code>
FROM C WHERE C.col1 < ALL (FROM C2(col1))
</code>

An ANY conditional expression returns true if the comparison operation is true for some
value in the result of the subquery. An ANY conditional expression is false if the result of the subquery
is empty or if the comparison operation is false for every value in the result of the subquery, and is
unknown if neither true nor false. 

<code>
FROM C WHERE C.col1 > ANY (FROM C2(col1))
</code>

The keyword SOME is an alias for ANY. 
<code>
FROM C WHERE C.col1 > SOME (FROM C2(col1))
</code>


The comparison operators that can be used with ALL or ANY conditional expressions are =, <, <=, >, >=, <>. The
result of the subquery must be same type with the conditional expression.



NOT IN is an alias for <> ALL. Thus, these two statements are equal:



<code>
FROM C WHERE C.col1 <> ALL (FROM C2(col1));
FROM C WHERE C.col1 NOT IN (FROM C2(col1));
</code>

