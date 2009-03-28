<?php

namespace Doctrine\Tests\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

class SelectSqlGenerationTest extends \Doctrine\Tests\OrmTestCase
{
    private $_em;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
    }

    public function assertSqlGeneration($dqlToBeTested, $sqlToBeConfirmed)
    {
        try {
            $query = $this->_em->createQuery($dqlToBeTested);
            parent::assertEquals($sqlToBeConfirmed, $query->getSql());
            $query->free();
        } catch (Doctrine_Exception $e) {
            echo $e->getMessage();
            echo $e->getTraceAsString(); die();
            $this->fail($e->getMessage());
        }
    }

    public function testPlainFromClauseWithoutAlias()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0.id AS c0__id, c0.status AS c0__status, c0.username AS c0__username, c0.name AS c0__name FROM cms_users c0'
        );

        $this->assertSqlGeneration(
            'SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0.id AS c0__id FROM cms_users c0'
        );
    }

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertSqlGeneration(
            'SELECT u.username, u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0.username AS c0__username, c0.name AS c0__name FROM cms_users c0'
        );
    }

    public function testSelectWithCollectionAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p',
            'SELECT c0.id AS c0__id, c0.status AS c0__status, c0.username AS c0__username, c0.name AS c0__name, c1.phonenumber AS c1__phonenumber FROM cms_users c0 INNER JOIN cms_phonenumbers c1 ON c0.id = c1.user_id'
        );
    }

    public function testSelectWithSingleValuedAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, a FROM Doctrine\Tests\Models\Forum\ForumUser u JOIN u.avatar a',
            'SELECT f0.id AS f0__id, f0.username AS f0__username, f1.id AS f1__id FROM forum_users f0 INNER JOIN forum_avatars f1 ON f0.avatar_id = f1.id'
        );
    }

    public function testSelectDistinctIsSupported()
    {
        $this->assertSqlGeneration(
            'SELECT DISTINCT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT DISTINCT c0.name AS c0__name FROM cms_users c0'
        );
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id',
            'SELECT COUNT(c0.id) AS dctrn__0 FROM cms_users c0 GROUP BY c0.id'
        );
    }

    public function testWhereClauseInSelectWithPositionalParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.id = ?1',
            'SELECT f0.id AS f0__id, f0.username AS f0__username FROM forum_users f0 WHERE f0.id = ?'
        );
    }

    public function testWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name',
            'SELECT f0.id AS f0__id, f0.username AS f0__username FROM forum_users f0 WHERE f0.username = :name'
        );
    }

    public function testWhereANDClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name and u.username = :name2',
            'SELECT f0.id AS f0__id, f0.username AS f0__username FROM forum_users f0 WHERE f0.username = :name AND f0.username = :name2'
        );
    }

    public function testCombinedWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where (u.username = :name OR u.username = :name2) AND u.id = :id',
            'SELECT f0.id AS f0__id, f0.username AS f0__username FROM forum_users f0 WHERE (f0.username = :name OR f0.username = :name2) AND f0.id = :id'
        );
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(DISTINCT u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT COUNT(DISTINCT c0.name) AS dctrn__0 FROM cms_users c0'
        );
    }

    // Ticket #668
    public function testKeywordUsageInStringParam()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name LIKE '%foo OR bar%'",
            "SELECT c0.name AS c0__name FROM cms_users c0 WHERE c0.name LIKE '%foo OR bar%'"
        );
    }

    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000',
            'SELECT c0.id AS c0__id, c0.status AS c0__status, c0.username AS c0__username, c0.name AS c0__name FROM cms_users c0 WHERE ((c0.id + 5000) * c0.id + 3) < 10000000'
        );
    }

    public function testPlainJoinWithoutClause()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a',
            'SELECT c0.id AS c0__id, c1.id AS c1__id FROM cms_users c0 LEFT JOIN cms_articles c1 ON c0.id = c1.user_id'
        );
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a',
            'SELECT c0.id AS c0__id, c1.id AS c1__id FROM cms_users c0 INNER JOIN cms_articles c1 ON c0.id = c1.user_id'
        );
    }

    public function testDeepJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id, p, c.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a JOIN u.phonenumbers p JOIN a.comments c',
            'SELECT c0.id AS c0__id, c1.id AS c1__id, c2.phonenumber AS c2__phonenumber, c3.id AS c3__id FROM cms_users c0 INNER JOIN cms_articles c1 ON c0.id = c1.user_id INNER JOIN cms_phonenumbers c2 ON c0.id = c2.user_id INNER JOIN cms_comments c3 ON c1.id = c3.article_id'
        );
    }
    
    public function testTrimFunction()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(TRAILING ' ' FROM u.name) = 'someone'",
            "SELECT c0.name AS c0__name FROM cms_users c0 WHERE TRIM(TRAILING ' ' FROM c0.name) = 'someone'"
        );
    }

    // Ticket 894
    public function testBetweenDeclarationWithInputParameter()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id BETWEEN ?1 AND ?2",
            "SELECT c0.name AS c0__name FROM cms_users c0 WHERE c0.id BETWEEN ? AND ?"
        );
    }

    public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(u.name) = 'someone'",
            // String quoting in the SQL usually depends on the database platform.
            // This test works with a mock connection which uses ' for string quoting.
            "SELECT c0.name AS c0__name FROM cms_users c0 WHERE TRIM(FROM c0.name) = 'someone'"
        );
    }

    // Ticket #973
    public function testSingleInValueWithoutSpace()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN(46)",
            "SELECT c0.name AS c0__name FROM cms_users c0 WHERE c0.id IN (46)"
        );
    }

    public function testInExpressionSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN (1, 2)',
            'SELECT c0.id AS c0__id, c0.status AS c0__status, c0.username AS c0__username, c0.name AS c0__name FROM cms_users c0 WHERE c0.id IN (1, 2)'
        );
    }

    public function testNotInExpressionSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN (1)',
            'SELECT c0.id AS c0__id, c0.status AS c0__status, c0.username AS c0__username, c0.name AS c0__name FROM cms_users c0 WHERE c0.id NOT IN (1)'
        );
    }

    public function testConcatFunction()
    {
        $connMock = $this->_em->getConnection();
        $orgPlatform = $connMock->getDatabasePlatform();

        $connMock->setDatabasePlatform(new \Doctrine\DBAL\Platforms\MySqlPlatform);
        $this->assertSqlGeneration(
            "SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE CONCAT(u.name, 's') = ?1",
            "SELECT c0.id AS c0__id FROM cms_users c0 WHERE CONCAT(c0.name, 's') = ?"
        );
        $this->assertSqlGeneration(
            "SELECT CONCAT(u.id, u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1",
            "SELECT CONCAT(c0.id, c0.name) AS dctrn__0 FROM cms_users c0 WHERE c0.id = ?"
        );

        $connMock->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);
        $this->assertSqlGeneration(
            "SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE CONCAT(u.name, 's') = ?1",
            "SELECT c0.id AS c0__id FROM cms_users c0 WHERE c0.name || 's' = ?"
        );
        $this->assertSqlGeneration(
            "SELECT CONCAT(u.id, u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1",
            "SELECT c0.id || c0.name AS dctrn__0 FROM cms_users c0 WHERE c0.id = ?"
        );

        $connMock->setDatabasePlatform($orgPlatform);
    }
}