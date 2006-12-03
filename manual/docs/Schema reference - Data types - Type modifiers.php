Within the Doctrine API there are a few modifiers that have been designed to aid in optimal table design. These are:
<br \>
<ul>
<li \>The notnull modifiers

<li \>The length modifiers

<li \>The default modifiers

<li \>unsigned modifiers for some field definitions, although not all DBMS's support this modifier for integer field types.

<li \>zerofill modifiers (not supported by all drivers)

<li \>collation modifiers (not supported by all drivers)

<li \>fixed length modifiers for some field definitions.
</ul>
Building upon the above, we can say that the modifiers alter the field definition to create more specific field types for specific usage scenarios. The notnull modifier will be used in the following way to set the default DBMS NOT NULL Flag on the field to true or false, depending on the DBMS's definition of the field value: In PostgreSQL the "NOT NULL" definition will be set to "NOT NULL", whilst in MySQL (for example) the "NULL" option will be set to "NO". In order to define a "NOT NULL" field type, we simply add an extra parameter to our definition array (See the examples in the following section)
<br \><br \>
<?php
'sometime' = array(
    'type'    = 'time',
    'default' = '12:34:05',
    'notnull' = true,
),
?>


<br \><br \>
Using the above example, we can also explore the default field operator. Default is set in the same way as the notnull operator to set a default value for the field. This value may be set in any character set that the DBMS supports for text fields, and any other valid data for the field's data type. In the above example, we have specified a valid time for the "Time" data type, '12:34:05'. Remember that when setting default dates and times, as well as datetimes, you should research and stay within the epoch of your chosen DBMS, otherwise you will encounter difficult to diagnose errors! 
<br \><br \>
Example 33-1. Example of the length modifier


<?php
'sometext' = array(
    'type'   = 'text',
    'length' = 12,
),
?>


 
 

 <br \><br \>
The above example will create a character varying field of length 12 characters in the database table. If the length definition is left out, MDB2 will create a length of the maximum allowable length for the data type specified, which may create a problem with some field types and indexing. Best practice is to define lengths for all or most of your fields. 

