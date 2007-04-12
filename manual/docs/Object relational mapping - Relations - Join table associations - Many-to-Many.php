If you are coming from relational database background it may be familiar to you
how many-to-many associations are handled: an additional association table is needed.
<br \><br \>
In many-to-many relations the relation between the two components is always an aggregate 
relation and the association table is owned by both ends. For example in the case of users and groups
when user is being deleted the groups it belongs to are not being deleted and the associations between this user
and the groups it belongs to are being deleted.
<br \><br \>
Sometimes you may not want that association table rows are being deleted when user / group is being deleted. You can override
this behoviour by setting the relations to association component (in this case Groupuser) explicitly. 
<br \><br \>
In the following example we have Groups and Users of which relation is defined as 
many-to-many. In this case we also need to define an additional class called Groupuser.


<code type="php">
class User extends Doctrine_Record { 
    public function setUp() {
        $this->hasMany('Group','Groupuser.group_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('name','string',30);
    }
}

class Group extends Doctrine_Record {
    public function setUp() {
        $this->hasMany('User','Groupuser.user_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('name','string',30);
    }
}

class Groupuser extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn('user_id','integer');
        $this->hasColumn('group_id','integer');
    }
}


$user = new User();

// add two groups
$user->Group[0]->name = 'First Group';

$user->Group[1]->name = 'Second Group';

// save changes into database
$user->save();

// deleting the associations between user and groups it belongs to

$user->Groupuser->delete();

$groups = new Doctrine_Collection($conn->getTable('Group'));

$groups[0]->name = 'Third Group';

$groups[1]->name = 'Fourth Group';

$user->Group[2] = $groups[0];
// $user will now have 3 groups

$user->Group = $groups;
// $user will now have two groups 'Third Group' and 'Fourth Group'

</code>
