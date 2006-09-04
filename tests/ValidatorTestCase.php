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
        $this->assertTrue(isset($stack['ValidatorTest'][0]));
        $stack = $stack['ValidatorTest'][0];


        $this->assertEqual($stack['mystring'], Doctrine_Validator::ERR_NOTNULL);
        $this->assertEqual($stack['myemail2'], Doctrine_Validator::ERR_NOTBLANK);
        $this->assertEqual($stack['myrange'], Doctrine_Validator::ERR_RANGE);
        $this->assertEqual($stack['myregexp'], Doctrine_Validator::ERR_REGEXP);
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
        $validator->validateRecord($email);

        $stack = $validator->getErrorStack();

        $this->assertTrue(is_array($stack));
        $this->assertEqual($stack["User"][0]["loginname"], Doctrine_Validator::ERR_LENGTH);
        $this->assertEqual($stack["User"][0]["password"], Doctrine_Validator::ERR_LENGTH);
        $this->assertEqual($stack["User"][0]["created"], Doctrine_Validator::ERR_TYPE);

        $this->assertEqual($stack["Email"][0]["address"], Doctrine_Validator::ERR_VALID);
        $email->address = "arnold@example.com";

        $validator->validateRecord($email);
        $stack = $validator->getErrorStack();
        $this->assertEqual($stack["Email"][1]["address"], Doctrine_Validator::ERR_UNIQUE);

    }

    public function testIsValidEmail() {

        $validator = new Doctrine_Validator_Email();

        $email = $this->connection->create("Email");
        $this->assertFalse($validator->validate($email,"address","example@example",null));
        $this->assertFalse($validator->validate($email,"address","example@@example",null));
        $this->assertFalse($validator->validate($email,"address","example@example.",null));
        $this->assertFalse($validator->validate($email,"address","example@e..",null));

        $this->assertFalse($validator->validate($email,"address","example@e..",null));


    }
    public function testSave() {
        $this->manager->setAttribute(Doctrine::ATTR_VLD, true);
        $user = $this->connection->getTable("User")->find(4);
        try {
            $user->name = "this is an example of too long name not very good example but an example nevertheless";
            $user->save();
        } catch(Doctrine_Validator_Exception $e) {
            $this->assertEqual($e->getErrorStack(),array("User" => array(array("name" => 0))));
        }

        try {
            $user = $this->connection->create("User");
            $user->Email->address = "jackdaniels@drinkmore.info...";
            $user->name = "this is an example of too long user name not very good example but an example nevertheles";
            $user->save();
        } catch(Doctrine_Validator_Exception $e) {
            $a = $e->getErrorStack();
        }
        $this->assertTrue(is_array($a));
        $this->assertEqual($a["Email"][0]["address"], Doctrine_Validator::ERR_VALID);
        $this->assertEqual($a["User"][0]["name"], Doctrine_Validator::ERR_LENGTH);
        $this->manager->setAttribute(Doctrine::ATTR_VLD, false);
    }

}
?>
