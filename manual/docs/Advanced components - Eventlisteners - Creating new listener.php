Creating a new listener is very easy. You can set the listener in global, connection or factory level.

<code type="php">
class MyListener extends Doctrine_EventListener {
    public function onLoad(Doctrine_Record $record) {
        print $record->getTable()->getComponentName()." just got loaded!";
    }
    public function onSave(Doctrine_Record $record) {
        print "saved data access object!";
    }
}
class MyListener2 extends Doctrine_EventListener {
    public function onPreUpdate() {
        try {
            $record->set("updated",time());
        } catch(InvalidKeyException $e) { 
        }
    }
}


// setting global listener
$manager = Doctrine_Manager::getInstance();

$manager->setAttribute(Doctrine::ATTR_LISTENER,new MyListener());

// setting connection level listener
$conn = $manager->openConnection($dbh);

$conn->setAttribute(Doctrine::ATTR_LISTENER,new MyListener2());

// setting factory level listener
$table = $conn->getTable("User");

$table->setAttribute(Doctrine::ATTR_LISTENER,new MyListener());
</code>
