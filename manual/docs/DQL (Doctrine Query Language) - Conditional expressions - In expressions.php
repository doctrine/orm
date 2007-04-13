Syntax:
<code>
<operand> IN (<subquery>|<value list>)
</code>
An IN conditional expression returns true if the //operand// is found from result of the //subquery//
or if its in the specificied comma separated //value list//, hence the IN expression is always false if the result of the subquery
is empty.

When //value list// is being used there must be at least one element in that list.

<code>
FROM C1 WHERE C1.col1 IN (FROM C2(col1));

FROM User WHERE User.id IN (1,3,4,5)
</code>

The keyword IN is an alias for = ANY. Thus, these two statements are equal:
<code>
FROM C1 WHERE C1.col1 = ANY (FROM C2(col1));
FROM C1 WHERE C1.col1 IN    (FROM C2(col1));
</code>


