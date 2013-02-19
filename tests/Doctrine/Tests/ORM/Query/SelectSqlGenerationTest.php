<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

class SelectSqlGenerationTest extends \Doctrine\Tests\OrmTestCase
{
    private $_em;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
    }

    /**
     * Assert a valid SQL generation.
     *
     * @param string $dqlToBeTested
     * @param string $sqlToBeConfirmed
     * @param array $queryHints
     * @param array $queryParams
     */
    public function assertSqlGeneration($dqlToBeTested, $sqlToBeConfirmed, array $queryHints = array(), array $queryParams = array())
    {
        try {
            $query = $this->_em->createQuery($dqlToBeTested);

            foreach ($queryParams AS $name => $value) {
                $query->setParameter($name, $value);
            }

            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                  ->useQueryCache(false);

            foreach ($queryHints AS $name => $value) {
                $query->setHint($name, $value);
            }

            $sqlGenerated = $query->getSQL();

            parent::assertEquals(
                $sqlToBeConfirmed,
                $sqlGenerated,
                sprintf('"%s" is not equal of "%s"', $sqlGenerated, $sqlToBeConfirmed)
            );

            $query->free();
        } catch (\Exception $e) {
            $this->fail($e->getMessage() ."\n".$e->getTraceAsString());
        }
    }

    /**
     * Asser an invalid SQL generation.
     *
     * @param string $dqlToBeTested
     * @param string $expectedException
     * @param array $queryHints
     * @param array $queryParams
     */
    public function assertInvalidSqlGeneration($dqlToBeTested, $expectedException, array $queryHints = array(), array $queryParams = array())
    {
        $this->setExpectedException($expectedException);

        $query = $this->_em->createQuery($dqlToBeTested);

        foreach ($queryParams AS $name => $value) {
            $query->setParameter($name, $value);
        }

        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
              ->useQueryCache(false);

        foreach ($queryHints AS $name => $value) {
            $query->setHint($name, $value);
        }

        $sql = $query->getSql();
        $query->free();

        // If we reached here, test failed
        $this->fail($sql);
    }


    public function testSupportsSelectForAllFields()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_'
        );
    }

    public function testSupportsSelectForOneField()
    {
        $this->assertSqlGeneration(
            'SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0_.id AS id0 FROM cms_users c0_'
        );
    }

    public function testSupportsSelectForOneNestedField()
    {
        $this->assertSqlGeneration(
            'SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsArticle a JOIN a.user u',
            'SELECT c0_.id AS id0 FROM cms_articles c1_ INNER JOIN cms_users c0_ ON c1_.user_id = c0_.id'
        );
    }

    public function testSupportsSelectForAllNestedField()
    {
        $this->assertSqlGeneration(
            'SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a JOIN a.user u ORDER BY u.name ASC',
            'SELECT c0_.id AS id0, c0_.topic AS topic1, c0_.text AS text2, c0_.version AS version3 FROM cms_articles c0_ INNER JOIN cms_users c1_ ON c0_.user_id = c1_.id ORDER BY c1_.name ASC'
        );
    }

    public function testNotExistsExpression()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT EXISTS (SELECT p.phonenumber FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.phonenumber = 1234)',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE NOT EXISTS (SELECT c1_.phonenumber FROM cms_phonenumbers c1_ WHERE c1_.phonenumber = 1234)'
        );
    }

    public function testSupportsSelectForMultipleColumnsOfASingleComponent()
    {
        $this->assertSqlGeneration(
            'SELECT u.username, u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0_.username AS username0, c0_.name AS name1 FROM cms_users c0_'
        );
    }

    public function testSupportsSelectUsingMultipleFromComponents()
    {
        $this->assertSqlGeneration(
            'SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE u = p.user',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c1_.phonenumber AS phonenumber4 FROM cms_users c0_, cms_phonenumbers c1_ WHERE c0_.id = c1_.user_id'
        );
    }

    public function testSupportsJoinOnMultipleComponents()
    {
        $this->assertSqlGeneration(
            'SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN Doctrine\Tests\Models\CMS\CmsPhonenumber p WITH u = p.user',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c1_.phonenumber AS phonenumber4 FROM cms_users c0_ INNER JOIN cms_phonenumbers c1_ ON (c0_.id = c1_.user_id)'
        );
    }

    public function testSupportsJoinOnMultipleComponentsWithJoinedInheritanceType()
    {
        $this->assertSqlGeneration(
            'SELECT e FROM Doctrine\Tests\Models\Company\CompanyEmployee e JOIN Doctrine\Tests\Models\Company\CompanyManager m WITH e.id = m.id',
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c1_.startDate AS startDate4, c0_.discr AS discr5 FROM company_employees c1_ INNER JOIN company_persons c0_ ON c1_.id = c0_.id INNER JOIN company_managers c2_ INNER JOIN company_employees c3_ ON c2_.id = c3_.id INNER JOIN company_persons c4_ ON c2_.id = c4_.id AND (c0_.id = c4_.id)'
        );

        $this->assertSqlGeneration(
            'SELECT e FROM Doctrine\Tests\Models\Company\CompanyEmployee e LEFT JOIN Doctrine\Tests\Models\Company\CompanyManager m WITH e.id = m.id',
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c1_.startDate AS startDate4, c0_.discr AS discr5 FROM company_employees c1_ INNER JOIN company_persons c0_ ON c1_.id = c0_.id LEFT JOIN company_managers c2_ INNER JOIN company_employees c3_ ON c2_.id = c3_.id INNER JOIN company_persons c4_ ON c2_.id = c4_.id ON (c0_.id = c4_.id)'
        );
    }

    public function testSupportsSelectWithCollectionAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c1_.phonenumber AS phonenumber4 FROM cms_users c0_ INNER JOIN cms_phonenumbers c1_ ON c0_.id = c1_.user_id'
        );
    }

    public function testSupportsSelectWithSingleValuedAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, a FROM Doctrine\Tests\Models\Forum\ForumUser u JOIN u.avatar a',
            'SELECT f0_.id AS id0, f0_.username AS username1, f1_.id AS id2 FROM forum_users f0_ INNER JOIN forum_avatars f1_ ON f0_.avatar_id = f1_.id'
        );
    }

    public function testSelectCorrelatedSubqueryComplexMathematicalExpression()
    {
        $this->assertSqlGeneration(
            'SELECT (SELECT (count(p.phonenumber)+5)*10 FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p JOIN p.user ui WHERE ui.id = u.id) AS c FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT (SELECT (count(c0_.phonenumber) + 5) * 10 AS sclr1 FROM cms_phonenumbers c0_ INNER JOIN cms_users c1_ ON c0_.user_id = c1_.id WHERE c1_.id = c2_.id) AS sclr0 FROM cms_users c2_'
        );
    }

    public function testSelectComplexMathematicalExpression()
    {
        $this->assertSqlGeneration(
            'SELECT (count(p.phonenumber)+5)*10 FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p JOIN p.user ui WHERE ui.id = ?1',
            'SELECT (count(c0_.phonenumber) + 5) * 10 AS sclr0 FROM cms_phonenumbers c0_ INNER JOIN cms_users c1_ ON c0_.user_id = c1_.id WHERE c1_.id = ?'
        );
    }

    /* NOT (YET?) SUPPORTED.
       Can be supported if SimpleSelectExpression supports SingleValuedPathExpression instead of StateFieldPathExpression.

    public function testSingleAssociationPathExpressionInSubselect()
    {
        $this->assertSqlGeneration(
            'SELECT (SELECT p.user FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.user = u) user_id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1',
            'SELECT (SELECT c0_.user_id FROM cms_phonenumbers c0_ WHERE c0_.user_id = c1_.id) AS sclr0 FROM cms_users c1_ WHERE c1_.id = ?'
        );
    }*/

    /**
     * @group DDC-1077
     */
    public function testConstantValueInSelect()
    {
        $this->assertSqlGeneration(
            "SELECT u.name, 'foo' AS bar FROM Doctrine\Tests\Models\CMS\CmsUser u",
            "SELECT c0_.name AS name0, 'foo' AS sclr1 FROM cms_users c0_"
        );
    }

    public function testSupportsOrderByWithAscAsDefault()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\Forum\ForumUser u ORDER BY u.id',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ ORDER BY f0_.id ASC'
        );
    }

    public function testSupportsOrderByAsc()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\Forum\ForumUser u ORDER BY u.id asc',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ ORDER BY f0_.id ASC'
        );
    }
    public function testSupportsOrderByDesc()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\Forum\ForumUser u ORDER BY u.id desc',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ ORDER BY f0_.id DESC'
        );
    }

    public function testSupportsSelectDistinct()
    {
        $this->assertSqlGeneration(
            'SELECT DISTINCT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT DISTINCT c0_.name AS name0 FROM cms_users c0_'
        );
    }

    public function testSupportsAggregateFunctionInSelectedFields()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id',
            'SELECT COUNT(c0_.id) AS sclr0 FROM cms_users c0_ GROUP BY c0_.id'
        );
    }

    public function testSupportsAggregateFunctionWithSimpleArithmetic()
    {
        $this->assertSqlGeneration(
            'SELECT MAX(u.id + 4) * 2 FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT MAX(c0_.id + 4) * 2 AS sclr0 FROM cms_users c0_'
        );
    }

    public function testSupportsWhereClauseWithPositionalParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.id = ?1',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ WHERE f0_.id = ?'
        );
    }

    public function testSupportsWhereClauseWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ WHERE f0_.username = ?'
        );
    }

    public function testSupportsWhereAndClauseWithNamedParameters()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name and u.username = :name2',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ WHERE f0_.username = ? AND f0_.username = ?'
        );
    }

    public function testSupportsCombinedWhereClauseWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where (u.username = :name OR u.username = :name2) AND u.id = :id',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ WHERE (f0_.username = ? OR f0_.username = ?) AND f0_.id = ?'
        );
    }

    public function testSupportsAggregateFunctionInASelectDistinct()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(DISTINCT u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT COUNT(DISTINCT c0_.name) AS sclr0 FROM cms_users c0_'
        );
    }

    // Ticket #668
    public function testSupportsASqlKeywordInAStringLiteralParam()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name LIKE '%foo OR bar%'",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE c0_.name LIKE '%foo OR bar%'"
        );
    }

    public function testSupportsArithmeticExpressionsInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (c0_.id + 5000) * c0_.id + 3 < 10000000'
        );
    }

    public function testSupportsMultipleEntitiesInFromClause()
    {
        $this->assertSqlGeneration(
            'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsArticle a JOIN a.user u2 WHERE u.id = u2.id',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c1_.id AS id4, c1_.topic AS topic5, c1_.text AS text6, c1_.version AS version7 FROM cms_users c0_, cms_articles c1_ INNER JOIN cms_users c2_ ON c1_.user_id = c2_.id WHERE c0_.id = c2_.id'
        );
    }

    public function testSupportsMultipleEntitiesInFromClauseUsingPathExpression()
    {
        $this->assertSqlGeneration(
            'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsArticle a WHERE u.id = a.user',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c1_.id AS id4, c1_.topic AS topic5, c1_.text AS text6, c1_.version AS version7 FROM cms_users c0_, cms_articles c1_ WHERE c0_.id = c1_.user_id'
        );
    }

    public function testSupportsPlainJoinWithoutClause()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a',
            'SELECT c0_.id AS id0, c1_.id AS id1 FROM cms_users c0_ LEFT JOIN cms_articles c1_ ON c0_.id = c1_.user_id'
        );
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a',
            'SELECT c0_.id AS id0, c1_.id AS id1 FROM cms_users c0_ INNER JOIN cms_articles c1_ ON c0_.id = c1_.user_id'
        );
    }

    /**
     * @group DDC-135
     */
    public function testSupportsJoinAndWithClauseRestriction()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a WITH a.topic LIKE '%foo%'",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ LEFT JOIN cms_articles c1_ ON c0_.id = c1_.user_id AND (c1_.topic LIKE '%foo%')"
        );
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a WITH a.topic LIKE '%foo%'",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ INNER JOIN cms_articles c1_ ON c0_.id = c1_.user_id AND (c1_.topic LIKE '%foo%')"
        );
    }

    /**
     * @group DDC-135
     * @group DDC-177
     */
    public function testJoinOnClause_NotYetSupported_ThrowsException()
    {
        $this->setExpectedException('Doctrine\ORM\Query\QueryException');

        $sql = $this->_em->createQuery(
            "SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a ON a.topic LIKE '%foo%'"
        )->getSql();
    }

    public function testSupportsMultipleJoins()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id, p.phonenumber, c.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a JOIN u.phonenumbers p JOIN a.comments c',
            'SELECT c0_.id AS id0, c1_.id AS id1, c2_.phonenumber AS phonenumber2, c3_.id AS id3 FROM cms_users c0_ INNER JOIN cms_articles c1_ ON c0_.id = c1_.user_id INNER JOIN cms_phonenumbers c2_ ON c0_.id = c2_.user_id INNER JOIN cms_comments c3_ ON c1_.id = c3_.article_id'
        );
    }

    public function testSupportsTrimFunction()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(TRAILING ' ' FROM u.name) = 'someone'",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE TRIM(TRAILING ' ' FROM c0_.name) = 'someone'"
        );
    }

    // Ticket 894
    public function testSupportsBetweenClauseWithPositionalParameters()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id BETWEEN ?1 AND ?2",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE c0_.id BETWEEN ? AND ?"
        );
    }

    /**
     * @group DDC-1802
     */
    public function testSupportsNotBetweenForSizeFunction()
    {
        $this->assertSqlGeneration(
            "SELECT m.name FROM Doctrine\Tests\Models\StockExchange\Market m WHERE SIZE(m.stocks) NOT BETWEEN ?1 AND ?2",
            "SELECT e0_.name AS name0 FROM exchange_markets e0_ WHERE (SELECT COUNT(*) FROM exchange_stocks e1_ WHERE e1_.market_id = e0_.id) NOT BETWEEN ? AND ?"
        );
    }

    public function testSupportsFunctionalExpressionsInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(u.name) = 'someone'",
            // String quoting in the SQL usually depends on the database platform.
            // This test works with a mock connection which uses ' for string quoting.
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE TRIM(c0_.name) = 'someone'"
        );
    }

    public function testSupportsInstanceOfExpressionsInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF Doctrine\Tests\Models\Company\CompanyEmployee",
            "SELECT c0_.id AS id0, c0_.name AS name1, c0_.discr AS discr2 FROM company_persons c0_ WHERE c0_.discr IN ('employee')"
        );
    }

    public function testSupportsInstanceOfExpressionInWherePartWithMultipleValues()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF (Doctrine\Tests\Models\Company\CompanyEmployee, \Doctrine\Tests\Models\Company\CompanyManager)",
            "SELECT c0_.id AS id0, c0_.name AS name1, c0_.discr AS discr2 FROM company_persons c0_ WHERE c0_.discr IN ('employee', 'manager')"
        );
    }

    /**
     * @group DDC-1194
     */
    public function testSupportsInstanceOfExpressionsInWherePartPrefixedSlash()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF \Doctrine\Tests\Models\Company\CompanyEmployee",
            "SELECT c0_.id AS id0, c0_.name AS name1, c0_.discr AS discr2 FROM company_persons c0_ WHERE c0_.discr IN ('employee')"
        );
    }

    /**
     * @group DDC-1194
     */
    public function testSupportsInstanceOfExpressionsInWherePartWithUnrelatedClass()
    {
        $this->assertInvalidSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF \Doctrine\Tests\Models\CMS\CmsUser",
            "Doctrine\ORM\Query\QueryException"
        );
    }

    public function testSupportsInstanceOfExpressionsInWherePartInDeeperLevel()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyEmployee u WHERE u INSTANCE OF Doctrine\Tests\Models\Company\CompanyManager",
            "SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c1_.startDate AS startDate4, c0_.discr AS discr5 FROM company_employees c1_ INNER JOIN company_persons c0_ ON c1_.id = c0_.id WHERE c0_.discr IN ('manager')"
        );
    }

    public function testSupportsInstanceOfExpressionsInWherePartInDeepestLevel()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyManager u WHERE u INSTANCE OF Doctrine\Tests\Models\Company\CompanyManager",
            "SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c1_.startDate AS startDate4, c2_.title AS title5, c0_.discr AS discr6 FROM company_managers c2_ INNER JOIN company_employees c1_ ON c2_.id = c1_.id INNER JOIN company_persons c0_ ON c2_.id = c0_.id WHERE c0_.discr IN ('manager')"
        );
    }

    public function testSupportsInstanceOfExpressionsUsingInputParameterInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF ?1",
            "SELECT c0_.id AS id0, c0_.name AS name1, c0_.discr AS discr2 FROM company_persons c0_ WHERE c0_.discr IN ('employee')",
            array(), array(1 => $this->_em->getClassMetadata('Doctrine\Tests\Models\Company\CompanyEmployee'))
        );
    }

    // Ticket #973
    public function testSupportsSingleValuedInExpressionWithoutSpacesInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE IDENTITY(u.email) IN(46)",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE c0_.email_id IN (46)"
        );
    }

    public function testSupportsMultipleValuedInExpressionInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN (1, 2)',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.id IN (1, 2)'
        );
    }

    public function testSupportsNotInExpressionInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE :id NOT IN (1)',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE ? NOT IN (1)'
        );
    }

    /**
     * @group DDC-1802
     */
    public function testSupportsNotInExpressionForModFunction()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE MOD(u.id, 5) NOT IN(1,3,4)",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE MOD(c0_.id, 5) NOT IN (1, 3, 4)"
        );
    }

    public function testInExpressionWithSingleValuedAssociationPathExpressionInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\Forum\ForumUser u WHERE u.avatar IN (?1, ?2)',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ WHERE f0_.avatar_id IN (?, ?)'
        );
    }

    public function testInvalidInExpressionWithSingleValuedAssociationPathExpressionOnInverseSide()
    {
        // We do not support SingleValuedAssociationPathExpression on inverse side
        $this->assertInvalidSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.address IN (?1, ?2)",
            "Doctrine\ORM\Query\QueryException"
        );
    }

    public function testSupportsConcatFunctionForMysqlAndPostgresql()
    {
        $connMock = $this->_em->getConnection();
        $orgPlatform = $connMock->getDatabasePlatform();

        $connMock->setDatabasePlatform(new \Doctrine\DBAL\Platforms\MySqlPlatform);
        $this->assertSqlGeneration(
            "SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE CONCAT(u.name, 's') = ?1",
            "SELECT c0_.id AS id0 FROM cms_users c0_ WHERE CONCAT(c0_.name, 's') = ?"
        );
        $this->assertSqlGeneration(
            "SELECT CONCAT(u.id, u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1",
            "SELECT CONCAT(c0_.id, c0_.name) AS sclr0 FROM cms_users c0_ WHERE c0_.id = ?"
        );

        $connMock->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);
        $this->assertSqlGeneration(
            "SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE CONCAT(u.name, 's') = ?1",
            "SELECT c0_.id AS id0 FROM cms_users c0_ WHERE c0_.name || 's' = ?"
        );
        $this->assertSqlGeneration(
            "SELECT CONCAT(u.id, u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1",
            "SELECT c0_.id || c0_.name AS sclr0 FROM cms_users c0_ WHERE c0_.id = ?"
        );

        $connMock->setDatabasePlatform($orgPlatform);
    }

    public function testSupportsExistsExpressionInWherePartWithCorrelatedSubquery()
    {
        $this->assertSqlGeneration(
            'SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE EXISTS (SELECT p.phonenumber FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.phonenumber = u.id)',
            'SELECT c0_.id AS id0 FROM cms_users c0_ WHERE EXISTS (SELECT c1_.phonenumber FROM cms_phonenumbers c1_ WHERE c1_.phonenumber = c0_.id)'
        );
    }

    /**
     * @group DDC-593
     */
    public function testSubqueriesInComparisonExpression()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id >= (SELECT u2.id FROM Doctrine\Tests\Models\CMS\CmsUser u2 WHERE u2.name = :name)) AND (u.id <= (SELECT u3.id FROM Doctrine\Tests\Models\CMS\CmsUser u3 WHERE u3.name = :name))',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (c0_.id >= (SELECT c1_.id FROM cms_users c1_ WHERE c1_.name = ?)) AND (c0_.id <= (SELECT c2_.id FROM cms_users c2_ WHERE c2_.name = ?))'
        );
    }

    public function testSupportsMemberOfExpressionOneToMany()
    {
        // "Get all users who have $phone as a phonenumber." (*cough* doesnt really make sense...)
        $q = $this->_em->createQuery('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE :param MEMBER OF u.phonenumbers');
        $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        $phone = new \Doctrine\Tests\Models\CMS\CmsPhonenumber;
        $phone->phonenumber = 101;
        $q->setParameter('param', $phone);

        $this->assertEquals(
            'SELECT c0_.id AS id0 FROM cms_users c0_ WHERE EXISTS (SELECT 1 FROM cms_phonenumbers c1_ WHERE c0_.id = c1_.user_id AND c1_.phonenumber = ?)',
            $q->getSql()
        );
    }

    public function testSupportsMemberOfExpressionManyToMany()
    {
        // "Get all users who are members of $group."
        $q = $this->_em->createQuery('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE :param MEMBER OF u.groups');
        $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        $group = new \Doctrine\Tests\Models\CMS\CmsGroup;
        $group->id = 101;
        $q->setParameter('param', $group);

        $this->assertEquals(
            'SELECT c0_.id AS id0 FROM cms_users c0_ WHERE EXISTS (SELECT 1 FROM cms_users_groups c1_ INNER JOIN cms_groups c2_ ON c1_.group_id = c2_.id WHERE c1_.user_id = c0_.id AND c2_.id = ?)',
            $q->getSql()
        );
    }

    public function testSupportsMemberOfExpressionSelfReferencing()
    {
        // "Get all persons who have $person as a friend."
        // Tough one: Many-many self-referencing ("friends") with class table inheritance
        $q = $this->_em->createQuery('SELECT p FROM Doctrine\Tests\Models\Company\CompanyPerson p WHERE :param MEMBER OF p.friends');
        $person = new \Doctrine\Tests\Models\Company\CompanyPerson;
        $this->_em->getClassMetadata(get_class($person))->setIdentifierValues($person, array('id' => 101));
        $q->setParameter('param', $person);
        $this->assertEquals(
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.title AS title2, c2_.salary AS salary3, c2_.department AS department4, c2_.startDate AS startDate5, c0_.discr AS discr6, c0_.spouse_id AS spouse_id7, c1_.car_id AS car_id8 FROM company_persons c0_ LEFT JOIN company_managers c1_ ON c0_.id = c1_.id LEFT JOIN company_employees c2_ ON c0_.id = c2_.id WHERE EXISTS (SELECT 1 FROM company_persons_friends c3_ INNER JOIN company_persons c4_ ON c3_.friend_id = c4_.id WHERE c3_.person_id = c0_.id AND c4_.id = ?)',
            $q->getSql()
        );
    }

    public function testSupportsMemberOfWithSingleValuedAssociation()
    {
        // Impossible example, but it illustrates the purpose
        $q = $this->_em->createQuery('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.email MEMBER OF u.groups');

        $this->assertEquals(
            'SELECT c0_.id AS id0 FROM cms_users c0_ WHERE EXISTS (SELECT 1 FROM cms_users_groups c1_ INNER JOIN cms_groups c2_ ON c1_.group_id = c2_.id WHERE c1_.user_id = c0_.id AND c2_.id = c0_.email_id)',
            $q->getSql()
        );
    }

    public function testSupportsMemberOfWithIdentificationVariable()
    {
        // Impossible example, but it illustrates the purpose
        $q = $this->_em->createQuery('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u MEMBER OF u.groups');

        $this->assertEquals(
            'SELECT c0_.id AS id0 FROM cms_users c0_ WHERE EXISTS (SELECT 1 FROM cms_users_groups c1_ INNER JOIN cms_groups c2_ ON c1_.group_id = c2_.id WHERE c1_.user_id = c0_.id AND c2_.id = c0_.id)',
            $q->getSql()
        );
    }

    public function testSupportsCurrentDateFunction()
    {
        $q = $this->_em->createQuery('SELECT d.id FROM Doctrine\Tests\Models\Generic\DateTimeModel d WHERE d.datetime > current_date()');
        $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $this->assertEquals('SELECT d0_.id AS id0 FROM date_time_model d0_ WHERE d0_.col_datetime > CURRENT_DATE', $q->getSql());
    }

    public function testSupportsCurrentTimeFunction()
    {
        $q = $this->_em->createQuery('SELECT d.id FROM Doctrine\Tests\Models\Generic\DateTimeModel d WHERE d.time > current_time()');
        $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $this->assertEquals('SELECT d0_.id AS id0 FROM date_time_model d0_ WHERE d0_.col_time > CURRENT_TIME', $q->getSql());
    }

    public function testSupportsCurrentTimestampFunction()
    {
        $q = $this->_em->createQuery('SELECT d.id FROM Doctrine\Tests\Models\Generic\DateTimeModel d WHERE d.datetime > current_timestamp()');
        $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $this->assertEquals('SELECT d0_.id AS id0 FROM date_time_model d0_ WHERE d0_.col_datetime > CURRENT_TIMESTAMP', $q->getSql());
    }

    public function testExistsExpressionInWhereCorrelatedSubqueryAssocCondition()
    {
        $this->assertSqlGeneration(
            // DQL
            // The result of this query consists of all employees whose spouses are also employees.
            'SELECT DISTINCT emp FROM Doctrine\Tests\Models\CMS\CmsEmployee emp
                WHERE EXISTS (
                    SELECT spouseEmp
                    FROM Doctrine\Tests\Models\CMS\CmsEmployee spouseEmp
                    WHERE spouseEmp = emp.spouse)',
            // SQL
            'SELECT DISTINCT c0_.id AS id0, c0_.name AS name1 FROM cms_employees c0_'
                . ' WHERE EXISTS ('
                    . 'SELECT c1_.id FROM cms_employees c1_ WHERE c1_.id = c0_.spouse_id'
                    . ')'
        );
    }

    public function testExistsExpressionWithSimpleSelectReturningScalar()
    {
        $this->assertSqlGeneration(
            // DQL
            // The result of this query consists of all employees whose spouses are also employees.
            'SELECT DISTINCT emp FROM Doctrine\Tests\Models\CMS\CmsEmployee emp
                WHERE EXISTS (
                    SELECT 1
                    FROM Doctrine\Tests\Models\CMS\CmsEmployee spouseEmp
                    WHERE spouseEmp = emp.spouse)',
            // SQL
            'SELECT DISTINCT c0_.id AS id0, c0_.name AS name1 FROM cms_employees c0_'
                . ' WHERE EXISTS ('
                    . 'SELECT 1 AS sclr2 FROM cms_employees c1_ WHERE c1_.id = c0_.spouse_id'
                    . ')'
        );
    }

    public function testLimitFromQueryClass()
    {
        $q = $this->_em
            ->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u')
            ->setMaxResults(10);

        $this->assertEquals('SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c0_.email_id AS email_id4 FROM cms_users c0_ LIMIT 10', $q->getSql());
    }

    public function testLimitAndOffsetFromQueryClass()
    {
        $q = $this->_em
            ->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u')
            ->setMaxResults(10)
            ->setFirstResult(0);

        $this->assertEquals('SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c0_.email_id AS email_id4 FROM cms_users c0_ LIMIT 10 OFFSET 0', $q->getSql());
    }

    public function testSizeFunction()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE SIZE(u.phonenumbers) > 1",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (SELECT COUNT(*) FROM cms_phonenumbers c1_ WHERE c1_.user_id = c0_.id) > 1"
        );
    }

    public function testSizeFunctionSupportsManyToMany()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE SIZE(u.groups) > 1",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (SELECT COUNT(*) FROM cms_users_groups c1_ WHERE c1_.user_id = c0_.id) > 1"
        );
    }

    public function testEmptyCollectionComparisonExpression()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.phonenumbers IS EMPTY",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (SELECT COUNT(*) FROM cms_phonenumbers c1_ WHERE c1_.user_id = c0_.id) = 0"
        );
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.phonenumbers IS NOT EMPTY",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (SELECT COUNT(*) FROM cms_phonenumbers c1_ WHERE c1_.user_id = c0_.id) > 0"
        );
    }

    public function testNestedExpressions()
    {
        $this->assertSqlGeneration(
            "select u from Doctrine\Tests\Models\CMS\CmsUser u where u.id > 10 and u.id < 42 and ((u.id * 2) > 5)",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.id > 10 AND c0_.id < 42 AND (c0_.id * 2 > 5)"
        );
    }

    public function testNestedExpressions2()
    {
        $this->assertSqlGeneration(
            "select u from Doctrine\Tests\Models\CMS\CmsUser u where (u.id > 10) and (u.id < 42 and ((u.id * 2) > 5)) or u.id <> 42",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (c0_.id > 10) AND (c0_.id < 42 AND (c0_.id * 2 > 5)) OR c0_.id <> 42"
        );
    }

    public function testNestedExpressions3()
    {
        $this->assertSqlGeneration(
            "select u from Doctrine\Tests\Models\CMS\CmsUser u where (u.id > 10) and (u.id between 1 and 10 or u.id in (1, 2, 3, 4, 5))",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (c0_.id > 10) AND (c0_.id BETWEEN 1 AND 10 OR c0_.id IN (1, 2, 3, 4, 5))"
        );
    }

    public function testOrderByCollectionAssociationSize()
    {
        $this->assertSqlGeneration(
            "select u, size(u.articles) as numArticles from Doctrine\Tests\Models\CMS\CmsUser u order by numArticles",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, (SELECT COUNT(*) FROM cms_articles c1_ WHERE c1_.user_id = c0_.id) AS sclr4 FROM cms_users c0_ ORDER BY sclr4 ASC"
        );
    }

    public function testOrderBySupportsSingleValuedPathExpressionOwningSide()
    {
        $this->assertSqlGeneration(
            "select a from Doctrine\Tests\Models\CMS\CmsArticle a order by a.user",
            "SELECT c0_.id AS id0, c0_.topic AS topic1, c0_.text AS text2, c0_.version AS version3 FROM cms_articles c0_ ORDER BY c0_.user_id ASC"
        );
    }

    /**
     * @expectedException Doctrine\ORM\Query\QueryException
     */
    public function testOrderBySupportsSingleValuedPathExpressionInverseSide()
    {
        $q = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u order by u.address");
        $q->getSQL();
    }

    public function testBooleanLiteralInWhereOnSqlite()
    {
        $oldPlat = $this->_em->getConnection()->getDatabasePlatform();
        $this->_em->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\SqlitePlatform);

        $this->assertSqlGeneration(
            "SELECT b FROM Doctrine\Tests\Models\Generic\BooleanModel b WHERE b.booleanField = true",
            "SELECT b0_.id AS id0, b0_.booleanField AS booleanField1 FROM boolean_model b0_ WHERE b0_.booleanField = 1"
        );

        $this->assertSqlGeneration(
            "SELECT b FROM Doctrine\Tests\Models\Generic\BooleanModel b WHERE b.booleanField = false",
            "SELECT b0_.id AS id0, b0_.booleanField AS booleanField1 FROM boolean_model b0_ WHERE b0_.booleanField = 0"
        );

        $this->_em->getConnection()->setDatabasePlatform($oldPlat);
    }

    public function testBooleanLiteralInWhereOnPostgres()
    {
        $oldPlat = $this->_em->getConnection()->getDatabasePlatform();
        $this->_em->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);

        $this->assertSqlGeneration(
            "SELECT b FROM Doctrine\Tests\Models\Generic\BooleanModel b WHERE b.booleanField = true",
            "SELECT b0_.id AS id0, b0_.booleanField AS booleanfield1 FROM boolean_model b0_ WHERE b0_.booleanField = true"
        );

        $this->assertSqlGeneration(
            "SELECT b FROM Doctrine\Tests\Models\Generic\BooleanModel b WHERE b.booleanField = false",
            "SELECT b0_.id AS id0, b0_.booleanField AS booleanfield1 FROM boolean_model b0_ WHERE b0_.booleanField = false"
        );

        $this->_em->getConnection()->setDatabasePlatform($oldPlat);
    }

    public function testSingleValuedAssociationFieldInWhere()
    {
        $this->assertSqlGeneration(
            "SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.user = ?1",
            "SELECT c0_.phonenumber AS phonenumber0 FROM cms_phonenumbers c0_ WHERE c0_.user_id = ?"
        );
    }

    public function testSingleValuedAssociationNullCheckOnOwningSide()
    {
        $this->assertSqlGeneration(
            "SELECT a FROM Doctrine\Tests\Models\CMS\CmsAddress a WHERE a.user IS NULL",
            "SELECT c0_.id AS id0, c0_.country AS country1, c0_.zip AS zip2, c0_.city AS city3 FROM cms_addresses c0_ WHERE c0_.user_id IS NULL"
        );
    }

    // Null check on inverse side has to happen through explicit JOIN.
    // "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.address IS NULL"
    // where the CmsUser is the inverse side is not supported.
    public function testSingleValuedAssociationNullCheckOnInverseSide()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.address a WHERE a.id IS NULL",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ LEFT JOIN cms_addresses c1_ ON c0_.id = c1_.user_id WHERE c1_.id IS NULL"
        );
    }

    /**
     * @group DDC-339
     * @group DDC-1572
     */
    public function testStringFunctionLikeExpression()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE LOWER(u.name) LIKE '%foo OR bar%'",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE LOWER(c0_.name) LIKE '%foo OR bar%'"
        );
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE LOWER(u.name) LIKE :str",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE LOWER(c0_.name) LIKE ?"
        );
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE CONCAT(UPPER(u.name), '_moo') LIKE :str",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE UPPER(c0_.name) || '_moo' LIKE ?"
        );

        // DDC-1572
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE UPPER(u.name) LIKE UPPER(:str)",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE UPPER(c0_.name) LIKE UPPER(?)"
        );
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE UPPER(LOWER(u.name)) LIKE UPPER(LOWER(:str))",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE UPPER(LOWER(c0_.name)) LIKE UPPER(LOWER(?))"
        );
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a WITH a.topic LIKE u.name",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ LEFT JOIN cms_articles c1_ ON c0_.id = c1_.user_id AND (c1_.topic LIKE c0_.name)"
        );
    }

    /**
     * @group DDC-1802
     */
    public function testStringFunctionNotLikeExpression()
    {
        $this->assertSqlGeneration(
                "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE LOWER(u.name) NOT LIKE '%foo OR bar%'",
                "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE LOWER(c0_.name) NOT LIKE '%foo OR bar%'"
        );

        $this->assertSqlGeneration(
                "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE UPPER(LOWER(u.name)) NOT LIKE UPPER(LOWER(:str))",
                "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE UPPER(LOWER(c0_.name)) NOT LIKE UPPER(LOWER(?))"
        );
        $this->assertSqlGeneration(
                "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a WITH a.topic NOT LIKE u.name",
                "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ LEFT JOIN cms_articles c1_ ON c0_.id = c1_.user_id AND (c1_.topic NOT LIKE c0_.name)"
        );
    }

    /**
     * @group DDC-338
     */
    public function testOrderedCollectionFetchJoined()
    {
        $this->assertSqlGeneration(
            "SELECT r, l FROM Doctrine\Tests\Models\Routing\RoutingRoute r JOIN r.legs l",
            "SELECT r0_.id AS id0, r1_.id AS id1, r1_.departureDate AS departureDate2, r1_.arrivalDate AS arrivalDate3 FROM RoutingRoute r0_ INNER JOIN RoutingRouteLegs r2_ ON r0_.id = r2_.route_id INNER JOIN RoutingLeg r1_ ON r1_.id = r2_.leg_id ".
            "ORDER BY r1_.departureDate ASC"
        );
    }

    public function testSubselectInSelect()
    {
        $this->assertSqlGeneration(
            "SELECT u.name, (SELECT COUNT(p.phonenumber) FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.phonenumber = 1234) pcount FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = 'jon'",
            "SELECT c0_.name AS name0, (SELECT COUNT(c1_.phonenumber) AS dctrn__1 FROM cms_phonenumbers c1_ WHERE c1_.phonenumber = 1234) AS sclr1 FROM cms_users c0_ WHERE c0_.name = 'jon'"
        );
    }

    /**
     * @group locking
     * @group DDC-178
     */
    public function testPessimisticWriteLockQueryHint()
    {
        if ($this->_em->getConnection()->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform) {
            $this->markTestSkipped('SqLite does not support Row locking at all.');
        }

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = 'gblanco'",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 ".
            "FROM cms_users c0_ WHERE c0_.username = 'gblanco' FOR UPDATE",
            array(Query::HINT_LOCK_MODE => \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
        );
    }

    /**
     * @group locking
     * @group DDC-178
     */
    public function testPessimisticReadLockQueryHintPostgreSql()
    {
        $this->_em->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = 'gblanco'",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 ".
            "FROM cms_users c0_ WHERE c0_.username = 'gblanco' FOR SHARE",
            array(Query::HINT_LOCK_MODE => \Doctrine\DBAL\LockMode::PESSIMISTIC_READ)
                );
    }

    /**
     * @group DDC-1693
     * @group locking
     */
    public function testLockModeNoneQueryHint()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = 'gblanco'",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 ".
            "FROM cms_users c0_ WHERE c0_.username = 'gblanco'",
            array(Query::HINT_LOCK_MODE => \Doctrine\DBAL\LockMode::NONE)
                );
    }

    /**
     * @group DDC-430
     */
    public function testSupportSelectWithMoreThan10InputParameters()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1 OR u.id = ?2 OR u.id = ?3 OR u.id = ?4 OR u.id = ?5 OR u.id = ?6 OR u.id = ?7 OR u.id = ?8 OR u.id = ?9 OR u.id = ?10 OR u.id = ?11",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.id = ? OR c0_.id = ? OR c0_.id = ? OR c0_.id = ? OR c0_.id = ? OR c0_.id = ? OR c0_.id = ? OR c0_.id = ? OR c0_.id = ? OR c0_.id = ? OR c0_.id = ?"
        );
    }

    /**
     * @group locking
     * @group DDC-178
     */
    public function testPessimisticReadLockQueryHintMySql()
    {
        $this->_em->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\MySqlPlatform);

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = 'gblanco'",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 ".
            "FROM cms_users c0_ WHERE c0_.username = 'gblanco' LOCK IN SHARE MODE",
            array(Query::HINT_LOCK_MODE => \Doctrine\DBAL\LockMode::PESSIMISTIC_READ)
        );
    }

    /**
     * @group locking
     * @group DDC-178
     */
    public function testPessimisticReadLockQueryHintOracle()
    {
        $this->_em->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\OraclePlatform);

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = 'gblanco'",
            "SELECT c0_.id AS ID0, c0_.status AS STATUS1, c0_.username AS USERNAME2, c0_.name AS NAME3 ".
            "FROM cms_users c0_ WHERE c0_.username = 'gblanco' FOR UPDATE",
            array(Query::HINT_LOCK_MODE => \Doctrine\DBAL\LockMode::PESSIMISTIC_READ)
        );
    }

    /**
     * @group DDC-431
     */
    public function testSupportToCustomDQLFunctions()
    {
        $config = $this->_em->getConfiguration();
        $config->addCustomNumericFunction('MYABS', 'Doctrine\Tests\ORM\Query\MyAbsFunction');

        $this->assertSqlGeneration(
            'SELECT MYABS(p.phonenumber) FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p',
            'SELECT ABS(c0_.phonenumber) AS sclr0 FROM cms_phonenumbers c0_'
        );

        $config->setCustomNumericFunctions(array());
    }

    /**
     * @group DDC-826
     */
    public function testMappedSuperclassAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT f FROM Doctrine\Tests\Models\DirectoryTree\File f JOIN f.parentDirectory d WHERE f.id = ?1',
            'SELECT f0_.id AS id0, f0_.extension AS extension1, f0_.name AS name2 FROM "file" f0_ INNER JOIN Directory d1_ ON f0_.parentDirectory_id = d1_.id WHERE f0_.id = ?'
        );
    }

    /**
     * @group DDC-1053
     */
    public function testGroupBy()
    {
        $this->assertSqlGeneration(
            'SELECT g.id, count(u.id) FROM Doctrine\Tests\Models\CMS\CmsGroup g JOIN g.users u GROUP BY g.id',
            'SELECT c0_.id AS id0, count(c1_.id) AS sclr1 FROM cms_groups c0_ INNER JOIN cms_users_groups c2_ ON c0_.id = c2_.group_id INNER JOIN cms_users c1_ ON c1_.id = c2_.user_id GROUP BY c0_.id'
        );
    }

    /**
     * @group DDC-1053
     */
    public function testGroupByIdentificationVariable()
    {
        $this->assertSqlGeneration(
            'SELECT g, count(u.id) FROM Doctrine\Tests\Models\CMS\CmsGroup g JOIN g.users u GROUP BY g',
            'SELECT c0_.id AS id0, c0_.name AS name1, count(c1_.id) AS sclr2 FROM cms_groups c0_ INNER JOIN cms_users_groups c2_ ON c0_.id = c2_.group_id INNER JOIN cms_users c1_ ON c1_.id = c2_.user_id GROUP BY c0_.id, c0_.name'
        );
    }

    public function testCaseContainingNullIf()
    {
        $this->assertSqlGeneration(
            "SELECT NULLIF(g.id, g.name) AS NullIfEqual FROM Doctrine\Tests\Models\CMS\CmsGroup g",
            'SELECT NULLIF(c0_.id, c0_.name) AS sclr0 FROM cms_groups c0_'
        );
    }

    public function testCaseContainingCoalesce()
    {
        $this->assertSqlGeneration(
            "SELECT COALESCE(NULLIF(u.name, ''), u.username) as Display FROM Doctrine\Tests\Models\CMS\CmsUser u",
            "SELECT COALESCE(NULLIF(c0_.name, ''), c0_.username) AS sclr0 FROM cms_users c0_"
        );
    }

    /**
     * Test that the right discriminator data is inserted in a subquery.
     */
    public function testSubSelectDiscriminator()
    {
        $this->assertSqlGeneration(
            "SELECT u.name, (SELECT COUNT(cfc.id) total FROM Doctrine\Tests\Models\Company\CompanyFixContract cfc) as cfc_count FROM Doctrine\Tests\Models\CMS\CmsUser u",
            "SELECT c0_.name AS name0, (SELECT COUNT(c1_.id) AS dctrn__total FROM company_contracts c1_ WHERE c1_.discr IN ('fix')) AS sclr1 FROM cms_users c0_"
        );
    }

    public function testIdVariableResultVariableReuse()
    {
        $exceptionThrown = false;
        try {
            $query = $this->_em->createQuery("SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IN (SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u)");

            $query->getSql();
            $query->free();
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

    }

    public function testSubSelectAliasesFromOuterQuery()
    {
        $this->assertSqlGeneration(
            "SELECT uo, (SELECT ui.name FROM Doctrine\Tests\Models\CMS\CmsUser ui WHERE ui.id = uo.id) AS bar FROM Doctrine\Tests\Models\CMS\CmsUser uo",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, (SELECT c1_.name FROM cms_users c1_ WHERE c1_.id = c0_.id) AS sclr4 FROM cms_users c0_"
        );
    }

    public function testSubSelectAliasesFromOuterQueryWithSubquery()
    {
        $this->assertSqlGeneration(
            "SELECT uo, (SELECT ui.name FROM Doctrine\Tests\Models\CMS\CmsUser ui WHERE ui.id = uo.id AND ui.name IN (SELECT uii.name FROM Doctrine\Tests\Models\CMS\CmsUser uii)) AS bar FROM Doctrine\Tests\Models\CMS\CmsUser uo",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, (SELECT c1_.name FROM cms_users c1_ WHERE c1_.id = c0_.id AND c1_.name IN (SELECT c2_.name FROM cms_users c2_)) AS sclr4 FROM cms_users c0_"
        );
    }

    public function testSubSelectAliasesFromOuterQueryReuseInWhereClause()
    {
        $this->assertSqlGeneration(
            "SELECT uo, (SELECT ui.name FROM Doctrine\Tests\Models\CMS\CmsUser ui WHERE ui.id = uo.id) AS bar FROM Doctrine\Tests\Models\CMS\CmsUser uo WHERE bar = ?0",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, (SELECT c1_.name FROM cms_users c1_ WHERE c1_.id = c0_.id) AS sclr4 FROM cms_users c0_ WHERE sclr4 = ?"
        );
    }

    /**
     * @group DDC-1298
     */
    public function testSelectForeignKeyPKWithoutFields()
    {
        $this->assertSqlGeneration(
            "SELECT t, s, l FROM Doctrine\Tests\Models\DDC117\DDC117Link l INNER JOIN l.target t INNER JOIN l.source s",
            "SELECT d0_.article_id AS article_id0, d0_.title AS title1, d1_.article_id AS article_id2, d1_.title AS title3, d2_.source_id AS source_id4, d2_.target_id AS target_id5 FROM DDC117Link d2_ INNER JOIN DDC117Article d0_ ON d2_.target_id = d0_.article_id INNER JOIN DDC117Article d1_ ON d2_.source_id = d1_.article_id"
        );
    }

    public function testGeneralCaseWithSingleWhenClause()
    {
        $this->assertSqlGeneration(
            "SELECT g.id, CASE WHEN ((g.id / 2) > 18) THEN 1 ELSE 0 END AS test FROM Doctrine\Tests\Models\CMS\CmsGroup g",
            "SELECT c0_.id AS id0, CASE WHEN (c0_.id / 2 > 18) THEN 1 ELSE 0 END AS sclr1 FROM cms_groups c0_"
        );
    }

    public function testGeneralCaseWithMultipleWhenClause()
    {
        $this->assertSqlGeneration(
            "SELECT g.id, CASE WHEN (g.id / 2 < 10) THEN 2 WHEN ((g.id / 2) > 20) THEN 1 ELSE 0 END AS test FROM Doctrine\Tests\Models\CMS\CmsGroup g",
            "SELECT c0_.id AS id0, CASE WHEN (c0_.id / 2 < 10) THEN 2 WHEN (c0_.id / 2 > 20) THEN 1 ELSE 0 END AS sclr1 FROM cms_groups c0_"
        );
    }

    public function testSimpleCaseWithSingleWhenClause()
    {
        $this->assertSqlGeneration(
            "SELECT g FROM Doctrine\Tests\Models\CMS\CmsGroup g WHERE g.id = CASE g.name WHEN 'admin' THEN 1 ELSE 2 END",
            "SELECT c0_.id AS id0, c0_.name AS name1 FROM cms_groups c0_ WHERE c0_.id = CASE c0_.name WHEN 'admin' THEN 1 ELSE 2 END"
        );
    }

    public function testSimpleCaseWithMultipleWhenClause()
    {
        $this->assertSqlGeneration(
            "SELECT g FROM Doctrine\Tests\Models\CMS\CmsGroup g WHERE g.id = (CASE g.name WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2 ELSE 3 END)",
            "SELECT c0_.id AS id0, c0_.name AS name1 FROM cms_groups c0_ WHERE c0_.id = CASE c0_.name WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2 ELSE 3 END"
        );
    }

    public function testGeneralCaseWithSingleWhenClauseInSubselect()
    {
        $this->assertSqlGeneration(
            "SELECT g FROM Doctrine\Tests\Models\CMS\CmsGroup g WHERE g.id IN (SELECT CASE WHEN ((g2.id / 2) > 18) THEN 2 ELSE 1 END FROM Doctrine\Tests\Models\CMS\CmsGroup g2)",
            "SELECT c0_.id AS id0, c0_.name AS name1 FROM cms_groups c0_ WHERE c0_.id IN (SELECT CASE WHEN (c1_.id / 2 > 18) THEN 2 ELSE 1 END AS sclr2 FROM cms_groups c1_)"
        );
    }

    public function testGeneralCaseWithMultipleWhenClauseInSubselect()
    {
        $this->assertSqlGeneration(
            "SELECT g FROM Doctrine\Tests\Models\CMS\CmsGroup g WHERE g.id IN (SELECT CASE WHEN (g.id / 2 < 10) THEN 3 WHEN ((g.id / 2) > 20) THEN 2 ELSE 1 END FROM Doctrine\Tests\Models\CMS\CmsGroup g2)",
            "SELECT c0_.id AS id0, c0_.name AS name1 FROM cms_groups c0_ WHERE c0_.id IN (SELECT CASE WHEN (c0_.id / 2 < 10) THEN 3 WHEN (c0_.id / 2 > 20) THEN 2 ELSE 1 END AS sclr2 FROM cms_groups c1_)"
        );
    }

    public function testSimpleCaseWithSingleWhenClauseInSubselect()
    {
        $this->assertSqlGeneration(
            "SELECT g FROM Doctrine\Tests\Models\CMS\CmsGroup g WHERE g.id IN (SELECT CASE g2.name WHEN 'admin' THEN 1 ELSE 2 END FROM Doctrine\Tests\Models\CMS\CmsGroup g2)",
            "SELECT c0_.id AS id0, c0_.name AS name1 FROM cms_groups c0_ WHERE c0_.id IN (SELECT CASE c1_.name WHEN 'admin' THEN 1 ELSE 2 END AS sclr2 FROM cms_groups c1_)"
        );
    }

    public function testSimpleCaseWithMultipleWhenClauseInSubselect()
    {
        $this->assertSqlGeneration(
            "SELECT g FROM Doctrine\Tests\Models\CMS\CmsGroup g WHERE g.id IN (SELECT CASE g2.name WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2 ELSE 3 END FROM Doctrine\Tests\Models\CMS\CmsGroup g2)",
            "SELECT c0_.id AS id0, c0_.name AS name1 FROM cms_groups c0_ WHERE c0_.id IN (SELECT CASE c1_.name WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2 ELSE 3 END AS sclr2 FROM cms_groups c1_)"
        );
    }

    /**
     * @group DDC-1696
     */
    public function testSimpleCaseWithStringPrimary()
    {
        $this->assertSqlGeneration(
            "SELECT g.id, CASE WHEN ((g.id / 2) > 18) THEN 'Foo' ELSE 'Bar' END AS test FROM Doctrine\Tests\Models\CMS\CmsGroup g",
            "SELECT c0_.id AS id0, CASE WHEN (c0_.id / 2 > 18) THEN 'Foo' ELSE 'Bar' END AS sclr1 FROM cms_groups c0_"
        );
    }

    /**
     * @group DDC-2205
     */
    public function testCaseNegativeValuesInThenExpression()
    {
        $this->assertSqlGeneration(
            "SELECT CASE g.name WHEN 'admin' THEN - 1 ELSE - 2 END FROM Doctrine\Tests\Models\CMS\CmsGroup g",
            "SELECT CASE c0_.name WHEN 'admin' THEN -1 ELSE -2 END AS sclr0 FROM cms_groups c0_"
        );

        $this->assertSqlGeneration(
            "SELECT CASE g.name WHEN 'admin' THEN  - 2 WHEN 'guest' THEN - 1 ELSE 0 END FROM Doctrine\Tests\Models\CMS\CmsGroup g",
            "SELECT CASE c0_.name WHEN 'admin' THEN -2 WHEN 'guest' THEN -1 ELSE 0 END AS sclr0 FROM cms_groups c0_"
        );

        $this->assertSqlGeneration(
            "SELECT CASE g.name WHEN 'admin' THEN (- 1) ELSE (- 2) END FROM Doctrine\Tests\Models\CMS\CmsGroup g",
            "SELECT CASE c0_.name WHEN 'admin' THEN -1 ELSE -2 END AS sclr0 FROM cms_groups c0_"
        );

        $this->assertSqlGeneration(
            "SELECT CASE g.name WHEN 'admin' THEN ( - :value) ELSE ( + :value) END FROM Doctrine\Tests\Models\CMS\CmsGroup g",
            "SELECT CASE c0_.name WHEN 'admin' THEN -? ELSE +? END AS sclr0 FROM cms_groups c0_"
        );

        $this->assertSqlGeneration(
            "SELECT CASE g.name WHEN 'admin' THEN ( - g.id) ELSE ( + g.id) END FROM Doctrine\Tests\Models\CMS\CmsGroup g",
            "SELECT CASE c0_.name WHEN 'admin' THEN -c0_.id ELSE +c0_.id END AS sclr0 FROM cms_groups c0_"
        );
    }

    public function testIdentityFunctionWithCompositePrimaryKey()
    {
        $this->assertSqlGeneration(
            "SELECT IDENTITY(p.poi, 'long') AS long FROM Doctrine\Tests\Models\Navigation\NavPhotos p",
            "SELECT n0_.poi_long AS sclr0 FROM navigation_photos n0_"
        );

        $this->assertSqlGeneration(
            "SELECT IDENTITY(p.poi, 'lat') AS lat FROM Doctrine\Tests\Models\Navigation\NavPhotos p",
            "SELECT n0_.poi_lat AS sclr0 FROM navigation_photos n0_"
        );

        $this->assertSqlGeneration(
            "SELECT IDENTITY(p.poi, 'long') AS long, IDENTITY(p.poi, 'lat') AS lat FROM Doctrine\Tests\Models\Navigation\NavPhotos p",
            "SELECT n0_.poi_long AS sclr0, n0_.poi_lat AS sclr1 FROM navigation_photos n0_"
        );

        $this->assertInvalidSqlGeneration(
            "SELECT IDENTITY(p.poi, 'invalid') AS invalid FROM Doctrine\Tests\Models\Navigation\NavPhotos p",
            "Doctrine\ORM\Query\QueryException"
        );
    }

    /**
     * @group DDC-1339
     */
    public function testIdentityFunctionInSelectClause()
    {
        $this->assertSqlGeneration(
            "SELECT IDENTITY(u.email) as email_id FROM Doctrine\Tests\Models\CMS\CmsUser u",
            "SELECT c0_.email_id AS sclr0 FROM cms_users c0_"
        );
    }

    /**
     * @group DDC-1339
     */
    public function testIdentityFunctionDoesNotAcceptStateField()
    {
        $this->assertInvalidSqlGeneration(
            "SELECT IDENTITY(u.name) as name FROM Doctrine\Tests\Models\CMS\CmsUser u",
            "Doctrine\ORM\Query\QueryException"
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeJoinInRootClassWithDisabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT p FROM Doctrine\Tests\Models\Company\CompanyPerson p',
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.title AS title2, c2_.salary AS salary3, c2_.department AS department4, c2_.startDate AS startDate5, c0_.discr AS discr6, c0_.spouse_id AS spouse_id7, c1_.car_id AS car_id8 FROM company_persons c0_ LEFT JOIN company_managers c1_ ON c0_.id = c1_.id LEFT JOIN company_employees c2_ ON c0_.id = c2_.id',
            array(Query::HINT_FORCE_PARTIAL_LOAD => false)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeJoinInRootClassWithEnabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT p FROM Doctrine\Tests\Models\Company\CompanyPerson p',
            'SELECT c0_.id AS id0, c0_.name AS name1, c0_.discr AS discr2 FROM company_persons c0_',
            array(Query::HINT_FORCE_PARTIAL_LOAD => true)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeJoinInChildClassWithDisabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT e FROM Doctrine\Tests\Models\Company\CompanyEmployee e',
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c1_.startDate AS startDate4, c2_.title AS title5, c0_.discr AS discr6, c0_.spouse_id AS spouse_id7, c2_.car_id AS car_id8 FROM company_employees c1_ INNER JOIN company_persons c0_ ON c1_.id = c0_.id LEFT JOIN company_managers c2_ ON c1_.id = c2_.id',
            array(Query::HINT_FORCE_PARTIAL_LOAD => false)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeJoinInChildClassWithEnabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT e FROM Doctrine\Tests\Models\Company\CompanyEmployee e',
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c1_.startDate AS startDate4, c0_.discr AS discr5 FROM company_employees c1_ INNER JOIN company_persons c0_ ON c1_.id = c0_.id',
            array(Query::HINT_FORCE_PARTIAL_LOAD => true)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeJoinInLeafClassWithDisabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT m FROM Doctrine\Tests\Models\Company\CompanyManager m',
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c1_.startDate AS startDate4, c2_.title AS title5, c0_.discr AS discr6, c0_.spouse_id AS spouse_id7, c2_.car_id AS car_id8 FROM company_managers c2_ INNER JOIN company_employees c1_ ON c2_.id = c1_.id INNER JOIN company_persons c0_ ON c2_.id = c0_.id',
            array(Query::HINT_FORCE_PARTIAL_LOAD => false)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeJoinInLeafClassWithEnabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT m FROM Doctrine\Tests\Models\Company\CompanyManager m',
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c1_.startDate AS startDate4, c2_.title AS title5, c0_.discr AS discr6 FROM company_managers c2_ INNER JOIN company_employees c1_ ON c2_.id = c1_.id INNER JOIN company_persons c0_ ON c2_.id = c0_.id',
            array(Query::HINT_FORCE_PARTIAL_LOAD => true)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeSingleTableInRootClassWithDisabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT c FROM Doctrine\Tests\Models\Company\CompanyContract c',
            "SELECT c0_.id AS id0, c0_.completed AS completed1, c0_.fixPrice AS fixPrice2, c0_.hoursWorked AS hoursWorked3, c0_.pricePerHour AS pricePerHour4, c0_.maxPrice AS maxPrice5, c0_.discr AS discr6, c0_.salesPerson_id AS salesPerson_id7 FROM company_contracts c0_ WHERE c0_.discr IN ('fix', 'flexible', 'flexultra')",
            array(Query::HINT_FORCE_PARTIAL_LOAD => false)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeSingleTableInRootClassWithEnabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT c FROM Doctrine\Tests\Models\Company\CompanyContract c',
            "SELECT c0_.id AS id0, c0_.completed AS completed1, c0_.fixPrice AS fixPrice2, c0_.hoursWorked AS hoursWorked3, c0_.pricePerHour AS pricePerHour4, c0_.maxPrice AS maxPrice5, c0_.discr AS discr6 FROM company_contracts c0_ WHERE c0_.discr IN ('fix', 'flexible', 'flexultra')",
            array(Query::HINT_FORCE_PARTIAL_LOAD => true)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeSingleTableInChildClassWithDisabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT fc FROM Doctrine\Tests\Models\Company\CompanyFlexContract fc',
            "SELECT c0_.id AS id0, c0_.completed AS completed1, c0_.hoursWorked AS hoursWorked2, c0_.pricePerHour AS pricePerHour3, c0_.maxPrice AS maxPrice4, c0_.discr AS discr5, c0_.salesPerson_id AS salesPerson_id6 FROM company_contracts c0_ WHERE c0_.discr IN ('flexible', 'flexultra')",
            array(Query::HINT_FORCE_PARTIAL_LOAD => false)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeSingleTableInChildClassWithEnabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT fc FROM Doctrine\Tests\Models\Company\CompanyFlexContract fc',
            "SELECT c0_.id AS id0, c0_.completed AS completed1, c0_.hoursWorked AS hoursWorked2, c0_.pricePerHour AS pricePerHour3, c0_.maxPrice AS maxPrice4, c0_.discr AS discr5 FROM company_contracts c0_ WHERE c0_.discr IN ('flexible', 'flexultra')",
            array(Query::HINT_FORCE_PARTIAL_LOAD => true)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeSingleTableInLeafClassWithDisabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT fuc FROM Doctrine\Tests\Models\Company\CompanyFlexUltraContract fuc',
            "SELECT c0_.id AS id0, c0_.completed AS completed1, c0_.hoursWorked AS hoursWorked2, c0_.pricePerHour AS pricePerHour3, c0_.maxPrice AS maxPrice4, c0_.discr AS discr5, c0_.salesPerson_id AS salesPerson_id6 FROM company_contracts c0_ WHERE c0_.discr IN ('flexultra')",
            array(Query::HINT_FORCE_PARTIAL_LOAD => false)
        );
    }

    /**
     * @group DDC-1389
     */
    public function testInheritanceTypeSingleTableInLeafClassWithEnabledForcePartialLoad()
    {
        $this->assertSqlGeneration(
            'SELECT fuc FROM Doctrine\Tests\Models\Company\CompanyFlexUltraContract fuc',
            "SELECT c0_.id AS id0, c0_.completed AS completed1, c0_.hoursWorked AS hoursWorked2, c0_.pricePerHour AS pricePerHour3, c0_.maxPrice AS maxPrice4, c0_.discr AS discr5 FROM company_contracts c0_ WHERE c0_.discr IN ('flexultra')",
            array(Query::HINT_FORCE_PARTIAL_LOAD => true)
        );
    }

    /**
     * @group DDC-1161
     */
    public function testSelfReferenceWithOneToOneDoesNotDuplicateAlias()
    {
        $this->assertSqlGeneration(
            'SELECT p, pp FROM Doctrine\Tests\Models\Company\CompanyPerson p JOIN p.spouse pp',
            "SELECT c0_.id AS id0, c0_.name AS name1, c1_.title AS title2, c2_.salary AS salary3, c2_.department AS department4, c2_.startDate AS startDate5, c3_.id AS id6, c3_.name AS name7, c4_.title AS title8, c5_.salary AS salary9, c5_.department AS department10, c5_.startDate AS startDate11, c0_.discr AS discr12, c0_.spouse_id AS spouse_id13, c1_.car_id AS car_id14, c3_.discr AS discr15, c3_.spouse_id AS spouse_id16, c4_.car_id AS car_id17 FROM company_persons c0_ LEFT JOIN company_managers c1_ ON c0_.id = c1_.id LEFT JOIN company_employees c2_ ON c0_.id = c2_.id INNER JOIN company_persons c3_ ON c0_.spouse_id = c3_.id LEFT JOIN company_managers c4_ ON c3_.id = c4_.id LEFT JOIN company_employees c5_ ON c3_.id = c5_.id",
            array(Query::HINT_FORCE_PARTIAL_LOAD => false)
        );
    }

    /**
     * @group DDC-1384
     */
    public function testAliasDoesNotExceedPlatformDefinedLength()
    {
        $this->assertSqlGeneration(
            'SELECT m FROM ' . __NAMESPACE__ .  '\\DDC1384Model m',
            "SELECT d0_.aVeryLongIdentifierThatShouldBeShortenedByTheSQLWalker_fooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo AS fooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo0 FROM DDC1384Model d0_"
        );
    }

    /**
     * @group DDC-331
     * @group DDC-1384
     */
    public function testIssue331()
    {
        $this->assertSqlGeneration(
            'SELECT e.name FROM Doctrine\Tests\Models\Company\CompanyEmployee e',
            'SELECT c0_.name AS name0 FROM company_employees c1_ INNER JOIN company_persons c0_ ON c1_.id = c0_.id'
        );
    }
    /**
     * @group DDC-1435
     */
    public function testForeignKeyAsPrimaryKeySubselect()
    {
        $this->assertSqlGeneration(
            "SELECT s FROM Doctrine\Tests\Models\DDC117\DDC117Article s WHERE EXISTS (SELECT r FROM Doctrine\Tests\Models\DDC117\DDC117Reference r WHERE r.source = s)",
            "SELECT d0_.article_id AS article_id0, d0_.title AS title1 FROM DDC117Article d0_ WHERE EXISTS (SELECT d1_.source_id, d1_.target_id FROM DDC117Reference d1_ WHERE d1_.source_id = d0_.article_id)"
        );
    }

    /**
     * @group DDC-1474
     */
    public function testSelectWithArithmeticExpressionBeforeField()
    {
        $this->assertSqlGeneration(
            'SELECT - e.value AS value, e.id FROM ' . __NAMESPACE__ . '\DDC1474Entity e',
            'SELECT -d0_.value AS sclr0, d0_.id AS id1 FROM DDC1474Entity d0_'
        );

        $this->assertSqlGeneration(
            'SELECT e.id, + e.value AS value FROM ' . __NAMESPACE__ . '\DDC1474Entity e',
            'SELECT d0_.id AS id0, +d0_.value AS sclr1 FROM DDC1474Entity d0_'
        );
    }

     /**
     * @group DDC-1430
     */
    public function testGroupByAllFieldsWhenObjectHasForeignKeys()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ GROUP BY c0_.id, c0_.status, c0_.username, c0_.name, c0_.email_id'
        );

        $this->assertSqlGeneration(
            'SELECT e FROM Doctrine\Tests\Models\CMS\CmsEmployee e GROUP BY e',
            'SELECT c0_.id AS id0, c0_.name AS name1 FROM cms_employees c0_ GROUP BY c0_.id, c0_.name, c0_.spouse_id'
        );
    }

    /**
     * @group DDC-1236
     */
    public function testGroupBySupportsResultVariable()
    {
        $this->assertSqlGeneration(
            'SELECT u, u.status AS st FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY st',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c0_.status AS status4 FROM cms_users c0_ GROUP BY status4'
        );
    }

    /**
     * @group DDC-1236
     */
    public function testGroupBySupportsIdentificationVariable()
    {
        $this->assertSqlGeneration(
            'SELECT u AS user FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY user',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ GROUP BY id0, status1, username2, name3'
        );
    }

    /**
     * @group DDC-1213
     */
    public function testSupportsBitComparison()
    {
        $this->assertSqlGeneration(
            'SELECT BIT_OR(4,2), BIT_AND(4,2), u FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT (4 | 2) AS sclr0, (4 & 2) AS sclr1, c0_.id AS id2, c0_.status AS status3, c0_.username AS username4, c0_.name AS name5 FROM cms_users c0_'
        );
        $this->assertSqlGeneration(
            'SELECT BIT_OR(u.id,2), BIT_AND(u.id,2) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE BIT_OR(u.id,2) > 0',
            'SELECT (c0_.id | 2) AS sclr0, (c0_.id & 2) AS sclr1 FROM cms_users c0_ WHERE (c0_.id | 2) > 0'
        );
        $this->assertSqlGeneration(
            'SELECT BIT_OR(u.id,2), BIT_AND(u.id,2) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE BIT_AND(u.id , 4) > 0',
            'SELECT (c0_.id | 2) AS sclr0, (c0_.id & 2) AS sclr1 FROM cms_users c0_ WHERE (c0_.id & 4) > 0'
        );
        $this->assertSqlGeneration(
            'SELECT BIT_OR(u.id,2), BIT_AND(u.id,2) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE BIT_OR(u.id , 2) > 0 OR BIT_AND(u.id , 4) > 0',
            'SELECT (c0_.id | 2) AS sclr0, (c0_.id & 2) AS sclr1 FROM cms_users c0_ WHERE (c0_.id | 2) > 0 OR (c0_.id & 4) > 0'
        );
    }

    /**
     * @group DDC-1539
     */
    public function testParenthesesOnTheLeftHandOfComparison()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u where ( (u.id + u.id) * u.id ) > 100',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (c0_.id + c0_.id) * c0_.id > 100'
        );
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u where (u.id + u.id) * u.id > 100',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (c0_.id + c0_.id) * c0_.id > 100'
        );
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u where 100 < (u.id + u.id) * u.id ',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE 100 < (c0_.id + c0_.id) * c0_.id'
        );
    }

    /**
     * @group DDC-1557
     */
    public function testSupportsSubSqlFunction()
    {
        $this->assertSqlGeneration(
            'SELECT u1 FROM Doctrine\Tests\Models\CMS\CmsUser u1 WHERE u1.name IN ( SELECT TRIM(u2.name) FROM Doctrine\Tests\Models\CMS\CmsUser u2 )',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.name IN (SELECT TRIM(c1_.name) AS sclr4 FROM cms_users c1_)'
        );
        $this->assertSqlGeneration(
            'SELECT u1 FROM Doctrine\Tests\Models\CMS\CmsUser u1 WHERE u1.name IN ( SELECT TRIM(u2.name) FROM Doctrine\Tests\Models\CMS\CmsUser u2  WHERE LOWER(u2.name) LIKE \'%fabio%\')',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.name IN (SELECT TRIM(c1_.name) AS sclr4 FROM cms_users c1_ WHERE LOWER(c1_.name) LIKE \'%fabio%\')'
        );
        $this->assertSqlGeneration(
            'SELECT u1 FROM Doctrine\Tests\Models\CMS\CmsUser u1 WHERE u1.email IN ( SELECT TRIM(IDENTITY(u2.email)) FROM Doctrine\Tests\Models\CMS\CmsUser u2 )',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.email_id IN (SELECT TRIM(c1_.email_id) AS sclr4 FROM cms_users c1_)'
        );
        $this->assertSqlGeneration(
            'SELECT u1 FROM Doctrine\Tests\Models\CMS\CmsUser u1 WHERE u1.email IN ( SELECT IDENTITY(u2.email) FROM Doctrine\Tests\Models\CMS\CmsUser u2 )',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.email_id IN (SELECT c1_.email_id AS sclr4 FROM cms_users c1_)'
        );
        $this->assertSqlGeneration(
            'SELECT u1 FROM Doctrine\Tests\Models\CMS\CmsUser u1 WHERE COUNT(u1.id) = ( SELECT SUM(u2.id) FROM Doctrine\Tests\Models\CMS\CmsUser u2 )',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE COUNT(c0_.id) = (SELECT SUM(c1_.id) AS dctrn__1 FROM cms_users c1_)'
        );
        $this->assertSqlGeneration(
            'SELECT u1 FROM Doctrine\Tests\Models\CMS\CmsUser u1 WHERE COUNT(u1.id) <= ( SELECT SUM(u2.id) + COUNT(u2.email) FROM Doctrine\Tests\Models\CMS\CmsUser u2 )',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE COUNT(c0_.id) <= (SELECT SUM(c1_.id) + COUNT(c1_.email_id) AS sclr4 FROM cms_users c1_)'
        );
    }

    /**
     * @group DDC-1574
     */
    public function testSupportsNewOperator()
    {
        $this->assertSqlGeneration(
            "SELECT new Doctrine\Tests\Models\CMS\CmsUserDTO(u.name, e.email, a.city) FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e JOIN u.address a",
            "SELECT c0_.name AS sclr0, c1_.email AS sclr1, c2_.city AS sclr2 FROM cms_users c0_ INNER JOIN cms_emails c1_ ON c0_.email_id = c1_.id INNER JOIN cms_addresses c2_ ON c0_.id = c2_.user_id"
        );

        $this->assertSqlGeneration(
            "SELECT new Doctrine\Tests\Models\CMS\CmsUserDTO(u.name, e.email, a.id + u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e JOIN u.address a",
            "SELECT c0_.name AS sclr0, c1_.email AS sclr1, c2_.id + c0_.id AS sclr2 FROM cms_users c0_ INNER JOIN cms_emails c1_ ON c0_.email_id = c1_.id INNER JOIN cms_addresses c2_ ON c0_.id = c2_.user_id"
        );

        $this->assertSqlGeneration(
            "SELECT new Doctrine\Tests\Models\CMS\CmsUserDTO(u.name, e.email, a.city, COUNT(p)) FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e JOIN u.address a JOIN u.phonenumbers p",
            "SELECT c0_.name AS sclr0, c1_.email AS sclr1, c2_.city AS sclr2, COUNT(c3_.phonenumber) AS sclr3 FROM cms_users c0_ INNER JOIN cms_emails c1_ ON c0_.email_id = c1_.id INNER JOIN cms_addresses c2_ ON c0_.id = c2_.user_id INNER JOIN cms_phonenumbers c3_ ON c0_.id = c3_.user_id"
        );

        $this->assertSqlGeneration(
            "SELECT new Doctrine\Tests\Models\CMS\CmsUserDTO(u.name, e.email, a.city, COUNT(p) + u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e JOIN u.address a JOIN u.phonenumbers p",
            "SELECT c0_.name AS sclr0, c1_.email AS sclr1, c2_.city AS sclr2, COUNT(c3_.phonenumber) + c0_.id AS sclr3 FROM cms_users c0_ INNER JOIN cms_emails c1_ ON c0_.email_id = c1_.id INNER JOIN cms_addresses c2_ ON c0_.id = c2_.user_id INNER JOIN cms_phonenumbers c3_ ON c0_.id = c3_.user_id"
        );

        $this->assertSqlGeneration(
            "SELECT new Doctrine\Tests\Models\CMS\CmsUserDTO(a.id, a.country, a.city), new Doctrine\Tests\Models\CMS\CmsAddressDTO(u.name, e.email) FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e JOIN u.address a ORDER BY u.name",
            "SELECT c0_.id AS sclr0, c0_.country AS sclr1, c0_.city AS sclr2, c1_.name AS sclr3, c2_.email AS sclr4 FROM cms_users c1_ INNER JOIN cms_emails c2_ ON c1_.email_id = c2_.id INNER JOIN cms_addresses c0_ ON c1_.id = c0_.user_id ORDER BY c1_.name ASC"
        );

    }

    /**
     * @group DDC-2234
     */
    public function testWhereFunctionIsNullComparisonExpression()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE IDENTITY(u.email) IS NULL",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.email_id IS NULL"
        );

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE NULLIF(u.name, 'FabioBatSilva') IS NULL AND IDENTITY(u.email) IS NOT NULL",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE NULLIF(c0_.name, 'FabioBatSilva') IS NULL AND c0_.email_id IS NOT NULL"
        );

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE IDENTITY(u.email) IS NOT NULL",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.email_id IS NOT NULL"
        );

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE NULLIF(u.name, 'FabioBatSilva') IS NOT NULL",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE NULLIF(c0_.name, 'FabioBatSilva') IS NOT NULL"
        );

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE COALESCE(u.name, u.id) IS NOT NULL",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE COALESCE(c0_.name, c0_.id) IS NOT NULL"
        );

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE COALESCE(u.id, IDENTITY(u.email)) IS NOT NULL",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE COALESCE(c0_.id, c0_.email_id) IS NOT NULL"
        );

        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE COALESCE(IDENTITY(u.email), NULLIF(u.name, 'FabioBatSilva')) IS NOT NULL",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE COALESCE(c0_.email_id, NULLIF(c0_.name, 'FabioBatSilva')) IS NOT NULL"
        );
    }

    public function testCustomTypeValueSql()
    {
        if (DBALType::hasType('negative_to_positive')) {
            DBALType::overrideType('negative_to_positive', 'Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        } else {
            DBALType::addType('negative_to_positive', 'Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        }

        $this->assertSqlGeneration(
            'SELECT p.customInteger FROM Doctrine\Tests\Models\CustomType\CustomTypeParent p WHERE p.id = 1',
            'SELECT -(c0_.customInteger) AS customInteger0 FROM customtype_parents c0_ WHERE c0_.id = 1'
        );
    }

    public function testCustomTypeValueSqlIgnoresIdentifierColumn()
    {
        if (DBALType::hasType('negative_to_positive')) {
            DBALType::overrideType('negative_to_positive', 'Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        } else {
            DBALType::addType('negative_to_positive', 'Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        }

        $this->assertSqlGeneration(
            'SELECT p.id FROM Doctrine\Tests\Models\CustomType\CustomTypeParent p WHERE p.id = 1',
            'SELECT c0_.id AS id0 FROM customtype_parents c0_ WHERE c0_.id = 1'
        );
    }

    public function testCustomTypeValueSqlForAllFields()
    {
        if (DBALType::hasType('negative_to_positive')) {
            DBALType::overrideType('negative_to_positive', 'Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        } else {
            DBALType::addType('negative_to_positive', 'Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        }

        $this->assertSqlGeneration(
            'SELECT p FROM Doctrine\Tests\Models\CustomType\CustomTypeParent p',
            'SELECT c0_.id AS id0, -(c0_.customInteger) AS customInteger1 FROM customtype_parents c0_'
        );
    }

    public function testCustomTypeValueSqlForPartialObject()
    {
        if (DBALType::hasType('negative_to_positive')) {
            DBALType::overrideType('negative_to_positive', 'Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        } else {
            DBALType::addType('negative_to_positive', 'Doctrine\Tests\DbalTypes\NegativeToPositiveType');
        }

        $this->assertSqlGeneration(
            'SELECT partial p.{id, customInteger} FROM Doctrine\Tests\Models\CustomType\CustomTypeParent p',
            'SELECT c0_.id AS id0, -(c0_.customInteger) AS customInteger1 FROM customtype_parents c0_'
        );
    }

    /**
     * @group DDC-1529
     */
    public function testMultipleFromAndInheritanceCondition()
    {
        $this->assertSqlGeneration(
            'SELECT fix, flex FROM Doctrine\Tests\Models\Company\CompanyFixContract fix, Doctrine\Tests\Models\Company\CompanyFlexContract flex',
            "SELECT c0_.id AS id0, c0_.completed AS completed1, c0_.fixPrice AS fixPrice2, c1_.id AS id3, c1_.completed AS completed4, c1_.hoursWorked AS hoursWorked5, c1_.pricePerHour AS pricePerHour6, c1_.maxPrice AS maxPrice7, c0_.discr AS discr8, c1_.discr AS discr9 FROM company_contracts c0_, company_contracts c1_ WHERE (c0_.discr IN ('fix') AND c1_.discr IN ('flexible', 'flexultra'))"
        );
    }

    /**
     * @group DDC-775
     */
    public function testOrderByClauseSupportsSimpleArithmeticExpression()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.id + 1 ',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ ORDER BY c0_.id + 1 ASC'
        );
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY ( ( (u.id + 1) * (u.id - 1) ) / 2)',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ ORDER BY (c0_.id + 1) * (c0_.id - 1) / 2 ASC'
        );
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY ((u.id + 5000) * u.id + 3) ',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ ORDER BY (c0_.id + 5000) * c0_.id + 3 ASC'
        );
    }

    /**
     * @group DDC-1719
     */
    public function testStripNonAlphanumericCharactersFromAlias()
    {
        $this->assertSqlGeneration(
            'SELECT e FROM Doctrine\Tests\Models\Quote\SimpleEntity e',
            'SELECT d0_."simple-entity-id" AS simpleentityid0, d0_."simple-entity-value" AS simpleentityvalue1 FROM "ddc-1719-simple-entity" d0_'
        );

        $this->assertSqlGeneration(
            'SELECT e.value FROM Doctrine\Tests\Models\Quote\SimpleEntity e ORDER BY e.value',
            'SELECT d0_."simple-entity-value" AS simpleentityvalue0 FROM "ddc-1719-simple-entity" d0_ ORDER BY d0_."simple-entity-value" ASC'
        );

        $this->assertSqlGeneration(
            'SELECT TRIM(e.value) FROM Doctrine\Tests\Models\Quote\SimpleEntity e ORDER BY e.value',
            'SELECT TRIM(d0_."simple-entity-value") AS sclr0 FROM "ddc-1719-simple-entity" d0_ ORDER BY d0_."simple-entity-value" ASC'
        );
    }

   /**
    * @group DDC-1845
    */
    public function testQuotedWalkJoinVariableDeclaration()
    {
        $this->assertSqlGeneration(
            'SELECT u, a FROM Doctrine\Tests\Models\Quote\User u JOIN u.address a',
            'SELECT q0_."user-id" AS userid0, q0_."user-name" AS username1, q1_."address-id" AS addressid2, q1_."address-zip" AS addresszip3 FROM "quote-user" q0_ INNER JOIN "quote-address" q1_ ON q0_."address-id" = q1_."address-id"'
        );

        $this->assertSqlGeneration(
            'SELECT u, p FROM Doctrine\Tests\Models\Quote\User u JOIN u.phones p',
            'SELECT q0_."user-id" AS userid0, q0_."user-name" AS username1, q1_."phone-number" AS phonenumber2 FROM "quote-user" q0_ INNER JOIN "quote-phone" q1_ ON q0_."user-id" = q1_."user-id"'
        );

        $this->assertSqlGeneration(
            'SELECT u, g FROM Doctrine\Tests\Models\Quote\User u JOIN u.groups g',
            'SELECT q0_."user-id" AS userid0, q0_."user-name" AS username1, q1_."group-id" AS groupid2, q1_."group-name" AS groupname3 FROM "quote-user" q0_ INNER JOIN "quote-users-groups" q2_ ON q0_."user-id" = q2_."user-id" INNER JOIN "quote-group" q1_ ON q1_."group-id" = q2_."group-id"'
        );

        $this->assertSqlGeneration(
            'SELECT a, u FROM Doctrine\Tests\Models\Quote\Address a JOIN a.user u',
            'SELECT q0_."address-id" AS addressid0, q0_."address-zip" AS addresszip1, q1_."user-id" AS userid2, q1_."user-name" AS username3 FROM "quote-address" q0_ INNER JOIN "quote-user" q1_ ON q0_."user-id" = q1_."user-id"'
        );

        $this->assertSqlGeneration(
            'SELECT g, u FROM Doctrine\Tests\Models\Quote\Group g JOIN g.users u',
            'SELECT q0_."group-id" AS groupid0, q0_."group-name" AS groupname1, q1_."user-id" AS userid2, q1_."user-name" AS username3 FROM "quote-group" q0_ INNER JOIN "quote-users-groups" q2_ ON q0_."group-id" = q2_."group-id" INNER JOIN "quote-user" q1_ ON q1_."user-id" = q2_."user-id"'
        );

        $this->assertSqlGeneration(
            'SELECT g, p FROM Doctrine\Tests\Models\Quote\Group g JOIN g.parent p',
            'SELECT q0_."group-id" AS groupid0, q0_."group-name" AS groupname1, q1_."group-id" AS groupid2, q1_."group-name" AS groupname3 FROM "quote-group" q0_ INNER JOIN "quote-group" q1_ ON q0_."parent-id" = q1_."group-id"'
        );
    }

   /**
    * @group DDC-2208
    */
    public function testCaseThenParameterArithmeticExpression()
    {
        $this->assertSqlGeneration(
            'SELECT SUM(CASE WHEN e.salary <= :value THEN e.salary - :value WHEN e.salary >= :value THEN :value - e.salary ELSE 0 END) FROM Doctrine\Tests\Models\Company\CompanyEmployee e',
            'SELECT SUM(CASE WHEN c0_.salary <= ? THEN c0_.salary - ? WHEN c0_.salary >= ? THEN ? - c0_.salary ELSE 0 END) AS sclr0 FROM company_employees c0_ INNER JOIN company_persons c1_ ON c0_.id = c1_.id'
        );

        $this->assertSqlGeneration(
            'SELECT SUM(CASE WHEN e.salary <= :value THEN e.salary - :value WHEN e.salary >= :value THEN :value - e.salary ELSE e.salary + 0 END) FROM Doctrine\Tests\Models\Company\CompanyEmployee e',
            'SELECT SUM(CASE WHEN c0_.salary <= ? THEN c0_.salary - ? WHEN c0_.salary >= ? THEN ? - c0_.salary ELSE c0_.salary + 0 END) AS sclr0 FROM company_employees c0_ INNER JOIN company_persons c1_ ON c0_.id = c1_.id'
        );

        $this->assertSqlGeneration(
            'SELECT SUM(CASE WHEN e.salary <= :value THEN (e.salary - :value) WHEN e.salary >= :value THEN (:value - e.salary) ELSE (e.salary + :value) END) FROM Doctrine\Tests\Models\Company\CompanyEmployee e',
            'SELECT SUM(CASE WHEN c0_.salary <= ? THEN c0_.salary - ? WHEN c0_.salary >= ? THEN ? - c0_.salary ELSE c0_.salary + ? END) AS sclr0 FROM company_employees c0_ INNER JOIN company_persons c1_ ON c0_.id = c1_.id'
        );
    }

    /**
    * @group DDC-2268
    */
    public function testCaseThenFunction()
    {
        $this->assertSqlGeneration(
            'SELECT CASE WHEN LENGTH(u.name) <> 0 THEN CONCAT(u.id, u.name) ELSE u.id END AS name  FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT CASE WHEN LENGTH(c0_.name) <> 0 THEN c0_.id || c0_.name ELSE c0_.id END AS sclr0 FROM cms_users c0_'
        );

        $this->assertSqlGeneration(
            'SELECT CASE WHEN LENGTH(u.name) <> LENGTH(TRIM(u.name)) THEN TRIM(u.name) ELSE u.name END AS name  FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT CASE WHEN LENGTH(c0_.name) <> LENGTH(TRIM(c0_.name)) THEN TRIM(c0_.name) ELSE c0_.name END AS sclr0 FROM cms_users c0_'
        );

        $this->assertSqlGeneration(
            'SELECT CASE WHEN LENGTH(u.name) > :value THEN SUBSTRING(u.name, 0, :value) ELSE TRIM(u.name) END AS name  FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT CASE WHEN LENGTH(c0_.name) > ? THEN SUBSTRING(c0_.name FROM 0 FOR ?) ELSE TRIM(c0_.name) END AS sclr0 FROM cms_users c0_'
        );
    }
    
    /**
     * @group DDC-2268
     */
    
    public function testSupportsMoreThanTwoParametersInConcatFunction()
    {
    	$connMock = $this->_em->getConnection();
    	$orgPlatform = $connMock->getDatabasePlatform();
    
    	$connMock->setDatabasePlatform(new \Doctrine\DBAL\Platforms\MySqlPlatform);
    	$this->assertSqlGeneration(
    			"SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE CONCAT(u.name, u.status, 's') = ?1",
    			"SELECT c0_.id AS id0 FROM cms_users c0_ WHERE CONCAT(c0_.name, c0_.status, 's') = ?"
    	);
    	$this->assertSqlGeneration(
    			"SELECT CONCAT(u.id, u.name, u.status) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1",
    			"SELECT CONCAT(c0_.id, c0_.name, c0_.status) AS sclr0 FROM cms_users c0_ WHERE c0_.id = ?"
    	);
    
    	$connMock->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);
    	$this->assertSqlGeneration(
    			"SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE CONCAT(u.name, u.status, 's') = ?1",
    			"SELECT c0_.id AS id0 FROM cms_users c0_ WHERE c0_.name || c0_.status || 's' = ?"
    	);
    	$this->assertSqlGeneration(
    			"SELECT CONCAT(u.id, u.name, u.status) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1",
    			"SELECT c0_.id || c0_.name || c0_.status AS sclr0 FROM cms_users c0_ WHERE c0_.id = ?"
    	);
    	
    	
    	$connMock->setDatabasePlatform(new \Doctrine\DBAL\Platforms\SQLServerPlatform());
    	$this->assertSqlGeneration(
    			"SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE CONCAT(u.name, u.status, 's') = ?1",
    			"SELECT c0_.id AS id0 FROM cms_users c0_ WITH (NOLOCK) WHERE (c0_.name + c0_.status + 's') = ?"
    	);
    	$this->assertSqlGeneration(
    			"SELECT CONCAT(u.id, u.name, u.status) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1",
    			"SELECT (c0_.id + c0_.name + c0_.status) AS sclr0 FROM cms_users c0_ WITH (NOLOCK) WHERE c0_.id = ?"
    	);
    
    	$connMock->setDatabasePlatform($orgPlatform);
    }
}

class MyAbsFunction extends \Doctrine\ORM\Query\AST\Functions\FunctionNode
{
    public $simpleArithmeticExpression;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'ABS(' . $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression) . ')';
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();

        $parser->match(\Doctrine\ORM\Query\Lexer::T_IDENTIFIER);
        $parser->match(\Doctrine\ORM\Query\Lexer::T_OPEN_PARENTHESIS);

        $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(\Doctrine\ORM\Query\Lexer::T_CLOSE_PARENTHESIS);
    }
}
/**
 * @Entity
 */
class DDC1384Model
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $aVeryLongIdentifierThatShouldBeShortenedByTheSQLWalker_fooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo;
}


/**
 * @Entity
 */
class DDC1474Entity
{

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue()
     */
    protected $id;

    /**
     * @column(type="float")
     */
    private $value;

    /**
     * @param string $float
     */
    public function __construct($float)
    {
        $this->value = $float;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

}

