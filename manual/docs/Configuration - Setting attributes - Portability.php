<?php ?>
Each database management system (DBMS) has it's own behaviors. For example, some databases capitalize field names in their output, some lowercase them, while others leave them alone. These quirks make it difficult to port your scripts over to another server type. PEAR Doctrine:: strives to overcome these differences so your program can switch between DBMS's without any changes.

You control which portability modes are enabled by using the portability configuration option. Configuration options are set via factory() and setOption().

The portability modes are bitwised, so they can be combined using | and removed using ^. See the examples section below on how to do this.
<br \><br \>
Portability Mode Constants
 <br \><br \>

<i>Doctrine::PORTABILITY_ALL (default)</i>
<br \><br \>
turn on all portability features. this is the default setting. 
<br \><br \>
<i>Doctrine::PORTABILITY_DELETE_COUNT</i>
<br \><br \>
Force reporting the number of rows deleted. Some DBMS's don't count the number of rows deleted when performing simple DELETE FROM tablename queries. This mode tricks such DBMS's into telling the count by adding WHERE 1=1 to the end of DELETE queries. 
<br \><br \>
<i>Doctrine::PORTABILITY_EMPTY_TO_NULL</i>
 <br \><br \>
Convert empty strings values to null in data in and output. Needed because Oracle considers empty strings to be null, while most other DBMS's know the difference between empty and null. 
<br \><br \>
<i>Doctrine::PORTABILITY_ERRORS</i>
<br \><br \>
Makes certain error messages in certain drivers compatible with those from other DBMS's 
<br \><br \>

Table 33-1. Error Code Re-mappings

Driver Description Old Constant New Constant 
mysql, mysqli  unique and primary key constraints  Doctrine::ERROR_ALREADY_EXISTS  Doctrine::ERROR_CONSTRAINT  
mysql, mysqli  not-null constraints  Doctrine::ERROR_CONSTRAINT  Doctrine::ERROR_CONSTRAINT_NOT_NULL  

<br \><br \>
<i>Doctrine::PORTABILITY_FIX_ASSOC_FIELD_NAMES</i> 
<br \><br \>
This removes any qualifiers from keys in associative fetches. some RDBMS , like for example SQLite, will be default use the fully qualified name for a column in assoc fetches if it is qualified in a query. 
<br \><br \>
<i>Doctrine::PORTABILITY_FIX_CASE</i>
<br \><br \>
Convert names of tables and fields to lower or upper case in all methods. The case depends on the 'field_case' option that may be set to either CASE_LOWER (default) or CASE_UPPER 
<br \><br \>
<i>Doctrine::PORTABILITY_NONE</i>
 <br \><br \>
Turn off all portability features 
<br \><br \>
<i>Doctrine::PORTABILITY_NUMROWS</i>
<br \><br \>
Enable hack that makes numRows() work in Oracle 
<br \><br \>
<i>Doctrine::PORTABILITY_RTRIM</i>
<br \><br \>
Right trim the data output for all data fetches. This does not applied in drivers for RDBMS that automatically right trim values of fixed length character values, even if they do not right trim value of variable length character values.
<br \><br \>

 
Using setAttribute() to enable portability for lowercasing and trimming
 <br \><br \>
<?php
renderCode("<?php
\$conn->setAttribute('portability',
        Doctrine::PORTABILITY_FIX_CASE | Doctrine::PORTABILITY_RTRIM);

?>");
?>


 
 <br \><br \>
Using setAttribute() to enable all portability options except trimming
 <br \><br \>

<?php
renderCode("<?php
\$conn->setAttribute('portability',
        Doctrine::PORTABILITY_ALL ^ Doctrine::PORTABILITY_RTRIM);
?>");
?>

