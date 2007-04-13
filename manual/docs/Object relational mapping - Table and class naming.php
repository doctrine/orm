Doctrine automatically creates table names from the record class names. For this reason, it is recommended to name your record classes using the following rules:

    <li>Use CamelCase naming</li>
    <li>Underscores are allowed</li>
    <li>The first letter must be capitalized</li>
    <li>The class name cannot be one of the following (these keywords are reserved in DQL API): 

        SELECT, FROM, WHERE, UPDATE, DELETE, JOIN, OUTER, INNER, LEFT, GROUP, ORDER, BY, HAVING,

        FETCH, DISTINCT, OBJECT, NULL, TRUE, FALSE, 

        NOT, AND, OR, BETWEEN, LIKE, IN,

        AS, UNKNOWN, EMPTY, MEMBER, OF, IS, ASC, DESC, 

        AVG, MAX, MIN, SUM, COUNT,

        MOD, UPPER, LOWER, TRIM, POSITION, 

        CHARACTER_LENGTH, CHAR_LENGTH, BIT_LENGTH, CURRENT_TIME, CURRENT_DATE, 

        CURRENT_TIMESTAMP, NEW, EXISTS, ALL, ANY, SOME.
</li>

Example. My_PerfectClass


If you need to use a different naming schema, you can override this using the setTableName() method in the setTableDefinition() method.

