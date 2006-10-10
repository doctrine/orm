Syntax:
<div class='sql'>
<pre>
<i>operand</i> IN (<i>subquery</i>|<i>value list</i>)
</pre>
</div>
An IN conditional expression returns true if the <i>operand</i> is found from result of the <i>subquery</i>
or if its in the specificied comma separated <i>value list</i>, hence the IN expression is always false if the result of the subquery
is empty.

When <i>value list</i> is being used there must be at least one element in that list.

<div class='sql'>
<pre>
FROM C1 WHERE C1.col1 IN (FROM C2(col1));

FROM User WHERE User.id IN (1,3,4,5)
</pre>
</div>

The keyword IN is an alias for = ANY. Thus, these two statements are equal:
<div class='sql'>
<pre>
FROM C1 WHERE C1.col1 = ANY (FROM C2(col1));
FROM C1 WHERE C1.col1 IN    (FROM C2(col1));
</pre>
</div>

