<?php
namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query,
    Doctrine\ORM\Query\QueryException;

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
            $parserResult = $this->parseDql($dql);
        } catch (QueryException $e) {
            if ($debug) {
                echo $e->getTraceAsString() . PHP_EOL;
            }
            
            $this->fail($e->getMessage());
        }
    }

    public function assertInvalidDql($dql, $debug = false)
    {
        try {
            $parserResult = $this->parseDql($dql);
            
            $this->fail('No syntax errors were detected, when syntax errors were expected');
        } catch (QueryException $e) {
            if ($debug) {
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL;
            }
        }
    }
    
    public function parseDql($dql, $hints = array())
    {
        $query = $this->_em->createQuery($dql);
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $query->setDql($dql);
        
        foreach ($hints as $key => $value) {
        	$query->setHint($key, $value);
        }
        
        $parser = new \Doctrine\ORM\Query\Parser($query);
        // We do NOT test SQL output here. That only unnecessarily slows down the tests!
        $parser->setCustomOutputTreeWalker('Doctrine\Tests\Mocks\MockTreeWalker');
        
        return $parser->parse();
    }
    
    public function parseDqlForAST($dql, $hints = array())
    {
        $query = $this->_em->createQuery($dql);
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $query->setDql($dql);
        
        foreach ($hints as $key => $value) {
        	$query->setHint($key, $value);
        }
        
        $parser = new \Doctrine\ORM\Query\Parser($query);
        // We do NOT test SQL output here. That only unnecessarily slows down the tests!
        $parser->setCustomOutputTreeWalker('Doctrine\Tests\Mocks\MockTreeWalker');
        $parser->parse();
        
        return $parser->getAST();
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

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertValidDql('SELECT u.name, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSelectMultipleComponentsWithAsterisk()
    {
        $this->assertValidDql('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p');
    }

    public function testSelectMultipleComponentsWithAsterisk2()
    {
        $this->assertValidDql('SELECT a.user.name FROM Doctrine\Tests\Models\CMS\CmsArticle a');
    }

    public function testSelectDistinctIsSupported()
    {
        $this->assertValidDql('SELECT DISTINCT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertValidDql('SELECT COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }
    
    public function testDuplicatedAliasInAggregateFunction()
    {
        $this->assertInvalidDql('SELECT COUNT(u.id) AS num, SUM(u.id) AS num FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertValidDql('SELECT COUNT(DISTINCT u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertValidDql("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(u.name) = 'someone'");
    }

    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000');
    }
 
    public function testInExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN (1, 2)');
    }

    public function testNotInExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN (1)');
    }

    public function testExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE EXISTS (SELECT p.phonenumber FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.phonenumber = 1234)');
    }

    public function testNotExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT EXISTS (SELECT p.phonenumber FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.phonenumber = 1234)');
    }

    public function testAggregateFunctionInHavingClause()
    {
        $this->assertValidDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p HAVING COUNT(p.phonenumber) > 2');
        $this->assertValidDql("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p HAVING MAX(u.name) = 'romanb'");
    }

    public function testLeftJoin()
    {
        $this->assertValidDql('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p');
    }

    public function testJoin()
    {
        $this->assertValidDql('SELECT u,p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p');
    }

    public function testInnerJoin()
    {
        $this->assertValidDql('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.phonenumbers p');
    }

    public function testMultipleLeftJoin()
    {
        $this->assertValidDql('SELECT u, a, p FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a LEFT JOIN u.phonenumbers p');
    }

    public function testMultipleInnerJoin()
    {
        $this->assertValidDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a INNER JOIN u.phonenumbers p');
    }

    public function testMixingOfJoins()
    {
        $this->assertValidDql('SELECT u.name, a.topic, p.phonenumber FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a LEFT JOIN u.phonenumbers p');
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

    public function testSubselectInInExpression()
    {
        $this->assertValidDql("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN (SELECT u2.id FROM Doctrine\Tests\Models\CMS\CmsUser u2 WHERE u2.name = 'zYne')");
    }

    public function testSubselectInSelectPart()
    {
        $this->assertValidDql("SELECT u.name, (SELECT COUNT(p.phonenumber) FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.phonenumber = 1234) pcount FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = 'jon'");
    }

    public function testPositionalInputParameter()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1');
    }

    public function testNamedInputParameter()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id');
    }

    public function testJoinConditionsSupported()
    {
        $this->assertValidDql("SELECT u.name, p FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p ON p.phonenumber = '123 123'");
    }

    public function testIndexByClauseWithOneComponent()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u INDEX BY u.id');
    }

    public function testIndexBySupportsJoins()
    {
        $this->assertValidDql('SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a INDEX BY a.id'); // INDEX BY is now referring to articles
    }

    public function testIndexBySupportsJoins2()
    {
        $this->assertValidDql('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u INDEX BY u.id LEFT JOIN u.phonenumbers p INDEX BY p.phonenumber');
    }

    public function testBetweenExpressionSupported()
    {
        $this->assertValidDql("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name BETWEEN 'jepso' AND 'zYne'");
    }

    public function testNotBetweenExpressionSupported()
    {
        $this->assertValidDql("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name NOT BETWEEN 'jepso' AND 'zYne'");
    }

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

    public function testFieldComparisonWithoutAlias()
    {
        $this->assertInvalidDql("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE id = 1");
    }
    
    public function testDuplicatedAliasDeclaration()
    {
        $this->assertInvalidDql("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles u WHERE u.id = 1");
    }

    /*public function testImplicitJoinInWhereOnSingleValuedAssociationPathExpression()
    {
        // This should be allowed because avatar is a single-value association.
        // SQL: SELECT ... FROM forum_user fu INNER JOIN forum_avatar fa ON fu.avatar_id = fa.id WHERE fa.id = ?
        $this->assertValidDql("SELECT u FROM Doctrine\Tests\Models\Forum\ForumUser u WHERE u.avatar.id = ?");
    }*/

    public function testImplicitJoinInWhereOnCollectionValuedPathExpression()
    {
        // This should be forbidden, because articles is a collection
        $this->assertInvalidDql("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.articles.title = ?");
    }

    public function testInvalidSyntaxIsRejected()
    {
        $this->assertInvalidDql("FOOBAR CmsUser");
        $this->assertInvalidDql("DELETE FROM Doctrine\Tests\Models\CMS\CmsUser.articles");
        $this->assertInvalidDql("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles.comments");

        // Currently UNDEFINED OFFSET error
        $this->assertInvalidDql("SELECT c FROM CmsUser.articles.comments c");
    }

    public function testUpdateWorksWithOneField()
    {
        $this->assertValidDql("UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.name = 'someone'");
    }

    public function testUpdateWorksWithMultipleFields()
    {
        $this->assertValidDql("UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.name = 'someone', u.username = 'some'");
    }

    public function testUpdateSupportsConditions()
    {
        $this->assertValidDql("UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.name = 'someone' WHERE u.id = 5");
    }

    public function testDeleteAll()
    {
        $this->assertValidDql('DELETE FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testDeleteWithCondition()
    {
        $this->assertValidDql('DELETE FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = 3');
    }

    /**
     * TODO: Hydration can't deal with this currently but it should be allowed.
     *       Also, generated SQL looks like: "... FROM cms_user, cms_article ..." which
     *       may not work on all dbms.
     *
     * The main use case for this generalized style of join is when a join condition
     * does not involve a foreign key relationship that is mapped to an entity relationship.
     */
    public function testImplicitJoinWithCartesianProductAndConditionInWhere()
    {
        $this->assertValidDql("SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsArticle a WHERE u.name = a.topic");
    }

    public function testAllExpressionWithCorrelatedSubquery()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > ALL (SELECT u2.id FROM Doctrine\Tests\Models\CMS\CmsUser u2 WHERE u2.name = u.name)');
    }

    public function testCustomJoinsAndWithKeywordSupported()
    {
        $this->assertValidDql('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.phonenumbers p WITH p.phonenumber = 123 WHERE u.id = 1');
    }

    public function testAnyExpressionWithCorrelatedSubquery()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > ANY (SELECT u2.id FROM Doctrine\Tests\Models\CMS\CmsUser u2 WHERE u2.name = u.name)');
    }

    public function testSomeExpressionWithCorrelatedSubquery()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > SOME (SELECT u2.id FROM Doctrine\Tests\Models\CMS\CmsUser u2 WHERE u2.name = u.name)');
    }
    
    public function testMemberOfExpression()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE :param MEMBER OF u.phonenumbers');
    }
    
    public function testSizeFunction()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE SIZE(u.phonenumbers) > 1');
    }
    
    public function testEmptyCollectionComparisonExpression()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.phonenumbers IS EMPTY');
    }
    
    public function testSingleValuedAssociationFieldInWhere()
    {
        $this->assertValidDql('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.address = ?1');
        $this->assertValidDql('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.user = ?1');
    }
    
    public function testBooleanLiteralInWhere()
    {
        $this->assertValidDql('SELECT b FROM Doctrine\Tests\Models\Generic\BooleanModel b WHERE b.booleanField = true');
    }
    
    public function testSubqueryInSelectExpression()
    {
        $this->assertValidDql('select u, (select max(p.phonenumber) from Doctrine\Tests\Models\CMS\CmsPhonenumber p) maxId from Doctrine\Tests\Models\CMS\CmsUser u');
    }
    
    public function testUsageOfQComponentOutsideSubquery()
    {
        $this->assertInvalidDql('select u, (select max(p.phonenumber) from Doctrine\Tests\Models\CMS\CmsPhonenumber p) maxId from Doctrine\Tests\Models\CMS\CmsUser u WHERE p.user = ?1');
    }
    
    public function testUnknownAbstractSchemaName()
    {
        $this->assertInvalidDql('SELECT u FROM UnknownClassName u');
    }
    
    /**
     * This checks for invalid attempt to hydrate a proxy. It should throw an exception
     *
     * @expectedException \Doctrine\Common\DoctrineException
     */
    public function testPartialObjectLoad()
    {
        $this->parseDql('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u', array(
        	\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD => false
        ));
    }
}