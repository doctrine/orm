<?php
/**
 * TestCase for Doctrine's validation component.
 * 
 * @todo More tests to cover the full interface of Doctrine_Validator_ErrorStack.
 */
class Doctrine_ValidatorTestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables[] = "ValidatorTest";
        parent::prepareTables();
    }

    /**
     * Tests correct type detection.
     */
    public function testIsValidType() {
        $var = "123";
        $this->assertTrue(Doctrine_Validator::isValidType($var,"string"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"integer"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"float"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"array"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"object"));

        $var = 123;
        $this->assertTrue(Doctrine_Validator::isValidType($var,"string"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"integer"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"float"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"array"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"object"));

        $var = 123.12;
        $this->assertTrue(Doctrine_Validator::isValidType($var,"string"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"integer"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"float"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"array"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"object"));

        $var = '123.12';
        $this->assertTrue(Doctrine_Validator::isValidType($var,"string"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"integer"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"float"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"array"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"object"));

        $var = '';
        $this->assertTrue(Doctrine_Validator::isValidType($var,"string"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"integer"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"float"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"array"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"object"));

        $var = null;
        $this->assertTrue(Doctrine_Validator::isValidType($var,"string"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"integer"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"float"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"array"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"object"));

        $var = 'str';
        $this->assertTrue(Doctrine_Validator::isValidType($var,"string"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"integer"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"float"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"array"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"object"));

        $var = array();
        $this->assertFalse(Doctrine_Validator::isValidType($var,"string"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"integer"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"float"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"array"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"object"));
        
        $var = new Exception();
        $this->assertFalse(Doctrine_Validator::isValidType($var,"string"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"integer"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"float"));
        $this->assertFalse(Doctrine_Validator::isValidType($var,"array"));
        $this->assertTrue(Doctrine_Validator::isValidType($var,"object"));
    }

    /**
     * Tests Doctrine_Validator::validateRecord()
     */
    public function testValidate2() {
        $test = new ValidatorTest();
        $test->mymixed = "message";
        $test->myrange = 1;
        $test->myregexp = '123a';
        
        $validator = new Doctrine_Validator();
        $validator->validateRecord($test);

        $stack = $test->getErrorStack();

        $this->assertTrue($stack instanceof Doctrine_Validator_ErrorStack);

        $this->assertTrue(in_array(array('type' => 'notnull'), $stack['mystring']));
        $this->assertTrue(in_array(array('type' => 'notblank'), $stack['myemail2']));
        $this->assertTrue(in_array(array('type' => 'range'), $stack['myrange']));
        $this->assertTrue(in_array(array('type' => 'regexp'), $stack['myregexp']));
        $test->mystring = 'str';


        $test->save();
    }

    /**
     * Tests Doctrine_Validator::validateRecord()
     */
    public function testValidate() {
        $user = $this->connection->getTable("User")->find(4); 

        $set = array("password" => "this is an example of too long password",
                     "loginname" => "this is an example of too long loginname",
                     "name" => "valid name",
                     "created" => "invalid");
        $user->setArray($set);
        $email = $user->Email;
        $email->address = "zYne@invalid";

        $this->assertTrue($user->getModified() == $set);

        $validator = new Doctrine_Validator();
        $validator->validateRecord($user);


        $stack = $user->getErrorStack();

        $this->assertTrue($stack instanceof Doctrine_Validator_ErrorStack);
        $this->assertTrue(in_array(array('type' => 'length'), $stack['loginname']));
        $this->assertTrue(in_array(array('type' => 'length'), $stack['password']));
        $this->assertTrue(in_array(array('type' => 'type'), $stack['created']));

        $validator->validateRecord($email);
        $stack = $email->getErrorStack();
        $this->assertTrue(in_array(array('type' => 'email'), $stack['address']));
        $email->address = "arnold@example.com";

        $validator->validateRecord($email);
        $stack = $email->getErrorStack();

        $this->assertTrue(in_array(array('type' => 'unique'), $stack['address']));
    }

    /**
     * Tests the Email validator. (Doctrine_Validator_Email)
     */
    public function testIsValidEmail() {

        $validator = new Doctrine_Validator_Email();

        $email = $this->connection->create("Email");
        $this->assertFalse($validator->validate($email,"address","example@example",null));
        $this->assertFalse($validator->validate($email,"address","example@@example",null));
        $this->assertFalse($validator->validate($email,"address","example@example.",null));
        $this->assertFalse($validator->validate($email,"address","example@e..",null));

        $this->assertFalse($validator->validate($email,"address","example@e..",null));

        $this->assertTrue($validator->validate($email,"address","null@pookey.co.uk",null));
        $this->assertTrue($validator->validate($email,"address","null@pookey.com",null));
        $this->assertTrue($validator->validate($email,"address","null@users.doctrine.pengus.net",null));

    }

    /**
     * Tests saving records with invalid attributes.
     */
    public function testSave() {
        $this->manager->setAttribute(Doctrine::ATTR_VLD, true);
        $user = $this->connection->getTable("User")->find(4);
        try {
            $user->name = "this is an example of too long name not very good example but an example nevertheless";
            $user->save();
        } catch(Doctrine_Validator_Exception $e) {
            $this->assertEqual($e->count(), 1);
            $invalidRecords = $e->getInvalidRecords();
            $this->assertEqual(count($invalidRecords), 1);
            $stack = $invalidRecords[0]->getErrorStack();
            $this->assertTrue(in_array(array('type' => 'length'), $stack['name']));
        }

        try {
            $user = $this->connection->create("User");
            $user->Email->address = "jackdaniels@drinkmore.info...";
            $user->name = "this is an example of too long user name not very good example but an example nevertheles";
            $user->save();
            $this->fail();
        } catch(Doctrine_Validator_Exception $e) {
            $this->pass();
            $a = $e->getInvalidRecords();
        }

        $this->assertTrue(is_array($a));
        
        $emailStack = $a[array_search($user->Email, $a)]->getErrorStack();
        $userStack  = $a[array_search($user, $a)]->getErrorStack();

        $this->assertTrue(in_array(array('type' => 'email'), $emailStack['address']));
        $this->assertTrue(in_array(array('type' => 'length'), $userStack['name']));
        $this->manager->setAttribute(Doctrine::ATTR_VLD, false);
    }

    /**
     * Tests whether custom validation through template methods works correctly
     * in descendants of Doctrine_Record.
     */
    public function testCustomValidation() {
        $this->manager->setAttribute(Doctrine::ATTR_VLD, true);
        $user = $this->connection->getTable("User")->find(4);
         try {
            $user->name = "I'm not The Saint";
            $user->save();
        } catch(Doctrine_Validator_Exception $e) {
            $this->assertEqual($e->count(), 1);
            $invalidRecords = $e->getInvalidRecords();
            $this->assertEqual(count($invalidRecords), 1);
            
            $stack = $invalidRecords[0]->getErrorStack();
            
            $this->assertEqual($stack->count(), 1);
            $this->assertTrue(in_array(array('type' => 'notTheSaint'), $stack['name']));
        }
        $this->manager->setAttribute(Doctrine::ATTR_VLD, false);
    }
}
?>
