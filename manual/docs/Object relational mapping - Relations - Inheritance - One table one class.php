In the following example we have three database tables called 'entity', 'user' and 'group'.
Users and groups are both entities.
The only thing we have to do is write 3 classes (Entity, Group and User) and make iterative
setTableDefinition method calls.




<code type="php">
class Entity extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn('name','string',30);
        $this->hasColumn('username','string',20);
        $this->hasColumn('password','string',16);
        $this->hasColumn('created','integer',11);
    }
}

class User extends Entity { 
    public function setTableDefinition() {
        // the following method call is needed in
        // one-table-one-class inheritance
        parent::setTableDefinition();
    }
}

class Group extends Entity {
    public function setTableDefinition() {
        // the following method call is needed in
        // one-table-one-class inheritance
        parent::setTableDefinition();
    }
}
</code>
