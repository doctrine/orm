<?php
class Doctrine_Query_LanguageRecognition_TestCase extends Doctrine_UnitTestCase
{
    public function assertValidDql($dql)
    {
        $parser = new Doctrine_Query_Parser($dql);

        try {
            $parser->parse();
            $this->pass();
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function assertInvalidDql($dql)
    {
        $parser = new Doctrine_Query_Parser($dql);

        try {
            $parser->parse();
            $this->fail();
        } catch (Exception $e) {
            $this->pass();
        }
    }

    public function testPlainFromClauseWithoutAlias()
    {
        $this->assertValidDql('FROM User');
    }

    public function testPlainFromClauseWithAlias()
    {
        $this->assertValidDql('FROM User u');
    }

    public function testSelectSingleComponentWithAsterisk()
    {
        $this->assertValidDql('SELECT u.* FROM User u');
    }

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertValidDql('SELECT u.name, u.type FROM User u');
    }

    public function testSelectMultipleComponentsWithAsterisk()
    {
        $this->assertValidDql('SELECT u.*, p.* FROM User u, u.Phonenumber p');
    }
}
