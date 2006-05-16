<?php
require_once("../classes/Doctrine.class.php");
Doctrine::loadAll();

class Sensei_UnitTestCase extends UnitTestCase { 
    protected $manager;
    protected $session;
    protected $dbh;
    protected $listener;
    protected $users;
    protected $tables;

    private $init = false;

    public function init() {
        $this->manager   = Doctrine_Manager::getInstance();
        $this->manager->setAttribute(Doctrine::ATTR_CACHE, Doctrine::CACHE_NONE);

        if($this->manager->count() > 0) {
            $this->session = $this->manager->getSession(0);
            $this->session->clear();
            $this->dbh     = $this->session->getDBH();
            $this->listener = $this->manager->getAttribute(Doctrine::ATTR_LISTENER);
        } else {
            $this->dbh     = Doctrine_DB::getConnection();
            $this->session = $this->manager->openSession($this->dbh);
        }



        $this->tables = array("sensei_group","sensei_user","sensei_entity","sensei_session","sensei_variable");
        $tables = $this->tables;
        foreach($tables as $name) {
            $this->dbh->query("DROP TABLE IF EXISTS $name");
        }
        
        $this->sensei = new Sensei();
        
        $entity = new Sensei_Entity();
        
        $entity->loginname = "Chuck Norris";
        $entity->password = "toughguy";
        
        $entity->save();

        $this->init   = true;
        
        $this->record = $this->sensei->getRecord();
    }
    public function setUp() {
        if( ! $this->init)
            $this->init();
    }

    public function testConstructor() {   
        $this->assertTrue($this->record instanceof Sensei_Session);

        if(isset($_COOKIE["PHPSESSID"])) {
            $this->assertEqual($this->record->session_id, $_COOKIE["PHPSESSID"]);
        }
        $updated = $this->record->updated;
        $this->assertFalse(empty($updated));
        $created = $this->record->created;
        $this->assertFalse(empty($created));

        $this->assertEqual($this->record->user_agent, $_SERVER['HTTP_USER_AGENT']);

        // make the changes persistent

        $this->sensei->flush();

        if(isset($_COOKIE["PHPSESSID"])) {
            $this->assertEqual($this->record->session_id, $_COOKIE["PHPSESSID"]);
        }
        $updated = $this->record->updated;
        $this->assertFalse(empty($updated));
        $created = $this->record->created;
        $this->assertFalse(empty($created));

        $this->assertEqual($this->record->user_agent, $_SERVER['HTTP_USER_AGENT']);
    }

    public function testLogin() {

        $this->assertFalse($this->sensei->login('Chuck Norris','unknown'));
        $this->assertEqual($this->record->logged_in, null);

        $this->assertEqual($this->record->entity_id, null);

        $this->assertTrue($this->sensei->login('Chuck Norris','toughguy'));
        $this->assertEqual($this->record->logged_in, 1);

        $this->assertEqual($this->record->entity_id, 1);
    }

    public function testLogout() {
        $this->assertTrue($this->sensei->logout());
        $this->assertEqual($this->record->logged_in, 0);

        $this->assertEqual($this->record->entity_id, 0);
        $this->assertEqual($this->record->getState(), Doctrine_Record::STATE_DIRTY);
        $this->assertEqual($this->record->getTable()->getIdentifierType(), Doctrine_Identifier::AUTO_INCREMENT);

        $this->assertEqual($this->record->getID(), 1);

        $this->sensei->flush();

        $this->assertEqual($this->record->logged_in, 0);

        $this->assertEqual($this->record->entity_id, 0);
    }

}
?>
