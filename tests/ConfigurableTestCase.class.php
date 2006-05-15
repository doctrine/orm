<?php
require_once("UnitTestCase.class.php");

class Doctrine_ConfigurableTestCase extends Doctrine_UnitTestCase {
    public function prepareTables() { }
    public function prepareData() { }
    public function testSetAttribute() {
        $table = $this->session->getTable("User");

        $this->manager->setAttribute(Doctrine::ATTR_CACHE_TTL,100);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_CACHE_TTL),100);
    
        $this->manager->setAttribute(Doctrine::ATTR_CACHE_SIZE,1);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_CACHE_SIZE),1);

        $this->manager->setAttribute(Doctrine::ATTR_CACHE_DIR,"%ROOT%".DIRECTORY_SEPARATOR."cache");
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_CACHE_DIR),$this->manager->getRoot().DIRECTORY_SEPARATOR."cache");

        $this->manager->setAttribute(Doctrine::ATTR_FETCHMODE,Doctrine::FETCH_LAZY);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_FETCHMODE),Doctrine::FETCH_LAZY);

        $this->manager->setAttribute(Doctrine::ATTR_BATCH_SIZE, 5);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_BATCH_SIZE),5);

        $this->manager->setAttribute(Doctrine::ATTR_LISTENER, new Doctrine_Debugger());
        $this->assertTrue($this->manager->getAttribute(Doctrine::ATTR_LISTENER) instanceof Doctrine_Debugger);

        $this->manager->setAttribute(Doctrine::ATTR_PK_COLUMNS, array("id"));
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_PK_COLUMNS), array("id"));

        $this->manager->setAttribute(Doctrine::ATTR_PK_TYPE, Doctrine::INCREMENT_KEY);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_PK_TYPE), Doctrine::INCREMENT_KEY);

        $this->manager->setAttribute(Doctrine::ATTR_LOCKMODE, Doctrine::LOCK_PESSIMISTIC);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_LOCKMODE), Doctrine::LOCK_PESSIMISTIC);

        // test invalid arguments
        try {
            $this->manager->setAttribute(Doctrine::ATTR_CACHE_TTL,-12);
        } catch(Exception $e) {
            $this->assertTrue($e instanceof Exception);
        }
        try {
            $this->manager->setAttribute(Doctrine::ATTR_CACHE_SIZE,-12);
        } catch(Exception $e) {
            $this->assertTrue($e instanceof Exception);
        }
        try {
            $this->manager->setAttribute(Doctrine::ATTR_BATCH_SIZE,-12);
        } catch(Exception $e) {
            $this->assertTrue($e instanceof Exception);
        }

        try {
            $this->session->beginTransaction();
            $this->manager->setAttribute(Doctrine::ATTR_LOCKMODE, Doctrine::LOCK_OPTIMISTIC);
        } catch(Exception $e) {
            $this->assertTrue($e instanceof Exception);
            $this->session->commit();
        }

        $e = false;
        try {
            $this->manager->setAttribute(Doctrine::ATTR_COLL_KEY, "name");
        } catch(Exception $e) {
        }
        $this->assertTrue($e instanceof Exception);

        $e = false;
        try {
            $table->setAttribute(Doctrine::ATTR_COLL_KEY, "unknown");
        } catch(Exception $e) {
        }
        $this->assertTrue($e instanceof Exception);

        $e = true;
        try {
            $table->setAttribute(Doctrine::ATTR_COLL_KEY, "name");
        } catch(Exception $e) {
        }
        $this->assertTrue($e);

        $e = false;
        try {
            $this->session->beginTransaction();
            $this->session->setAttribute(Doctrine::ATTR_LOCKMODE, Doctrine::LOCK_PESSIMISTIC);
        } catch(Exception $e) {
            $this->assertTrue($e instanceof Exception);
            $this->session->commit();
        }
        try {
            $this->manager->setAttribute(Doctrine::ATTR_PK_TYPE,-12);
        } catch(Exception $e) {
            $this->assertTrue($e instanceof Exception);
        }
    }
    public function testGetAttributes() {
        $this->assertTrue(is_array($this->manager->getAttributes()));
    }
}
?>
