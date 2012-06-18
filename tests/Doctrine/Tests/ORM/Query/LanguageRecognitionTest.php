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

    public function assertValidDQL($dql, $debug = false)
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

    public function assertInvalidDQL($dql, $debug = false)
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

    public function testEmptyQueryString()
    {
        $this->assertInvalidDQL('');
    }

    public function testPlainFromClauseWithAlias()
    {
        $this->assertValidDQL('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSelectSingleComponentWithAsterisk()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertValidDQL('SELECT u.name, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSelectMultipleComponentsUsingMultipleFrom()
    {
        $this->assertValidDQL('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE u = p.user');
    }

    public function testSelectMultipleComponentsWithAsterisk()
    {
        $this->assertValidDQL('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p');
    }

    public function testSelectDistinctIsSupported()
    {
        $this->assertValidDQL('SELECT DISTINCT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertValidDQL('SELECT COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testDuplicatedAliasInAggregateFunction()
    {
        $this->assertInvalidDQL('SELECT COUNT(u.id) AS num, SUM(u.id) AS num FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertValidDQL('SELECT COUNT(DISTINCT u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertValidDQL("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(u.name) = 'someone'");
    }

    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000');
    }

    public function testInExpressionSupportedInWherePart()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN (1, 2)');
    }

    public function testInExpressionWithoutSpacesSupportedInWherePart()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN (1,2,3)');
    }

    public function testNotInExpressionSupportedInWherePart()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN (1)');
    }

    public function testInExpressionWithSingleValuedAssociationPathExpression()
    {
        $this->assertValidDQL("SELECT u FROM Doctrine\Tests\Models\Forum\ForumUser u WHERE u.avatar IN (?1, ?2)");
    }

    public function testInvalidInExpressionWithCollectionValuedAssociationPathExpression()
    {
        $this->assertInvalidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.phonenumbers IN (?1, ?2)");
    }

    public function testInstanceOfExpressionSupportedInWherePart()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF Doctrine\Tests\Models\Company\CompanyEmployee');
    }

    public function testInstanceOfExpressionWithInputParamSupportedInWherePart()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF ?1');
    }

    public function testNotInstanceOfExpressionSupportedInWherePart()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u NOT INSTANCE OF ?1');
    }

    public function testExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE EXISTS (SELECT p.phonenumber FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.phonenumber = 1234)');
    }

    public function testNotExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT EXISTS (SELECT p.phonenumber FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.phonenumber = 1234)');
    }

    public function testAggregateFunctionInHavingClause()
    {
        $this->assertValidDQL('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p HAVING COUNT(p.phonenumber) > 2');
        $this->assertValidDQL("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p HAVING MAX(u.name) = 'romanb'");
    }

    public function testLeftJoin()
    {
        $this->assertValidDQL('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p');
    }

    public function testJoin()
    {
        $this->assertValidDQL('SELECT u,p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p');
    }

    public function testInnerJoin()
    {
        $this->assertValidDQL('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.phonenumbers p');
    }

    public function testMultipleLeftJoin()
    {
        $this->assertValidDQL('SELECT u, a, p FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a LEFT JOIN u.phonenumbers p');
    }

    public function testMultipleInnerJoin()
    {
        $this->assertValidDQL('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a INNER JOIN u.phonenumbers p');
    }

    public function testMixingOfJoins()
    {
        $this->assertValidDQL('SELECT u.name, a.topic, p.phonenumber FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a LEFT JOIN u.phonenumbers p');
    }

    public function testJoinClassPath()
    {
        $this->assertValidDQL('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN Doctrine\Tests\Models\CMS\CmsArticle a WITH a.user = u.id');
    }

    public function testOrderBySingleColumn()
    {
        $this->assertValidDQL('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.name');
    }

    public function testOrderBySingleColumnAscending()
    {
        $this->assertValidDQL('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.name ASC');
    }

    public function testOrderBySingleColumnDescending()
    {
        $this->assertValidDQL('SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.name DESC');
    }

    public function testOrderByMultipleColumns()
    {
        $this->assertValidDQL('SELECT u.name, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username DESC, u.name DESC');
    }

    public function testSubselectInInExpression()
    {
        $this->assertValidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN (SELECT u2.id FROM Doctrine\Tests\Models\CMS\CmsUser u2 WHERE u2.name = 'zYne')");
    }

    public function testSubselectInSelectPart()
    {
        $this->assertValidDQL("SELECT u.name, (SELECT COUNT(p.phonenumber) FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.phonenumber = 1234) pcount FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = 'jon'");
    }

    public function testArithmeticExpressionInSelectPart()
    {
        $this->assertValidDQL("SELECT SUM(u.id) / COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u");
    }

    public function testArithmeticExpressionInSubselectPart()
    {
        $this->assertValidDQL("SELECT (SELECT SUM(u.id) / COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u2) value FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = 'jon'");
    }

    public function testArithmeticExpressionWithParenthesisInSubselectPart()
    {
        $this->assertValidDQL("SELECT (SELECT (SUM(u.id) / COUNT(u.id)) FROM Doctrine\Tests\Models\CMS\CmsUser u2) value FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = 'jon'");
    }

    /**
     * @group DDC-1079
     */
    public function testSelectLiteralInSubselect()
    {
        $this->assertValidDQL('SELECT (SELECT 1 FROM Doctrine\Tests\Models\CMS\CmsUser u2) value FROM Doctrine\Tests\Models\CMS\CmsUser u');
        $this->assertValidDQL('SELECT (SELECT 0 FROM Doctrine\Tests\Models\CMS\CmsUser u2) value FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    /**
     * @group DDC-1077
     */
    public function testConstantValueInSelect()
    {
        $this->assertValidDQL("SELECT u.name, 'foo' AS bar FROM Doctrine\Tests\Models\CMS\CmsUser u", true);
    }

    public function testDuplicateAliasInSubselectPart()
    {
        $this->assertInvalidDQL("SELECT (SELECT SUM(u.id) / COUNT(u.id) AS foo FROM Doctrine\Tests\Models\CMS\CmsUser u2) foo FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = 'jon'");
    }

    public function testPositionalInputParameter()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1');
    }

    public function testNamedInputParameter()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id');
    }

    public function testJoinConditionOverrideNotSupported()
    {
        $this->assertInvalidDQL("SELECT u.name, p FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.phonenumbers p ON p.phonenumber = '123 123'");
    }

    public function testIndexByClauseWithOneComponent()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u INDEX BY u.id');
    }

    public function testIndexBySupportsJoins()
    {
        $this->assertValidDQL('SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a INDEX BY a.id'); // INDEX BY is now referring to articles
    }

    public function testIndexBySupportsJoins2()
    {
        $this->assertValidDQL('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u INDEX BY u.id LEFT JOIN u.phonenumbers p INDEX BY p.phonenumber');
    }

    public function testBetweenExpressionSupported()
    {
        $this->assertValidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name BETWEEN 'jepso' AND 'zYne'");
    }

    public function testNotBetweenExpressionSupported()
    {
        $this->assertValidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name NOT BETWEEN 'jepso' AND 'zYne'");
    }

    public function testLikeExpression()
    {
        $this->assertValidDQL("SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name LIKE 'z%'");
    }

    public function testNotLikeExpression()
    {
        $this->assertValidDQL("SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name NOT LIKE 'z%'");
    }

    public function testLikeExpressionWithCustomEscapeCharacter()
    {
        $this->assertValidDQL("SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name LIKE 'z|%' ESCAPE '|'");
    }

    public function testFieldComparisonWithoutAlias()
    {
        $this->assertInvalidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE id = 1");
    }

    public function testDuplicatedAliasDeclaration()
    {
        $this->assertInvalidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles u WHERE u.id = 1");
    }

    public function testImplicitJoinInWhereOnSingleValuedAssociationPathExpression()
    {
        // This should be allowed because avatar is a single-value association.
        // SQL: SELECT ... FROM forum_user fu INNER JOIN forum_avatar fa ON fu.avatar_id = fa.id WHERE fa.id = ?
        $this->assertValidDQL("SELECT u FROM Doctrine\Tests\Models\Forum\ForumUser u JOIN u.avatar a WHERE a.id = ?1");
    }

    public function testImplicitJoinInWhereOnCollectionValuedPathExpression()
    {
        // This should be forbidden, because articles is a collection
        $this->assertInvalidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a WHERE a.title = ?");
    }

    public function testInvalidSyntaxIsRejected()
    {
        $this->assertInvalidDQL("FOOBAR CmsUser");
        $this->assertInvalidDQL("DELETE FROM Doctrine\Tests\Models\CMS\CmsUser.articles");
        $this->assertInvalidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles.comments");

        // Currently UNDEFINED OFFSET error
        $this->assertInvalidDQL("SELECT c FROM CmsUser.articles.comments c");
    }

    public function testUpdateWorksWithOneField()
    {
        $this->assertValidDQL("UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.name = 'someone'");
    }

    public function testUpdateWorksWithMultipleFields()
    {
        $this->assertValidDQL("UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.name = 'someone', u.username = 'some'");
    }

    public function testUpdateSupportsConditions()
    {
        $this->assertValidDQL("UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.name = 'someone' WHERE u.id = 5");
    }

    public function testDeleteAll()
    {
        $this->assertValidDQL('DELETE FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testDeleteWithCondition()
    {
        $this->assertValidDQL('DELETE FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = 3');
    }

    /**
     * The main use case for this generalized style of join is when a join condition
     * does not involve a foreign key relationship that is mapped to an entity relationship.
     */
    public function testImplicitJoinWithCartesianProductAndConditionInWhere()
    {
        $this->assertValidDQL("SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsArticle a WHERE u.name = a.topic");
    }

    public function testAllExpressionWithCorrelatedSubquery()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > ALL (SELECT u2.id FROM Doctrine\Tests\Models\CMS\CmsUser u2 WHERE u2.name = u.name)');
    }

    public function testCustomJoinsAndWithKeywordSupported()
    {
        $this->assertValidDQL('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.phonenumbers p WITH p.phonenumber = 123 WHERE u.id = 1');
    }

    public function testAnyExpressionWithCorrelatedSubquery()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > ANY (SELECT u2.id FROM Doctrine\Tests\Models\CMS\CmsUser u2 WHERE u2.name = u.name)');
    }

    public function testSomeExpressionWithCorrelatedSubquery()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > SOME (SELECT u2.id FROM Doctrine\Tests\Models\CMS\CmsUser u2 WHERE u2.name = u.name)');
    }

    public function testArithmeticExpressionWithoutParenthesisInWhereClause()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE SIZE(u.phonenumbers) + 1 > 10');
    }

    public function testMemberOfExpression()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE :param MEMBER OF u.phonenumbers');
        //$this->assertValidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE 'Joe' MEMBER OF u.nicknames");
    }

    public function testSizeFunction()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE SIZE(u.phonenumbers) > 1');
    }

    public function testEmptyCollectionComparisonExpression()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.phonenumbers IS EMPTY');
    }

    public function testSingleValuedAssociationFieldInWhere()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.address = ?1');
        $this->assertValidDQL('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.user = ?1');
    }

    public function testBooleanLiteralInWhere()
    {
        $this->assertValidDQL('SELECT b FROM Doctrine\Tests\Models\Generic\BooleanModel b WHERE b.booleanField = true');
    }

    public function testSubqueryInSelectExpression()
    {
        $this->assertValidDQL('select u, (select max(p.phonenumber) from Doctrine\Tests\Models\CMS\CmsPhonenumber p) maxId from Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testUsageOfQComponentOutsideSubquery()
    {
        $this->assertInvalidDQL('select u, (select max(p.phonenumber) from Doctrine\Tests\Models\CMS\CmsPhonenumber p) maxId from Doctrine\Tests\Models\CMS\CmsUser u WHERE p.user = ?1');
    }

    public function testUnknownAbstractSchemaName()
    {
        $this->assertInvalidDQL('SELECT u FROM UnknownClassName u');
    }

    public function testCorrectPartialObjectLoad()
    {
        $this->assertValidDQL('SELECT PARTIAL u.{id,name} FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testIncorrectPartialObjectLoadBecauseOfMissingIdentifier()
    {
        $this->assertInvalidDQL('SELECT PARTIAL u.{name} FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testScalarExpressionInSelect()
    {
        $this->assertValidDQL('SELECT u, 42 + u.id AS someNumber FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testInputParameterInSelect()
    {
        $this->assertValidDQL('SELECT u, u.id + ?1 AS someNumber FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    /**
     * @group DDC-1091
     */
    public function testCustomFunctionsReturningStringInStringPrimary()
    {
        $this->_em->getConfiguration()->addCustomStringFunction('CC', 'Doctrine\ORM\Query\AST\Functions\ConcatFunction');

        $this->assertValidDQL("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE CC('%', u.name) LIKE '%foo%'", true);
    }

    /**
     * @group DDC-505
     */
    public function testDQLKeywordInJoinIsAllowed()
    {
        $this->assertValidDQL('SELECT u FROM ' . __NAMESPACE__ . '\DQLKeywordsModelUser u JOIN u.group g');
    }

    /**
     * @group DDC-505
     */
    public function testDQLKeywordInConditionIsAllowed()
    {
        $this->assertValidDQL('SELECT g FROM ' . __NAMESPACE__ . '\DQLKeywordsModelGroup g WHERE g.from=0');
    }

    /* The exception is currently thrown in the SQLWalker, not earlier.
    public function testInverseSideSingleValuedAssociationPathNotAllowed()
    {
        $this->assertInvalidDQL('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.address = ?1');
    }
    */

    /**
     * @group DDC-617
     */
    public function testSelectOnlyNonRootEntityAlias()
    {
        $this->assertInvalidDQL('SELECT g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g');
    }

    /**
     * @group DDC-1108
     */
    public function testInputParameterSingleChar()
    {
        $this->assertValidDQL('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = :q');
    }

    /**
     * @group DDC-1053
     */
    public function testGroupBy()
    {
        $this->assertValidDQL('SELECT g.id, count(u.id) FROM Doctrine\Tests\Models\CMS\CmsGroup g JOIN g.users u GROUP BY g.id');
    }

    /**
     * @group DDC-1053
     */
    public function testGroupByIdentificationVariable()
    {
        $this->assertValidDQL('SELECT g, count(u.id) FROM Doctrine\Tests\Models\CMS\CmsGroup g JOIN g.users u GROUP BY g');
    }

    /**
     * @group DDC-1053
     */
    public function testGroupByUnknownIdentificationVariable()
    {
        $this->assertInvalidDQL('SELECT g, count(u.id) FROM Doctrine\Tests\Models\CMS\CmsGroup g JOIN g.users u GROUP BY m');
    }

    /**
     * @group DDC-117
     */
    public function testSizeOfForeignKeyOneToManyPrimaryKeyEntity()
    {
        $this->assertValidDQL("SELECT a, t FROM Doctrine\Tests\Models\DDC117\DDC117Article a JOIN a.translations t WHERE SIZE(a.translations) > 0");
    }

    /**
     * @group DDC-117
     */
    public function testSizeOfForeignKeyManyToManyPrimaryKeyEntity()
    {
        $this->assertValidDQL("SELECT e, t FROM Doctrine\Tests\Models\DDC117\DDC117Editor e JOIN e.reviewingTranslations t WHERE SIZE(e.reviewingTranslations) > 0");
    }

    public function testCaseSupportContainingNullIfExpression()
    {
        $this->assertValidDQL("SELECT u.id, NULLIF(u.name, u.name) AS shouldBeNull FROM Doctrine\Tests\Models\CMS\CmsUser u");
    }

    public function testCaseSupportContainingCoalesceExpression()
    {
        $this->assertValidDQL("select COALESCE(NULLIF(u.name, ''), u.username) as Display FROM Doctrine\Tests\Models\CMS\CmsUser u");
    }
}

/** @Entity */
class DQLKeywordsModelUser
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @OneToOne(targetEntity="DQLKeywordsModelGroup") */
    private $group;
}

/** @Entity */
class DQLKeywordsModelGroup
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column */
    private $from;
}
