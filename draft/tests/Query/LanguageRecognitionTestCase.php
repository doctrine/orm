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

    public function testSelectDistinctIsSupported()
    {
        $this->assertValidDql('SELECT DISTINCT u.name FROM User u');
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertValidDql('SELECT COUNT(u.id) FROM User u');
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertValidDql('SELECT COUNT(DISTINCT u.name) FROM User u');
    }

    public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertValidDql("SELECT u.name FROM User u WHERE TRIM(u.name) = 'someone'");
    }

    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertValidDql('FROM Account a WHERE ((a.amount + 5000) * a.amount + 3) < 10000000');
    }

    public function testInExpressionSupportedInWherePart()
    {
        $this->assertValidDql('FROM User WHERE User.id IN (1, 2)');
    }

    public function testNotInExpressionSupportedInWherePart()
    {
        $this->assertValidDql('FROM User WHERE User.id NOT IN (1)');
    }

    public function testExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDql('FROM User WHERE EXISTS (SELECT g.id FROM Groupuser g WHERE g.user_id = u.id)');
    }

    public function testNotExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDql('FROM User WHERE NOT EXISTS (SELECT g.id FROM Groupuser g WHERE g.user_id = u.id)');
    }

    public function testLiteralValueAsInOperatorOperandIsSupported()
    {
        $this->assertValidDql('SELECT u.id FROM User u WHERE 1 IN (1, 2)');
    }
}
