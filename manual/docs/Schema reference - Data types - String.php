The text data type is available with two options for the length: one that is explicitly length limited and another of undefined length that should be as large as the database allows.
<br \><br \>
The length limited option is the most recommended for efficiency reasons. The undefined length option allows very large fields but may prevent the use of indexes, nullability and may not allow sorting on fields of its type.
<br \><br \>
The fields of this type should be able to handle 8 bit characters. Drivers take care of DBMS specific escaping of characters of special meaning with the values of the strings to be converted to this type.
<br \><br \>
By default Doctrine will use variable length character types. If fixed length types should be used can be controlled via the fixed modifier.
