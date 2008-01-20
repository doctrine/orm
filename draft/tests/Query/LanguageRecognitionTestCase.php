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

    public function testEmptyQueryString()
    {
        $this->assertInvalidDql('');
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

    public function testUpdateWorksWithOneColumn()
    {
        $this->assertValidDql("UPDATE User u SET u.name = 'someone'");
    }

    public function testUpdateWorksWithMultipleColumns()
    {
        $this->assertValidDql("UPDATE User u SET u.name = 'someone', u.email_id = 5");
    }

    public function testUpdateSupportsConditions()
    {
        $this->assertValidDql("UPDATE User u SET u.name = 'someone' WHERE u.id = 5");
    }

    public function testDeleteAll()
    {
        $this->assertValidDql('DELETE FROM Entity');
    }

    public function testDeleteWithCondition()
    {
        $this->assertValidDql('DELETE FROM Entity WHERE id = 3');
    }

    public function testDeleteWithLimit()
    {
        $this->assertValidDql('DELETE FROM Entity LIMIT 20');
    }

    public function testDeleteWithLimitAndOffset()
    {
        $this->assertValidDql('DELETE FROM Entity LIMIT 10 OFFSET 20');
    }

    public function testAdditionExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id + u.id) addition FROM User u');
    }

    public function testSubtractionExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id - u.id) subtraction FROM User u');
    }

    public function testDivisionExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id/u.id) division FROM User u');
    }

    public function testMultiplicationExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id * u.id) multiplication FROM User u');
    }

    public function testNegationExpression()
    {
        $this->assertValidDql('SELECT u.*, -u.id negation FROM User u');
    }

    public function testExpressionWithPrecedingPlusSign()
    {
        $this->assertValidDql('SELECT u.*, +u.id FROM User u');
    }

    public function testAggregateFunctionInHavingClause()
    {
        $this->assertValidDql('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p HAVING COUNT(p.id) > 2');
        $this->assertValidDql("SELECT u.name FROM User u LEFT JOIN u.Phonenumber p HAVING MAX(u.name) = 'zYne'");
    }

    public function testMultipleAggregateFunctionsInHavingClause()
    {
        $this->assertValidDql("SELECT u.name FROM User u LEFT JOIN u.Phonenumber p HAVING MAX(u.name) = 'zYne'");
    }

    public function testLeftJoin()
    {
        $this->assertValidDql('FROM User u LEFT JOIN u.Group');
    }

    public function testJoin()
    {
        $this->assertValidDql('FROM User u JOIN u.Group');
    }

    public function testInnerJoin()
    {
        $this->assertValidDql('FROM User u INNER JOIN u.Group');
    }

    public function testMultipleLeftJoin()
    {
        $this->assertValidDql('FROM User u LEFT JOIN u.Group LEFT JOIN u.Phonenumber');
    }

    public function testMultipleInnerJoin()
    {
        $this->assertValidDql('SELECT u.name FROM User u INNER JOIN u.Group INNER JOIN u.Phonenumber');
    }

    public function testMultipleInnerJoin2()
    {
        $this->assertValidDql('SELECT u.name FROM User u INNER JOIN u.Group, u.Phonenumber');
    }

    public function testMixingOfJoins()
    {
        $this->assertValidDql('SELECT u.name, g.name, p.phonenumber FROM User u INNER JOIN u.Group g LEFT JOIN u.Phonenumber p');
    }

    public function testMixingOfJoins2()
    {
        $this->assertValidDql('SELECT u.name, g.name, p.phonenumber FROM User u INNER JOIN u.Group.Phonenumber p');
    }

    public function testOrderBySingleColumn()
    {
        $this->assertValidDql('SELECT u.name FROM User u ORDER BY u.name');
    }

    public function testOrderBySingleColumnAscending()
    {
        $this->assertValidDql('SELECT u.name FROM User u ORDER BY u.name ASC');
    }

    public function testOrderBySingleColumnDescending()
    {
        $this->assertValidDql('SELECT u.name FROM User u ORDER BY u.name DESC');
    }

    public function testOrderByMultipleColumns()
    {
        $this->assertValidDql('SELECT u.firstname, u.lastname FROM User u ORDER BY u.lastname DESC, u.firstname DESC');
    }

    public function testOrderByWithFunctionExpression()
    {
        $this->assertValidDql('SELECT u.name FROM User u ORDER BY COALESCE(u.id, u.name) DESC');
    }

    public function testSubselectInInExpression()
    {
        $this->assertValidDql("FROM User u WHERE u.id NOT IN (SELECT u2.id FROM User u2 WHERE u2.name = 'zYne')");
    }

    public function testSubselectInSelectPart()
    {
        $this->assertValidDql("SELECT u.name, (SELECT COUNT(p.id) FROM Phonenumber p WHERE p.entity_id = u.id) pcount FROM User u WHERE u.name = 'zYne' LIMIT 1");
    }

    public function testInputParameter()
    {
        $this->assertValidDql('FROM User WHERE u.id = ?');
    }

    public function testNamedInputParameter()
    {
        $this->assertValidDql('FROM User WHERE u.id = :id');
    }

    public function testCustomJoinsAndWithKeywordSupported()
    {
        $this->assertValidDql('SELECT c.*, c2.*, d.* FROM Record_Country c INNER JOIN c.City c2 WITH c2.id = 2 WHERE c.id = 1');
    }

    public function testJoinConditionsSupported()
    {
        $this->assertValidDql("SELECT u.name, p.id FROM User u LEFT JOIN u.Phonenumber p ON p.phonenumber = '123 123'");
    }

    public function testIndexByClauseWithOneComponent()
    {
        $this->assertValidDql('FROM Record_City c INDEX BY c.name');
    }
}
