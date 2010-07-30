<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

class SelectSqlGenerationTest extends \Doctrine\Tests\OrmTestCase
{
    private $_em;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
    }

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

            parent::assertEquals($sqlToBeConfirmed, $query->getSql());
            $query->free();
        } catch (\Exception $e) {
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
            "SELECT c0_.name AS name0 FROM cms_users c0_ WHERE TRIM(c0_.name) = 'someone'"
        );
    }

    public function testSupportsInstanceOfExpressionsInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF Doctrine\Tests\Models\Company\CompanyEmployee",
            "SELECT c0_.id AS id0, c0_.name AS name1, c0_.discr AS discr2 FROM company_persons c0_ WHERE c0_.discr = 'employee'"
        );
    }

    public function testSupportsInstanceOfExpressionsInWherePartInDeeperLevel()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyEmployee u WHERE u INSTANCE OF Doctrine\Tests\Models\Company\CompanyManager",
            "SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c0_.discr AS discr4 FROM company_employees c1_ INNER JOIN company_persons c0_ ON c1_.id = c0_.id WHERE c0_.discr = 'manager'"
        );
    }

    public function testSupportsInstanceOfExpressionsInWherePartInDeepestLevel()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyManager u WHERE u INSTANCE OF Doctrine\Tests\Models\Company\CompanyManager",
            "SELECT c0_.id AS id0, c0_.name AS name1, c1_.salary AS salary2, c1_.department AS department3, c2_.title AS title4, c0_.discr AS discr5 FROM company_managers c2_ INNER JOIN company_employees c1_ ON c2_.id = c1_.id INNER JOIN company_persons c0_ ON c2_.id = c0_.id WHERE c0_.discr = 'manager'"
        );
    }

    public function testSupportsInstanceOfExpressionsUsingInputParameterInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF ?1",
            "SELECT c0_.id AS id0, c0_.name AS name1, c0_.discr AS discr2 FROM company_persons c0_ WHERE c0_.discr = 'employee'",
            array(), array(1 => $this->_em->getClassMetadata('Doctrine\Tests\Models\Company\CompanyEmployee'))
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

    public function testSupportsMemberOfExpression()
    {
        // "Get all users who have $phone as a phonenumber." (*cough* doesnt really make sense...)
        $q1 = $this->_em->createQuery('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE :param MEMBER OF u.phonenumbers');
        $q1->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        $phone = new \Doctrine\Tests\Models\CMS\CmsPhonenumber;
        $phone->phonenumber = 101;
        $q1->setParameter('param', $phone);

        $this->assertEquals(
            'SELECT c0_.id AS id0 FROM cms_users c0_ WHERE EXISTS (SELECT 1 FROM cms_phonenumbers c1_ WHERE c0_.id = c1_.user_id AND c1_.phonenumber = ?)',
            $q1->getSql()
        );

        // "Get all users who are members of $group."
        $q2 = $this->_em->createQuery('SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE :param MEMBER OF u.groups');
        $q2->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        $group = new \Doctrine\Tests\Models\CMS\CmsGroup;
        $group->id = 101;
        $q2->setParameter('param', $group);

        $this->assertEquals(
            'SELECT c0_.id AS id0 FROM cms_users c0_ WHERE EXISTS (SELECT 1 FROM cms_users_groups c1_ INNER JOIN cms_groups c2_ ON c1_.group_id = c2_.id WHERE c1_.user_id = c0_.id AND c2_.id = ?)',
            $q2->getSql()
        );

        // "Get all persons who have $person as a friend."
        // Tough one: Many-many self-referencing ("friends") with class table inheritance
        $q3 = $this->_em->createQuery('SELECT p FROM Doctrine\Tests\Models\Company\CompanyPerson p WHERE :param MEMBER OF p.friends');
        $person = new \Doctrine\Tests\Models\Company\CompanyPerson;
        $this->_em->getClassMetadata(get_class($person))->setIdentifierValues($person, array('id' => 101));
        $q3->setParameter('param', $person);
        $this->assertEquals(
            'SELECT c0_.id AS id0, c0_.name AS name1, c1_.title AS title2, c1_.car_id AS car_id3, c2_.salary AS salary4, c2_.department AS department5, c0_.discr AS discr6, c0_.spouse_id AS spouse_id7 FROM company_persons c0_ LEFT JOIN company_managers c1_ ON c0_.id = c1_.id LEFT JOIN company_employees c2_ ON c0_.id = c2_.id WHERE EXISTS (SELECT 1 FROM company_persons_friends c3_ INNER JOIN company_persons c4_ ON c3_.friend_id = c4_.id WHERE c3_.person_id = c0_.id AND c4_.id = ?)',
            $q3->getSql()
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

        $this->assertEquals('SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ LIMIT 10 OFFSET 0', $q->getSql());
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
            "SELECT b0_.id AS id0, b0_.booleanField AS booleanField1 FROM boolean_model b0_ WHERE b0_.booleanField = 'true'"
        );

        $this->assertSqlGeneration(
            "SELECT b FROM Doctrine\Tests\Models\Generic\BooleanModel b WHERE b.booleanField = false",
            "SELECT b0_.id AS id0, b0_.booleanField AS booleanField1 FROM boolean_model b0_ WHERE b0_.booleanField = 'false'"
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
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 ".
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
}


class MyAbsFunction extends \Doctrine\ORM\Query\AST\Functions\FunctionNode
{
    public $simpleArithmeticExpression;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'ABS(' . $sqlWalker->walkSimpleArithmeticExpression(
            $this->simpleArithmeticExpression
        ) . ')';
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