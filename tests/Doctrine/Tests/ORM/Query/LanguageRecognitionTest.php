<?php
namespace Doctrine\Tests\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

class LanguageRecognitionTest extends \Doctrine\Tests\OrmTestCase
{
    private $_em;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
    }

    public function assertValidDql($dql, $debug = false)
    {
        try {
            $query = $this->_em->createQuery($dql);
            $parserResult = $query->parse();
        } catch (\Exception $e) {
            if ($debug) {
                echo $e->getTraceAsString() . PHP_EOL;
            }
            $this->fail($e->getMessage());
        }
    }

    public function assertInvalidDql($dql, $debug = false)
    {
        try {
            $query = $this->_em->createQuery($dql);
            $query->setDql($dql);
            $parserResult = $query->parse();

            $this->fail('No syntax errors were detected, when syntax errors were expected');
        } catch (\Exception $e) {
            //echo $e->getMessage() . PHP_EOL;
            if ($debug) {
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL;
            }
            // It was expected!
        }
    }

    public function testEmptyQueryString()
    {
        $this->assertInvalidDql('');
    }

    public function testPlainFromClauseWithAlias()
    {
        $this->assertValidDql('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSelectSingleComponentWithAsterisk()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testInvalidSelectSingleComponentWithAsterisk()
    {
        $this->assertInvalidDql('SELECT p FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertValidDql('SELECT u.name, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSelectMultipleComponentsWithAsterisk()
    {
        $this->assertValidDql('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p');
    }

    public function testSelectDistinctIsSupported()
    {
        $this->assertValidDql('SELECT DISTINCT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertValidDql('SELECT COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertValidDql('SELECT COUNT(DISTINCT u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }
/*
    public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertValidDql("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(u.name) = 'someone'");
    }
*/
    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000');
    }
/*
    public function testInExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN (1, 2)');
    }

    public function testNotInExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN (1)');
    }
*/
    /*public function testExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE EXISTS (SELECT p.user_id FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.user_id = u.id)');
    }

    public function testNotExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u.* FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT EXISTS (SELECT p.user_id FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.user_id = u.id)');
    }

    public function testLiteralValueAsInOperatorOperandIsSupported()
    {
        $this->assertValidDql('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE 1 IN (1, 2)');
    }

    public function testUpdateWorksWithOneColumn()
    {
        $this->assertValidDql("UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.name = 'someone'");
    }

    public function testUpdateWorksWithMultipleColumns()
    {
        $this->assertValidDql("UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.name = 'someone', u.username = 'some'");
    }

    public function testUpdateSupportsConditions()
    {
        $this->assertValidDql("UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.name = 'someone' WHERE u.id = 5");
    }

    public function testDeleteAll()
    {
        $this->assertValidDql('DELETE FROM Doctrine\Tests\Models\CMS\CmsUser');
    }

    public function testDeleteWithCondition()
    {
        $this->assertValidDql('DELETE FROM Doctrine\Tests\Models\CMS\CmsUser WHERE id = 3');
    }

    public function testAdditionExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id + u.id) addition FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSubtractionExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id - u.id) subtraction FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testDivisionExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id/u.id) division FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testMultiplicationExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id * u.id) multiplication FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testNegationExpression()
    {
        $this->assertValidDql('SELECT u.*, -u.id negation FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testExpressionWithPrecedingPlusSign()
    {
        $this->assertValidDql('SELECT u.*, +u.id FROM CmsUser u');
    }

    public function testAggregateFunctionInHavingClause()
    {
        $this->assertValidDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p HAVING COUNT(p.phonenumber) > 2');
        $this->assertValidDql("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p HAVING MAX(u.name) = 'zYne'");
    }

    public function testMultipleAggregateFunctionsInHavingClause()
    {
        $this->assertValidDql("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p HAVING MAX(u.name) = 'zYne'");
    }

    public function testLeftJoin()
    {
        $this->assertValidDql('SELECT u.*, p.* FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p');
    }

    public function testJoin()
    {
        $this->assertValidDql('SELECT u.* FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers');
    }

    public function testInnerJoin()
    {
        $this->assertValidDql('SELECT u.*, u.phonenumbers.* FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.phonenumbers');
    }

    public function testMultipleLeftJoin()
    {
        $this->assertValidDql('SELECT u.articles.*, u.phonenumbers.* FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles LEFT JOIN u.phonenumbers');
    }

    public function testMultipleInnerJoin()
    {
        $this->assertValidDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles INNER JOIN u.phonenumbers');
    }

    public function testMultipleInnerJoin2()
    {
        $this->assertValidDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles, u.phonenumbers');
    }

    public function testMixingOfJoins()
    {
        $this->assertValidDql('SELECT u.name, a.topic, p.phonenumber FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a LEFT JOIN u.phonenumbers p');
    }

    public function testMixingOfJoins2()
    {
        $this->assertInvalidDql('SELECT u.name, u.articles.topic, c.text FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles.comments c');
    }

    public function testOrderBySingleColumn()
    {
        $this->assertValidDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.name');
    }

    public function testOrderBySingleColumnAscending()
    {
        $this->assertValidDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.name ASC');
    }

    public function testOrderBySingleColumnDescending()
    {
        $this->assertValidDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.name DESC');
    }

    public function testOrderByMultipleColumns()
    {
        $this->assertValidDql('SELECT u.name, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username DESC, u.name DESC');
    }

    public function testOrderByWithFunctionExpression()
    {
        $this->assertValidDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY COALESCE(u.id, u.name) DESC');
    }*/
/*
    public function testSubselectInInExpression()
    {
        $this->assertValidDql("SELECT * FROM CmsUser u WHERE u.id NOT IN (SELECT u2.id FROM CmsUser u2 WHERE u2.name = 'zYne')");
    }

    public function testSubselectInSelectPart()
    {
        // Semantical error: Unknown query component u (probably in subselect)
        $this->assertValidDql("SELECT u.name, (SELECT COUNT(p.phonenumber) FROM CmsPhonenumber p WHERE p.user_id = u.id) pcount FROM CmsUser u WHERE u.name = 'zYne' LIMIT 1");
    }
*//*
    public function testPositionalInputParameter()
    {
        $this->assertValidDql('SELECT * FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?');
    }

    public function testNamedInputParameter()
    {
        $this->assertValidDql('SELECT * FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id');
    }*/
/*
    public function testCustomJoinsAndWithKeywordSupported()
    {
        // We need existant classes here, otherwise semantical will always fail
        $this->assertValidDql('SELECT c.*, c2.*, d.* FROM Record_Country c INNER JOIN c.City c2 WITH c2.id = 2 WHERE c.id = 1');
    }
*/
/*
    public function testJoinConditionsSupported()
    {
        $this->assertValidDql("SELECT u.name, p.* FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p ON p.phonenumber = '123 123'");
    }

    public function testIndexByClauseWithOneComponent()
    {
        $this->assertValidDql('SELECT * FROM Doctrine\Tests\Models\CMS\CmsUser u INDEX BY id');
    }

    public function testIndexBySupportsJoins()
    {
        $this->assertValidDql('SELECT u.*, u.articles.* FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles INDEX BY id'); // INDEX BY is now referring to articles
    }

    public function testIndexBySupportsJoins2()
    {
        $this->assertValidDql('SELECT u.*, p.* FROM Doctrine\Tests\Models\CMS\CmsUser u INDEX BY id LEFT JOIN u.phonenumbers p INDEX BY phonenumber');
    }

    public function testBetweenExpressionSupported()
    {
        $this->assertValidDql("SELECT * FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name BETWEEN 'jepso' AND 'zYne'");
    }

    public function testNotBetweenExpressionSupported()
    {
        $this->assertValidDql("SELECT * FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name NOT BETWEEN 'jepso' AND 'zYne'");
    }
*/
/*    public function testAllExpressionWithCorrelatedSubquery()
    {
        // We need existant classes here, otherwise semantical will always fail
        $this->assertValidDql('SELECT * FROM CompanyEmployee e WHERE e.salary > ALL (SELECT m.salary FROM CompanyManager m WHERE m.department = e.department)', true);
    }

    public function testAnyExpressionWithCorrelatedSubquery()
    {
        // We need existant classes here, otherwise semantical will always fail
        $this->assertValidDql('SELECT * FROM Employee e WHERE e.salary > ANY (SELECT m.salary FROM Manager m WHERE m.department = e.department)');
    }

    public function testSomeExpressionWithCorrelatedSubquery()
    {
        // We need existant classes here, otherwise semantical will always fail
        $this->assertValidDql('SELECT * FROM Employee e WHERE e.salary > SOME (SELECT m.salary FROM Manager m WHERE m.department = e.department)');
    }
*//*
    public function testLikeExpression()
    {
        $this->assertValidDql("SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name LIKE 'z%'");
    }

    public function testNotLikeExpression()
    {
        $this->assertValidDql("SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name NOT LIKE 'z%'");
    }

    public function testLikeExpressionWithCustomEscapeCharacter()
    {
        $this->assertValidDql("SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name LIKE 'z|%' ESCAPE '|'");
    }
*/
    /**
     * TODO: Hydration can't deal with this currently but it should be allowed.
     *       Also, generated SQL looks like: "... FROM cms_user, cms_article ..." which
     *       may not work on all dbms.
     * 
     * The main use case for this generalized style of join is when a join condition 
     * does not involve a foreign key relationship that is mapped to an entity relationship.
     */
  /*  public function testImplicitJoinWithCartesianProductAndConditionInWhere()
    {
        $this->assertValidDql("SELECT u.*, a.* FROM Doctrine\Tests\Models\CMS\CmsUser u, CmsArticle a WHERE u.name = a.topic");
    }
    
    public function testImplicitJoinInWhereOnSingleValuedAssociationPathExpression()
    {
        // This should be allowed because avatar is a single-value association.
        // SQL: SELECT ... FROM forum_user fu INNER JOIN forum_avatar fa ON fu.avatar_id = fa.id WHERE fa.id = ?
        //$this->assertValidDql("SELECT u.* FROM ForumUser u WHERE u.avatar.id = ?");
    }
    
    public function testImplicitJoinInWhereOnCollectionValuedPathExpression()
    {
        // This should be forbidden, because articles is a collection
        $this->assertInvalidDql("SELECT u.* FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.articles.title = ?");
    }

    public function testInvalidSyntaxIsRejected()
    {
        $this->assertInvalidDql("FOOBAR CmsUser");
        $this->assertInvalidDql("DELETE FROM Doctrine\Tests\Models\CMS\CmsUser.articles");
        $this->assertInvalidDql("DELETE FROM Doctrine\Tests\Models\CMS\CmsUser cu WHERE cu.articles.id > ?");
        $this->assertInvalidDql("SELECT user FROM Doctrine\Tests\Models\CMS\CmsUser user");
        
        // Error message here is: Relation 'comments' does not exist in component 'CmsUser'
        // This means it is intepreted as:
        // SELECT u.* FROM CmsUser u JOIN u.articles JOIN u.comments
        // This seems wrong. "JOIN u.articles.comments" should not be allowed.
        $this->assertInvalidDql("SELECT u.* FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles.comments");
        
        // Currently UNDEFINED OFFSET error
        //$this->assertInvalidDql("SELECT * FROM CmsUser.articles.comments");
    }*/
}