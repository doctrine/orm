Following data types are availible in doctrine:
    
    <li />** string **
        <dd /> The same as type 'string' in php
    <li />** float / double**
        <dd /> The same as type 'float' in php

    <li />** integer**
        <dd /> The same as type 'integer' in php

    <li />** boolean **
        <dd /> The same as type 'boolean' in php

    <li />** array **
         The same as type 'array' in php. Automatically serialized when saved into database and unserialized when retrieved from database.
    <li />** object **
         The same as type 'object' in php. Automatically serialized when saved into database and unserialized when retrieved from database.
    <li />** enum **
         
    <li />** timestamp **
        <dd /> Database 'timestamp' type
    <li />** clob**
        <dd /> Database 'clob' type
    <li />** blob**
        <dd /> Database 'blob' type
    <li />** date **
        <dd /> Database 'date' type
    

It should be noted that the length of the column affects in database level type
as well as application level validated length (the length that is validated with Doctrine validators).



Example 1. Column named 'content' with type 'string' and length 3000 results in database type 'TEXT' of which has database level length of 4000.
However when the record is validated it is only allowed to have 'content' -column with maximum length of 3000.



Example 2. Column with type 'integer' and length 1 results in 'TINYINT' on many databases. 




In general Doctrine is smart enough to know which integer/string type to use depending on the specified length.


  

<code type="php">
class Article extends Doctrine_Record {
    public function setTableDefinition() {
        // few mapping examples:

        // maps into VARCHAR(100) on mysql
        $this->hasColumn("title","string",100);
        
        // maps into TEXT on mysql
        $this->hasColumn("content","string",4000);

        // maps into TINYINT on mysql
        $this->hasColumn("type","integer",1);

        // maps into INT on mysql
        $this->hasColumn("type2","integer",11);

        // maps into BIGINT on mysql
        $this->hasColumn("type3","integer",20);

        // maps into TEXT on mysql
        // (serialized and unserialized automatically by doctrine)
        $this->hasColumn("types","array",4000);

    }
}
</code>
