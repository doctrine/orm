Doctrine_Record is the basic component of every doctrine-based project.
There should be atleast one Doctrine_Record for each of your database tables.
Doctrine_Record follows the [http://www.martinfowler.com/eaaCatalog/activeRecord.html Active Record pattern]

Doctrine auto-creates database tables and always adds a primary key column named 'id' to tables that doesn't have any primary keys specified. Only thing you need to for creating database tables is defining a class which extends Doctrine_Record and setting a setTableDefinition method with hasColumn() method calls.

An short example:

We want to create a database table called 'user' with columns id(primary key), name, username, password and created. Provided that you have already installed Doctrine these few lines of code are all you need:

<code type='php'>
require_once('lib/Doctrine.php');

spl_autoload_register(array('Doctrine', 'autoload'));

class User extends Doctrine_Record { 
    public function setTableDefinition() {
        // set 'user' table columns, note that
        // id column is always auto-created
        
        $this->hasColumn('name','string',30);
        $this->hasColumn('username','string',20);
        $this->hasColumn('password','string',16);
        $this->hasColumn('created','integer',11);
    }
}
</code>

We now have a user model that supports basic CRUD opperations! 
