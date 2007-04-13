When it comes to handling inheritance Doctrine is very smart.
In the following example we have one database table called 'entity'.
Users and groups are both entities and they share the same database table.
The only thing we have to make is 3 records (Entity, Group and User).



Doctrine is smart enough to know that the inheritance type here is one-table-many-classes.


<code type="php">
class Entity extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name','string',30);
        $this->hasColumn('username','string',20);
        $this->hasColumn('password','string',16);
        $this->hasColumn('created','integer',11);                                     	
    }
}

class User extends Entity { }

class Group extends Entity { }
</code>
