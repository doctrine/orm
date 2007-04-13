In the following example we make a user management system where



1. Each user and group are entities



2. User is an entity of type 0



3. Group is an entity of type 1



4. Each entity (user/group) has 0-1 email



5. Each entity has 0-* phonenumbers



6. If an entity is saved all its emails and phonenumbers are also saved



7. If an entity is deleted all its emails and phonenumbers are also deleted



8. When an entity is created and saved a current timestamp will be assigned to 'created' field



9. When an entity is updated a current timestamp will be assigned to 'updated' field



10. Entities will always be fetched in batches

<code type="php">
class Entity extends Doctrine_Record {
    public function setUp() {
        $this->ownsOne("Email","Entity.email_id");
        $this->ownsMany("Phonenumber","Phonenumber.entity_id");
        $this->setAttribute(Doctrine::ATTR_FETCHMODE,Doctrine::FETCH_BATCH);
        $this->setAttribute(Doctrine::ATTR_LISTENER,new EntityListener());
    }
    public function setTableDefinition() {
        $this->hasColumn("name","string",50);
        $this->hasColumn("loginname","string",20);
        $this->hasColumn("password","string",16);
        $this->hasColumn("type","integer",1);
        $this->hasColumn("created","integer",11);
        $this->hasColumn("updated","integer",11);
        $this->hasColumn("email_id","integer");
    }
}
class Group extends Entity {
    public function setUp() {
        parent::setUp();
        $this->hasMany("User","Groupuser.user_id");
        $this->setInheritanceMap(array("type"=>1));
    }
}
class User extends Entity {
    public function setUp() {
        parent::setUp();
        $this->hasMany("Group","Groupuser.group_id");
        $this->setInheritanceMap(array("type"=>0));
    }
}
class Groupuser extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn("group_id","integer");
        $this->hasColumn("user_id","integer");
    }
}

class Phonenumber extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn("phonenumber","string",20);
        $this->hasColumn("entity_id","integer");
    }
}
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("address","string",150,"email|unique");
    }
}
class EntityListener extends Doctrine_EventListener {
    public function onPreUpdate(Doctrine_Record $record) {
        $record->updated = time();
    }
    public function onPreInsert(Doctrine_Record $record) {
        $record->created  = time();
    }
}

// USER MANAGEMENT SYSTEM IN ACTION:

$manager = Doctrine_Manager::getInstance();

$conn = $manager->openConnection(new PDO("DSN","username","password"));

$user = new User();

$user->name = "Jack Daniels";
$user->Email->address = "jackdaniels@drinkmore.info";
$user->Phonenumber[0]->phonenumber = "123 123";
$user->Phonenumber[1]->phonenumber = "133 133";
$user->save();

$user->Group[0]->name = "beer lovers";
$user->Group[0]->Email->address = "beerlovers@drinkmore.info";
$user->Group[0]->save();

</code>
