Following data types are availible in doctrine:
    <ul>
    <li /><b> string / s</b>
        <dd /> The same as type 'string' in php
    <li /><b> float / double / f</b>
        <dd /> The same as type 'float' in php<br />
    <li /><b> integer / int / i</b>
        <dd /> The same as type 'integer' in php<br />
    <li /><b> boolean / bool</b>
        <dd /> The same as type 'boolean' in php<br />
    <li /><b> array / a</b>
        <ul> The same as type 'array' in php. Automatically serialized when saved into database and unserialized when retrieved from database.</ul>
    <li /><b> object / o</b>
        <ul> The same as type 'object' in php. Automatically serialized when saved into database and unserialized when retrieved from database.</ul>
    <li /><b> enum / e</b>
        <ul> Unified 'enum' type. Automatically converts the string values into index numbers and vice versa. The possible values for the column 
        can be specified with Doctrine_Record::setEnumValues(columnName, array values).</ul>
    <li /><b> timestamp / t</b>
        <dd /> Database 'timestamp' type
    <li /><b> clob</b>
        <dd /> Database 'clob' type
    <li /><b> blob</b>
        <dd /> Database 'blob' type
    <li /><b> date / d</b>
        <dd /> Database 'date' type
    </ul>

It should be noted that the length of the column affects in database level type
as well as application level validated length (the length that is validated with Doctrine validators).<br \>

<br \>Example 1. Column named 'content' with type 'string' and length 3000 results in database type 'TEXT' of which has database level length of 4000.
However when the record is validated it is only allowed to have 'content' -column with maximum length of 3000.<br \>

<br \>Example 2. Column with type 'integer' and length 1 results in 'TINYINT' on many databases. 
<br \><br \>

In general Doctrine is smart enough to know which integer/string type to use depending on the specified length.

<br \>


