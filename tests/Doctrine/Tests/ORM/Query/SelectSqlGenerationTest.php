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
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_'
        );

        $this->assertSqlGeneration(
            'SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0_.id AS id0 FROM cms_users c0_'
        );
    }

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertSqlGeneration(
            'SELECT u.username, u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0_.username AS username0, c0_.name AS name1 FROM cms_users c0_'
        );
    }

    public function testSelectWithCollectionAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, c1_.phonenumber AS phonenumber4 FROM cms_users c0_ INNER JOIN cms_phonenumbers c1_ ON c0_.id = c1_.user_id'
        );
    }

    public function testSelectWithSingleValuedAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, a FROM Doctrine\Tests\Models\Forum\ForumUser u JOIN u.avatar a',
            'SELECT f0_.id AS id0, f0_.username AS username1, f1_.id AS id2 FROM forum_users f0_ INNER JOIN forum_avatars f1_ ON f0_.avatar_id = f1_.id'
        );
    }

    public function testSelectDistinctIsSupported()
    {
        $this->assertSqlGeneration(
            'SELECT DISTINCT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT DISTINCT c0_.name AS name0 FROM cms_users c0_'
        );
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id',
            'SELECT COUNT(c0_.id) AS sclr0 FROM cms_users c0_ GROUP BY c0_.id'
        );
    }

    public function testWhereClauseInSelectWithPositionalParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.id = ?1',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ WHERE f0_.id = ?'
        );
    }

    public function testWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ WHERE f0_.username = :name'
        );
    }

    public function testWhereANDClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name and u.username = :name2',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ WHERE f0_.username = :name AND f0_.username = :name2'
        );
    }

    public function testCombinedWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where (u.username = :name OR u.username = :name2) AND u.id = :id',
            'SELECT f0_.id AS id0, f0_.username AS username1 FROM forum_users f0_ WHERE (f0_.username = :name OR f0_.username = :name2) AND f0_.id = :id'
        );
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(DISTINCT u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT COUNT(DISTINCT c0_.name) AS sclr0 FROM cms_users c0_'
        );
    }

    // Ticket #668
    public function testKeywordUsageInStringParam()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name LIKE '%foo OR bar%'",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE c0_.name LIKE '%foo OR bar%'"
        );
    }

    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE ((c0_.id + 5000) * c0_.id + 3) < 10000000'
        );
    }

    public function testPlainJoinWithoutClause()
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

    public function testDeepJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id, p, c.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a JOIN u.phonenumbers p JOIN a.comments c',
            'SELECT c0_.id AS id0, c1_.id AS id1, c2_.phonenumber AS phonenumber2, c3_.id AS id3 FROM cms_users c0_ INNER JOIN cms_articles c1_ ON c0_.id = c1_.user_id INNER JOIN cms_phonenumbers c2_ ON c0_.id = c2_.user_id INNER JOIN cms_comments c3_ ON c1_.id = c3_.article_id'
        );
    }
    
    public function testTrimFunction()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(TRAILING ' ' FROM u.name) = 'someone'",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE TRIM(TRAILING ' ' FROM c0_.name) = 'someone'"
        );
    }

    // Ticket 894
    public function testBetweenDeclarationWithInputParameter()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id BETWEEN ?1 AND ?2",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE c0_.id BETWEEN ? AND ?"
        );
    }

    public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(u.name) = 'someone'",
            // String quoting in the SQL usually depends on the database platform.
            // This test works with a mock connection which uses ' for string quoting.
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE TRIM(FROM c0_.name) = 'someone'"
        );
    }

    // Ticket #973
    public function testSingleInValueWithoutSpace()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN(46)",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE c0_.id IN (46)"
        );
    }

    public function testInExpressionSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN (1, 2)',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.id IN (1, 2)'
        );
    }

    public function testNotInExpressionSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN (1)',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.id NOT IN (1)'
        );
    }

    public function testConcatFunction()
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
}