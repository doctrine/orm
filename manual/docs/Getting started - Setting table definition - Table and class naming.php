Doctrine automatically creates table names from the record class names. For this reason, it is recommended to name your record classes using the following rules:
<ul>
    <li>Use CamelCase naming</li>
    <li>Underscores are allowed</li>
    <li>The first letter must be capitalized</li>
</ul>
Example. My_PerfectClass
<br />
If you need to use a different naming schema, you can override this using the setTableName() method in the setTableDefinition() method.
