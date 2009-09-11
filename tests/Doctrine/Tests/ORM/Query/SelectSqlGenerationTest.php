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
            echo $e->getTraceAsString();
            $this->fail($e->getMessage());
        }
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

    public function testSupportsSelectForMultipleColumnsOfASingleComponent()
    {
        $this->assertSqlGeneration(
            'SELECT u.username, u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0_.username AS username0, c0_.name AS name1 FROM cms_users c0_'
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
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE ((c0_.id + 5000) * c0_.id + 3) < 10000000'
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

    public function testSupportsMultipleJoins()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id, p, c.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a JOIN u.phonenumbers p JOIN a.comments c',
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

    public function testSupportsFunctionalExpressionsInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE TRIM(u.name) = 'someone'",
            // String quoting in the SQL usually depends on the database platform.
            // This test works with a mock connection which uses ' for string quoting.
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE TRIM(FROM c0_.name) = 'someone'"
        );
    }

    // Ticket #973
    public function testSupportsSingleValuedInExpressionWithoutSpacesInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN(46)",
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE c0_.id IN (46)"
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
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN (1)',
            'SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.id NOT IN (1)'
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
    
    public function testSupportsMemberOfExpression()
    {
        // "Get all users who have $phone as a phonenumber." (*cough* doesnt really make sense...)
        $q1 = $this->_em->createQuery('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE :param MEMBER OF u.phonenumbers');
        $phone = new \Doctrine\Tests\Models\CMS\CmsPhonenumber;
        $phone->phonenumber = 101;
        $q1->setParameter('param', $phone);
        
        $this->assertEquals(
            'SELECT c0_.id AS id0 FROM cms_users c0_ WHERE EXISTS (SELECT 1 FROM cms_phonenumbers c1_ WHERE c0_.id = c1_.user_id AND c1_.phonenumber = ?)',
            $q1->getSql()
        );
        
        // "Get all users who are members of $group."
        $q2 = $this->_em->createQuery('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE :param MEMBER OF u.groups');
        $group = new \Doctrine\Tests\Models\CMS\CmsGroup;
        $group->id = 101;
        $q2->setParameter('param', $group);
        
        $this->assertEquals(
            'SELECT c0_.id AS id0 FROM cms_users c0_ WHERE EXISTS (SELECT 1 FROM cms_users_groups c1_ INNER JOIN cms_groups c2_ ON c1_.user_id = c0_.id WHERE c1_.group_id = c2_.id AND c2_.id = ?)',
            $q2->getSql()
        );
        
        // "Get all persons who have $person as a friend."
        // Tough one: Many-many self-referencing ("friends") with class table inheritance
        $this->_em->getConfiguration()->setAllowPartialObjects(false);
        $q3 = $this->_em->createQuery('SELECT p FROM Doctrine\Tests\Models\Company\CompanyPerson p WHERE :param MEMBER OF p.friends');
        $person = new \Doctrine\Tests\Models\Company\CompanyPerson;
        $this->_em->getClassMetadata(get_class($person))->setIdentifierValues($person, 101);
        $q3->setParameter('param', $person);
        $this->assertEquals(
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.title AS title2, c2_.salary AS salary3, c2_.department AS department4, c0_.discr AS discr5, c0_.spouse_id AS spouse_id6 FROM company_persons c0_ LEFT JOIN company_managers c1_ ON c0_.id = c1_.id LEFT JOIN company_employees c2_ ON c0_.id = c2_.id WHERE EXISTS (SELECT 1 FROM company_persons_friends c3_ INNER JOIN company_persons c4_ ON c3_.person_id = c0_.id WHERE c3_.friend_id = c4_.id AND c4_.id = ?)',
            $q3->getSql()
        );
        $this->_em->getConfiguration()->setAllowPartialObjects(true);
    }

    public function testSupportsCurrentDateFunction()
    {
      $q = $this->_em->createQuery('SELECT d.id FROM Doctrine\Tests\Models\Generic\DateTimeModel d WHERE d.datetime > current_date()');
      $this->assertEquals('SELECT d0_.id AS id0 FROM date_time_model d0_ WHERE d0_.datetime > CURRENT_DATE', $q->getSql());
    }

    public function testSupportsCurrentTimeFunction()
    {
      $q = $this->_em->createQuery('SELECT d.id FROM Doctrine\Tests\Models\Generic\DateTimeModel d WHERE d.time > current_time()');
      $this->assertEquals('SELECT d0_.id AS id0 FROM date_time_model d0_ WHERE d0_.time > CURRENT_TIME', $q->getSql());
    }

    public function testSupportsCurrentTimestampFunction()
    {
      $q = $this->_em->createQuery('SELECT d.id FROM Doctrine\Tests\Models\Generic\DateTimeModel d WHERE d.datetime > current_timestamp()');
      $this->assertEquals('SELECT d0_.id AS id0 FROM date_time_model d0_ WHERE d0_.datetime > CURRENT_TIMESTAMP', $q->getSql());
    }

    /*public function testExistsExpressionInWhereCorrelatedSubqueryAssocCondition()
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
    }*/

    public function testLimitFromQueryClass()
    {
        $q = $this->_em
            ->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u')
            ->setMaxResults(10);

        $this->assertEquals('SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ LIMIT 10', $q->getSql());
    }

    public function testLimitAndOffsetFromQueryClass()
    {
        $q = $this->_em
            ->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u')
            ->setMaxResults(10)
            ->setFirstResult(0);

        $this->assertEquals('SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ OFFSET 0 LIMIT 10', $q->getSql());
    }
    
    public function testSizeFunction()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE SIZE(u.phonenumbers) > 1",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (SELECT COUNT(c1_.user_id) FROM cms_phonenumbers c1_ WHERE c1_.user_id = c0_.id) > 1"
        );
    }
    
    public function testEmptyCollectionComparisonExpression()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.phonenumbers IS EMPTY",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (SELECT COUNT(c1_.user_id) FROM cms_phonenumbers c1_ WHERE c1_.user_id = c0_.id) = 0"
        );
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.phonenumbers IS NOT EMPTY",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (SELECT COUNT(c1_.user_id) FROM cms_phonenumbers c1_ WHERE c1_.user_id = c0_.id) > 0"
        );
    }
    
    public function testNestedExpressions()
    {
        $this->assertSqlGeneration(
            "select u from Doctrine\Tests\Models\CMS\CmsUser u where u.id > 10 and u.id < 42 and ((u.id * 2) > 5)",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.id > 10 AND c0_.id < 42 AND ((c0_.id * 2) > 5)"
        );
    }
    
    public function testNestedExpressions2()
    {
        $this->assertSqlGeneration(
            "select u from Doctrine\Tests\Models\CMS\CmsUser u where (u.id > 10) and (u.id < 42 and ((u.id * 2) > 5)) or u.id <> 42",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE (c0_.id > 10) AND (c0_.id < 42 AND ((c0_.id * 2) > 5)) OR c0_.id <> 42"
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
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, (SELECT COUNT(c1_.user_id) FROM cms_articles c1_ WHERE c1_.user_id = c0_.id) AS sclr4 FROM cms_users c0_ ORDER BY sclr4 ASC"
        );
    }
    
    /* Not yet implemented, needs more thought
    public function testSingleValuedAssociationFieldInWhere()
    {
        $this->assertSqlGeneration(
            "SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p WHERE p.user = ?1",
            "SELECT c0_.phonenumber AS phonenumber0 FROM cms_phonenumbers c0_ WHERE c0_.user_id = ?"
        );
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.address = ?1",
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ WHERE c0_.id = (SELECT c1_.user_id FROM cms_addresses c1_ WHERE c1_.id = ?)"
        );
    }*/
}
