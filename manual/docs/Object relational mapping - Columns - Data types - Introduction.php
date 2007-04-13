All DBMS provide multiple choice of data types for the information that can be stored in their database table fields. 
However, the set of data types made available varies from DBMS to DBMS.

 

To simplify the interface with the DBMS supported by Doctrine, it was defined a base set of data types 
that applications may access independently of the underlying DBMS.



The Doctrine applications programming interface takes care of mapping data types 
when managing database options. It is also able to convert that is sent to and received from the underlying DBMS using the respective driver.



The following data type examples should be used with Doctrine's createTable() method. 
The example array at the end of the data types section may be used with createTable() 
to create a portable table on the DBMS of choice (please refer to the main Doctrine 
documentation to find out what DBMS back ends are properly supported). 
It should also be noted that the following examples do not cover the creation 
and maintenance of indices, this chapter is only concerned with data types and the proper usage thereof.



It should be noted that the length of the column affects in database level type as well as application level validated length (the length that is validated with Doctrine validators).



Example 1. Column named 'content' with type 'string' and length 3000 results in database type 'TEXT' of which has database level length of 4000. However when the record is validated it is only allowed to have 'content' -column with maximum length of 3000.



Example 2. Column with type 'integer' and length 1 results in 'TINYINT' on many databases.



In general Doctrine is smart enough to know which integer/string type to use depending on the specified length.


