<pre>
InvalidKeyException

Doctrine_Exception

DQLException

Doctrine_PrimaryKey_Exception          thrown when Doctrine_Record is loaded and there is no primary key field

Doctrine_Refresh_Exception             thrown when Doctrine_Record is refreshed and the refreshed primary key doens't match the old one

Doctrine_Find_Exception                thrown when user tries to find a Doctrine_Record for given primary key and that object is not found

Doctrine_Naming_Exception              thrown when user defined Doctrine_Table is badly named


Doctrine_Session_Exception             thrown when user tries to get the current
                                       session and there are no open sessions

Doctrine_Table_Exception               thrown when user tries to initialize a new instance of Doctrine_Table,
                                       while there already exists an instance of that factory

Doctrine_Mapping_Exception             thrown when user tries to get a foreign key object but the mapping is not done right
</pre>
