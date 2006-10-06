Doctrine automatically creates table names from the record class names. For this reason, it is recommended to name your record classes using the following rules:
<ul>
    <li>Use CamelCase naming</li>
    <li>Underscores are allowed</li>
    <li>The first letter must be capitalized</li>
    <li>The class name cannot be one of the following (these keywords are reserved in DQL API): <br \>
        SELECT, FROM, WHERE, UPDATE, DELETE, JOIN, OUTER, INNER, LEFT, GROUP, ORDER, BY, HAVING,<br \>
        FETCH, DISTINCT, OBJECT, NULL, TRUE, FALSE, <br \>
        NOT, AND, OR, BETWEEN, LIKE, IN,<br \>
        AS, UNKNOWN, EMPTY, MEMBER, OF, IS, ASC, DESC, <br \>
        AVG, MAX, MIN, SUM, COUNT,<br \>
        MOD, UPPER, LOWER, TRIM, POSITION, <br \>
        CHARACTER_LENGTH, CHAR_LENGTH, BIT_LENGTH, CURRENT_TIME, CURRENT_DATE, <br \>
        CURRENT_TIMESTAMP, NEW, EXISTS, ALL, ANY, SOME.<br \></li>
</ul>
Example. My_PerfectClass
<br />
If you need to use a different naming schema, you can override this using the setTableName() method in the setTableDefinition() method.
