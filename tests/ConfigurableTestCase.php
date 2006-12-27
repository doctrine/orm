<?php
require_once("UnitTestCase.php");

class Doctrine_Configurable_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() { }
    public function prepareData() { }

    public function testGetIndexNameFormatAttribute() {
        // default index name format is %_idx
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_IDXNAME_FORMAT), '%s_idx');
    }
    public function testGetSequenceNameFormatAttribute() {
        // default sequence name format is %_seq
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_SEQNAME_FORMAT), '%s_seq');
    }
    public function testSetIndexNameFormatAttribute() {
        $this->manager->setAttribute(Doctrine::ATTR_IDXNAME_FORMAT, '%_index');

        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_IDXNAME_FORMAT), '%_index');
    }
    public function testSetSequenceNameFormatAttribute() {
        $this->manager->setAttribute(Doctrine::ATTR_SEQNAME_FORMAT, '%_sequence');

        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_SEQNAME_FORMAT), '%_sequence');
    }
    public function testExceptionIsThrownWhenSettingIndexNameFormatAttributeAtTableLevel() {
        try {
            $this->connection->getTable('Entity')->setAttribute(Doctrine::ATTR_IDXNAME_FORMAT, '%s_idx');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testExceptionIsThrownWhenSettingSequenceNameFormatAttributeAtTableLevel() {
        try {
            $this->connection->getTable('Entity')->setAttribute(Doctrine::ATTR_SEQNAME_FORMAT, '%s_seq');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testSettingFieldCaseIsSuccesfulWithZero() {
        try {
            $this->connection->setAttribute(Doctrine::ATTR_FIELD_CASE, 0);
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
    }
    public function testSettingFieldCaseIsSuccesfulWithCaseConstants() {
        try {
            $this->connection->setAttribute(Doctrine::ATTR_FIELD_CASE, CASE_LOWER);
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
    }
    public function testSettingFieldCaseIsSuccesfulWithCaseConstants2() {
        try {
            $this->connection->setAttribute(Doctrine::ATTR_FIELD_CASE, CASE_UPPER);
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
    }
    public function testExceptionIsThrownWhenSettingFieldCaseToNotZeroOneOrTwo() {
        try {
            $this->connection->setAttribute(Doctrine::ATTR_FIELD_CASE, -1);
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testExceptionIsThrownWhenSettingFieldCaseToNotZeroOneOrTwo2() {
        try {
            $this->connection->setAttribute(Doctrine::ATTR_FIELD_CASE, 5);
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testDefaultQuoteIdentifierAttributeValueIsFalse() {
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER), false);
    }
    public function testQuoteIdentifierAttributeAcceptsBooleans() {
        $this->manager->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, true);

        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER), true);
                $this->manager->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, false);
    }
    public function testDefaultSequenceColumnNameAttributeValueIsId() {
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_SEQCOL_NAME), 'id');
    }
    public function testSequenceColumnNameAttributeAcceptsStrings() {
        $this->manager->setAttribute(Doctrine::ATTR_SEQCOL_NAME, 'sequence');

        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_SEQCOL_NAME), 'sequence');
    }
    public function testValidatorAttributeAcceptsBooleans() {
        $this->manager->setAttribute(Doctrine::ATTR_VLD, true);
        
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_VLD), true);
    }
    public function testAutoLengthValidationAttributeAcceptsBooleans() {
        $this->manager->setAttribute(Doctrine::ATTR_AUTO_LENGTH_VLD, true);
        
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_AUTO_LENGTH_VLD), true);
    }
    public function testAutoTypeValidationAttributeAcceptsBooleans() {
        $this->manager->setAttribute(Doctrine::ATTR_AUTO_TYPE_VLD, true);
        
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_AUTO_TYPE_VLD), true);
    }
    public function testDefaultPortabilityAttributeValueIsAll() {
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_PORTABILITY), Doctrine::PORTABILITY_ALL);
    }
    public function testPortabilityAttributeAcceptsPortabilityConstants() {
        $this->manager->setAttribute(Doctrine::ATTR_PORTABILITY, Doctrine::PORTABILITY_RTRIM | Doctrine::PORTABILITY_FIX_CASE);

        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_PORTABILITY), 
                           Doctrine::PORTABILITY_RTRIM | Doctrine::PORTABILITY_FIX_CASE);
    }
    public function testDefaultListenerIsDoctrineEventListener() {
        $this->assertTrue($this->manager->getAttribute(Doctrine::ATTR_LISTENER) instanceof Doctrine_EventListener);                                                                 	
    }
    public function testListenerAttributeAcceptsEventListenerObjects() {
        $this->manager->setAttribute(Doctrine::ATTR_LISTENER, new Doctrine_EventListener_Debugger());

        $this->assertTrue($this->manager->getAttribute(Doctrine::ATTR_LISTENER) instanceof Doctrine_EventListener_Debugger);
    }
    public function testCollectionKeyAttributeAcceptsValidColumnName() {
        try {
            $this->connection->getTable('User')->setAttribute(Doctrine::ATTR_COLL_KEY, 'name');
            
            $this->pass();
        } catch(Exception $e) {
            $this->fail();
        }
    }
    public function testSettingInvalidColumnNameToCollectionKeyAttributeThrowsException() {
        try {
            $this->connection->getTable('User')->setAttribute(Doctrine::ATTR_COLL_KEY, 'unknown');
            
            $this->fail();
        } catch(Exception $e) {
            $this->pass();
        }
    }
    public function testSettingCollectionKeyAttributeOnOtherThanTableLevelThrowsException() {
        try {
            $this->connection->setAttribute(Doctrine::ATTR_COLL_KEY, 'name');
            
            $this->fail();
        } catch(Exception $e) {
            $this->pass();
        }
    }
    public function testSetAttribute() {
        $table = $this->connection->getTable("User");
        /**
        $this->manager->setAttribute(Doctrine::ATTR_CACHE_TTL,100);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_CACHE_TTL),100);

        $this->manager->setAttribute(Doctrine::ATTR_CACHE_SIZE,1);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_CACHE_SIZE),1);

        $this->manager->setAttribute(Doctrine::ATTR_CACHE_DIR,"%ROOT%".DIRECTORY_SEPARATOR."cache");
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_CACHE_DIR),$this->manager->getRoot().DIRECTORY_SEPARATOR."cache");
        */
        $this->manager->setAttribute(Doctrine::ATTR_FETCHMODE,Doctrine::FETCH_LAZY);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_FETCHMODE),Doctrine::FETCH_LAZY);

        $this->manager->setAttribute(Doctrine::ATTR_BATCH_SIZE, 5);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_BATCH_SIZE),5);

        $this->manager->setAttribute(Doctrine::ATTR_LOCKMODE, Doctrine::LOCK_PESSIMISTIC);
        $this->assertEqual($this->manager->getAttribute(Doctrine::ATTR_LOCKMODE), Doctrine::LOCK_PESSIMISTIC);

        // test invalid arguments
        /**
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
        */
        try {
            $this->connection->beginTransaction();
            $this->manager->setAttribute(Doctrine::ATTR_LOCKMODE, Doctrine::LOCK_OPTIMISTIC);
        } catch(Exception $e) {
            $this->assertTrue($e instanceof Exception);
            $this->connection->commit();
        }

        try {
            $this->connection->beginTransaction();
            $this->connection->setAttribute(Doctrine::ATTR_LOCKMODE, Doctrine::LOCK_PESSIMISTIC);
        } catch(Exception $e) {
            $this->assertTrue($e instanceof Exception);
            $this->connection->commit();
        }

    }
    public function testGetAttributes() {
        $this->assertTrue(is_array($this->manager->getAttributes()));
    }
}
?>
