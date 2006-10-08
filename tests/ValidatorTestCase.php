<?php
class Doctrine_ValidatorTestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables[] = "ValidatorTest";
        parent::prepareTables();
    }

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

    public function testValidate2() {
        $test = new ValidatorTest();
        $test->mymixed = "message";
        $test->myrange = 1;
        $test->myregexp = '123a';
        
        $validator = new Doctrine_Validator();
        $validator->validateRecord($test);

        $stack = $validator->getErrorStack();

        $this->assertTrue(is_array($stack));

        $this->assertEqual($stack['mystring'], 'notnull');
        $this->assertEqual($stack['myemail2'], 'notblank');
        $this->assertEqual($stack['myrange'],  'range');
        $this->assertEqual($stack['myregexp'], 'regexp');
        $test->mystring = 'str';


        $test->save();
    }
    public function testEmailValidation() {
    }

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


        $stack = $validator->getErrorStack();

        $this->assertTrue(is_array($stack));
        $this->assertEqual($stack["loginname"], 'length');
        $this->assertEqual($stack["password"],  'length');
        $this->assertEqual($stack["created"],   'type');
        

        $validator->validateRecord($email);
        $stack = $validator->getErrorStack();
        $this->assertEqual($stack["address"], 'email');
        $email->address = "arnold@example.com";

        $validator->validateRecord($email);
        $stack = $validator->getErrorStack();

        $this->assertEqual($stack["address"], 'unique');

        $email->isValid();
        
        $this->assertTrue($email->getErrorStack() instanceof Doctrine_Validator_ErrorStack);
    }

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

    public function testSave() {
        $this->manager->setAttribute(Doctrine::ATTR_VLD, true);
        $user = $this->connection->getTable("User")->find(4);
        try {
            $user->name = "this is an example of too long name not very good example but an example nevertheless";
            $user->save();
        } catch(Doctrine_Validator_Exception $e) {
            $this->assertEqual($e->count(), 1);
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

        $this->assertEqual($emailStack["address"], 'email');
        $this->assertEqual($userStack["name"], 'length');
        $this->manager->setAttribute(Doctrine::ATTR_VLD, false);
    }

}
?>
