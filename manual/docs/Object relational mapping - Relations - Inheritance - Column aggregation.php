In the following example we have one database table called 'entity'. Users and groups are both entities and they share the same database table.

The entity table has a column called 'type' which tells whether an entity is a group or a user. Then we decide that users are type 1 and groups type 2.

The only thing we have to do is to create 3 records (the same as before) and add call the Doctrine_Table::setInheritanceMap() method inside the setUp() method.

<code type='php'>
class Entity extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name','string',30);
        $this->hasColumn('username','string',20);
        $this->hasColumn('password','string',16);
        $this->hasColumn('created','integer',11);

        // this column is used for column
        // aggregation inheritance
        $this->hasColumn('type', 'integer', 11);
    }
}

class User extends Entity {
    public function setUp() {
        $this->setInheritanceMap(array('type'=>1));
    }
}

class Group extends Entity {
    public function setUp() {
        $this->setInheritanceMap(array('type'=>2));
    }
}
</code>

If we want to be able to fetch a record from the Entity table and automatically get a User record if the Entity we fetched is a user we have to do set the subclasses option in the parent class. The adjusted example:

<code type='php'>
class Entity extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name','string',30);
        $this->hasColumn('username','string',20);
        $this->hasColumn('password','string',16);
        $this->hasColumn('created','integer',11);

        // this column is used for column
        // aggregation inheritance
        $this->hasColumn('type', 'integer', 11);
        $this->option('subclasses', array('User', 'Group'));
    }
}

class User extends Entity {
    public function setUp() {
        $this->setInheritanceMap(array('type'=>1));
    }
}

class Group extends Entity {
    public function setUp() {
        $this->setInheritanceMap(array('type'=>2));
    }
}
</code>

We can then do the following given the previous table mapping.

<code type='php'>
$user = new User();
$user->name='Bjarte S. Karlsen';
$user->username='meus';
$user->password='rat';
$user->save();

$group = new Group();
$group->name='Users';
$group->username='users';
$group->password='password';
$group->save();

$q = new Doctrine_Query();
$user = $q->from('Entity')->where('id=?')->execute(array($user->id))->getFirst();

$q = new Doctrine_Query();
$group = $q->from('Entity')->where('id=?')->execute(array($group->id))->getFirst();
</code>

The user object is here an instance of User while the group object is an instance of Group.
