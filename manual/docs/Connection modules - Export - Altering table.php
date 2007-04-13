<?php ?>
Doctrine_Export drivers provide an easy database portable way of altering existing database tables.



NOTE: if you only want to get the generated sql (and not execute it) use Doctrine_Export::alterTableSql() 



<code type="php">
\$dbh  = new PDO('dsn','username','pw');
\$conn = Doctrine_Manager::getInstance()
         ->openConnection(\$dbh);

\$a    = array('add' => array('name' => array('type' => 'string', 'length' => 255)));


\$conn->export->alterTableSql('mytable', \$a);

// On mysql this method returns: 
// ALTER TABLE mytable ADD COLUMN name VARCHAR(255)
?></code>



Doctrine_Export::alterTable() takes two parameters:



: string //$name// : name of the table that is intended to be changed. 

: array //$changes// : associative array that contains the details of each type of change that is intended to be performed.
The types of changes that are currently supported are defined as follows:

* //name//
New name for the table.

* //add//

Associative array with the names of fields to be added as indexes of the array. The value of each entry of the array should be set to another associative array with the properties of the fields to be added. The properties of the fields should be the same as defined by the Doctrine parser.

* //remove//

Associative array with the names of fields to be removed as indexes of the array. Currently the values assigned to each entry are ignored. An empty array should be used for future compatibility.

* //rename//

Associative array with the names of fields to be renamed as indexes of the array. The value of each entry of the array should be set to another associative array with the entry named name with the new field name and the entry named Declaration that is expected to contain the portion of the field declaration already in DBMS specific SQL code as it is used in the CREATE TABLE statement.

* //change//

Associative array with the names of the fields to be changed as indexes of the array. Keep in mind that if it is intended to change either the name of a field and any other properties, the change array entries should have the new names of the fields as array indexes.


The value of each entry of the array should be set to another associative array with the properties of the fields to that are meant to be changed as array entries. These entries should be assigned to the new values of the respective properties. The properties of the fields should be the same as defined by the Doctrine parser.


<code type="php">
$a = array('name' => 'userlist',
           'add' => array(
                    'quota' => array(
                        'type' => 'integer',
                        'unsigned' => 1
                        )
                    ),
            'remove' => array(
                    'file_limit' => array(),
                    'time_limit' => array()
                    ),
            'change' => array(
                    'name' => array(
                        'length' => '20',
                        'definition' => array(
                            'type' => 'text',
                            'length' => 20
                            )
                        )
                    ),
            'rename' => array(
                    'sex' => array(
                        'name' => 'gender',
                        'definition' => array(
                            'type' => 'text',
                            'length' => 1,
                            'default' => 'M'
                            )
                        )
                    )
            
            );

$dbh  = new PDO('dsn','username','pw');
$conn = Doctrine_Manager::getInstance()->openConnection($dbh);

$conn->export->alterTable('mytable', $a);
</code>
