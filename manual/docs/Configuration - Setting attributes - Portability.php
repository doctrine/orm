<?php ?>
Each database management system (DBMS) has it's own behaviors. For example, some databases capitalize field names in their output, some lowercase them, while others leave them alone. These quirks make it difficult to port your scripts over to another server type. PEAR Doctrine:: strives to overcome these differences so your program can switch between DBMS's without any changes.

You control which portability modes are enabled by using the portability configuration option. Configuration options are set via factory() and setOption().

The portability modes are bitwised, so they can be combined using | and removed using ^. See the examples section below on how to do this.



Portability Mode Constants
 



//Doctrine::PORTABILITY_ALL (default)//



turn on all portability features. this is the default setting. 



//Doctrine::PORTABILITY_DELETE_COUNT//



Force reporting the number of rows deleted. Some DBMS's don't count the number of rows deleted when performing simple DELETE FROM tablename queries. This mode tricks such DBMS's into telling the count by adding WHERE 1=1 to the end of DELETE queries. 



//Doctrine::PORTABILITY_EMPTY_TO_NULL//
 


Convert empty strings values to null in data in and output. Needed because Oracle considers empty strings to be null, while most other DBMS's know the difference between empty and null. 



//Doctrine::PORTABILITY_ERRORS//



Makes certain error messages in certain drivers compatible with those from other DBMS's 




Table 33-1. Error Code Re-mappings

Driver Description Old Constant New Constant 
mysql, mysqli  unique and primary key constraints  Doctrine::ERROR_ALREADY_EXISTS  Doctrine::ERROR_CONSTRAINT  
mysql, mysqli  not-null constraints  Doctrine::ERROR_CONSTRAINT  Doctrine::ERROR_CONSTRAINT_NOT_NULL  




//Doctrine::PORTABILITY_FIX_ASSOC_FIELD_NAMES// 



This removes any qualifiers from keys in associative fetches. some RDBMS , like for example SQLite, will be default use the fully qualified name for a column in assoc fetches if it is qualified in a query. 



//Doctrine::PORTABILITY_FIX_CASE//



Convert names of tables and fields to lower or upper case in all methods. The case depends on the 'field_case' option that may be set to either CASE_LOWER (default) or CASE_UPPER 



//Doctrine::PORTABILITY_NONE//
 


Turn off all portability features 



//Doctrine::PORTABILITY_NUMROWS//



Enable hack that makes numRows() work in Oracle 



//Doctrine::PORTABILITY_RTRIM//



Right trim the data output for all data fetches. This does not applied in drivers for RDBMS that automatically right trim values of fixed length character values, even if they do not right trim value of variable length character values.




 
Using setAttribute() to enable portability for lowercasing and trimming
 


<code type="php">
\$conn->setAttribute('portability',
        Doctrine::PORTABILITY_FIX_CASE | Doctrine::PORTABILITY_RTRIM);

?></code>


 
 


Using setAttribute() to enable all portability options except trimming
 



<code type="php">
\$conn->setAttribute('portability',
        Doctrine::PORTABILITY_ALL ^ Doctrine::PORTABILITY_RTRIM);
?></code>

