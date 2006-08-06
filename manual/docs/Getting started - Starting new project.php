Doctrine_Record is the basic component of every doctrine-based project.
There should be atleast one Doctrine_Record for each of your database tables.
Doctrine_Record follows <a href="http://www.martinfowler.com/eaaCatalog/activeRecord.html">Active Record pattern</a>.
<br \><br \>
Doctrine auto-creates database tables and always adds a primary key column named 'id' to tables that doesn't have any primary keys specified. Only thing you need to for creating database tables
is defining a class which extends Doctrine_Record and setting a setTableDefinition method with hasColumn() method calls.
<br \><br \>
Consider we want to create a database table called 'user' with columns id(primary key), name, username, password and created. You only need couple of lines of code
to create a simple up-and-running model.

