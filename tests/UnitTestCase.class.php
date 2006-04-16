<?php
require_once("../classes/Doctrine.class.php");

/**
function __autoload($class) {
    Doctrine::autoload($class);
}
*/


Doctrine::loadAll();

require_once("classes.php");

ini_set('include_path',ucwords($_SERVER["DOCUMENT_ROOT"]));
require_once("simpletest/unit_tester.php");
require_once("simpletest/reporter.php");


class Doctrine_UnitTestCase extends UnitTestCase {
    protected $manager;
    protected $session;
    protected $objTable;
    protected $repository;
    protected $new;
    protected $old;
    protected $dbh;
    protected $listener;
    protected $cache;
    protected $users;
    protected $tables;

    private $init = false;

    public function init() {
        $name = get_class($this);

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
            $this->listener = new Doctrine_Debugger();
            $this->manager->setAttribute(Doctrine::ATTR_LISTENER, $this->listener);
        }

        $this->tables = array("entity","email","phonenumber","groupuser","album","song","element","error","description");
        $tables = $this->tables;
        foreach($tables as $name) {
            $this->dbh->query("DROP TABLE IF EXISTS $name");
        }

        foreach($tables as $name) {
            $table = $this->session->getTable($name);

            $table->getCache()->deleteAll();
        }


        $this->objTable = $this->session->getTable("User");
        $this->repository = $this->objTable->getRepository();
        //$this->cache = $this->objTable->getCache();

        $this->prepareData();
    }
    public function prepareData() {
        $groups = new Doctrine_Collection($this->session->getTable("Group"));

        $groups[0]->name = "Drama Actors";
        $groups[0]->save();

        $groups[1]->name = "Quality Actors";
        $groups[1]->save();

        $groups[2]->name = "Action Actors";
        $groups[2]["Phonenumber"][0]->phonenumber = "123 123";
        $groups[2]->save();

        $users = new Doctrine_Collection($this->session->getTable("User"));


        $users[0]->name = "zYne";
        $users[0]["Email"]->address = "zYne@example.com";
        $users[0]["Phonenumber"][0]->phonenumber = "123 123";

        $users[1]->name = "Arnold Schwarzenegger";
        $users[1]->Email->address = "arnold@example.com";
        $users[1]["Phonenumber"][0]->phonenumber = "123 123";
        $users[1]["Phonenumber"][1]->phonenumber = "456 456";
        $users[1]->Phonenumber[2]->phonenumber = "789 789";
        $users[1]->Group[0] = $groups[2];

        $users[2]->name = "Michael Caine";
        $users[2]->Email->address = "caine@example.com";
        $users[2]->Phonenumber[0]->phonenumber = "123 123";

        $users[3]->name = "Takeshi Kitano";
        $users[3]->Email->address = "kitano@example.com";
        $users[3]->Phonenumber[0]->phonenumber = "111 222 333";

        $users[4]->name = "Sylvester Stallone";
        $users[4]->Email->address = "stallone@example.com";
        $users[4]->Phonenumber[0]->phonenumber = "111 555 333";
        $users[4]["Phonenumber"][1]->phonenumber = "123 213";
        $users[4]["Phonenumber"][2]->phonenumber = "444 555";

        $users[5]->name = "Kurt Russell";
        $users[5]->Email->address = "russell@example.com";
        $users[5]->Phonenumber[0]->phonenumber = "111 222 333";

        $users[6]->name = "Jean Reno";
        $users[6]->Email->address = "reno@example.com";
        $users[6]->Phonenumber[0]->phonenumber = "111 222 333";
        $users[6]["Phonenumber"][1]->phonenumber = "222 123";
        $users[6]["Phonenumber"][2]->phonenumber = "123 456";

        $users[7]->name = "Edward Furlong";
        $users[7]->Email->address = "furlong@example.com";
        $users[7]->Phonenumber[0]->phonenumber = "111 567 333";

        $this->users = $users;
        $this->session->flush();
    }
    public function getSession() {
        return $this->session;
    }
    public function clearCache() {
        foreach($this->tables as $name) {
            $table = $this->session->getTable($name);
            $table->getCache()->deleteAll();
        }
    }
    public function setUp() {
        if( ! $this->init) $this->init(); 
        
        $this->init    = true;
        $this->new     = $this->objTable->create();
        $this->old     = $this->objTable->find(4);
    }
}
?>
