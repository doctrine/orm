In the following example we have one database table called 'entity'.
Users and groups are both entities and they share the same database table.
<br \><br \>
The entity table has a column called 'type' which tells whether an entity is a group or a user.
Then we decide that users are type 1 and groups type 2.

The only thing we have to do is to create 3 records (the same as before) and add
call the Doctrine_Table::setInheritanceMap() method inside the setUp() method.
