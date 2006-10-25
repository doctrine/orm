<?php
class Doctrine_Record_State_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() { }
    public function prepareData() { }
    
    public function testAssignUnknownState() {
        $user = new User();
        try {
            $user->state(123123);
            $this->fail();
        } catch(Doctrine_Record_State_Exception $e) {
            $this->pass();
        }
        $this->assertEqual($user->state(), Doctrine_Record::STATE_TCLEAN);
        try {
            $user->state('some unknown state');
            $this->fail();
        } catch(Doctrine_Record_State_Exception $e) {
            $this->pass();
        }
        $this->assertEqual($user->state(), Doctrine_Record::STATE_TCLEAN);
    }

    public function testAssignDirtyState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_DIRTY);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_DIRTY);
        
        $user->state('dirty');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_DIRTY);
    }
    public function testAssignCleanState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_CLEAN);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_CLEAN);
        
        $user->state('clean');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_CLEAN);
    }
    public function testAssignTransientCleanState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_TCLEAN);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_TCLEAN);
        
        $user->state('tclean');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_TCLEAN);
    }
    public function testAssignTransientDirtyState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_TDIRTY);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_TDIRTY);
        
        $user->state('tdirty');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_TDIRTY);
    }
    public function testAssignProxyState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_PROXY);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_PROXY);
        
        $user->state('proxy');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_PROXY);
    }
}
?>
