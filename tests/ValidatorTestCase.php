<?php
class Doctrine_ValidatorTestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables[] = "Validator_Test";
        parent::prepareTables();
    }
    public function testValidate2() {
        $test = new Validator_Test();
        $test->mymixed = "message";

        $validator = new Doctrine_Validator();
        $validator->validateRecord($test);

        $stack = $validator->getErrorStack();

        $this->assertTrue(is_array($stack));

        $stack = $stack['Validator_Test'][0];
        $this->assertEqual($stack['mystring'], Doctrine_Validator::ERR_NOTNULL);

        $test->mystring = 'str';

        $test->save();
    }

    public function testValidate() {
        $user = $this->session->getTable("User")->find(4); 

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

        $email = $this->session->create("Email");
        $this->assertFalse($validator->validate($email,"address","example@example",null));
        $this->assertFalse($validator->validate($email,"address","example@@example",null));
        $this->assertFalse($validator->validate($email,"address","example@example.",null));
        $this->assertFalse($validator->validate($email,"address","example@e..",null));

        $this->assertFalse($validator->validate($email,"address","example@e..",null));
        $this->assertTrue($validator->validate($email,"address","example@e.e.e.e.e",null));

    }
    public function testSave() {
        $this->manager->setAttribute(Doctrine::ATTR_VLD, true);
        $user = $this->session->getTable("User")->find(4); 
        try {
            $user->name = "this is an example of too long name not very good example but an example nevertheless";
            $user->save();
        } catch(Doctrine_Validator_Exception $e) {
            $this->assertEqual($e->getErrorStack(),array("User" => array(array("name" => 0))));
        }

        try {
            $user = $this->session->create("User");
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
