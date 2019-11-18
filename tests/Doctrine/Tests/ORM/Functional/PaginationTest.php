<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent;
use Doctrine\Tests\Models\Pagination\Company;
use Doctrine\Tests\Models\Pagination\Department;
use Doctrine\Tests\Models\Pagination\Logo;
use Doctrine\Tests\Models\Pagination\User1;
use Doctrine\Tests\OrmFunctionalTestCase;
use ReflectionMethod;
use function iterator_to_array;

/**
 * @group DDC-1613
 */
class PaginationTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        $this->useModelSet('pagination');
        $this->useModelSet('company');
        $this->useModelSet('custom_id_object_type');

        if (DBALType::hasType(CustomIdObjectType::NAME)) {
            DBALType::overrideType(CustomIdObjectType::NAME, CustomIdObjectType::class);
        } else {
            DBALType::addType(CustomIdObjectType::NAME, CustomIdObjectType::class);
        }

        parent::setUp();
        $this->populate();
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testCountSimpleWithoutJoin($useOutputWalkers)
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(9, $paginator);
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testCountWithFetchJoin($useOutputWalkers)
    {
        $dql = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(9, $paginator);
    }

    public function testCountComplexWithOutputWalker()
    {
        $dql = 'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(true);
        $this->assertCount(3, $paginator);
    }

    public function testCountComplexWithoutOutputWalker()
    {
        $dql = 'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot count query that uses a HAVING clause. Use the output walkers for pagination');

        $this->assertCount(3, $paginator);
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testCountWithComplexScalarOrderBy($useOutputWalkers)
    {
        $dql = 'SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.image_width * l.image_height DESC';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(9, $paginator);
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateSimpleWithoutJoin($useOutputWalkers, $fetchJoinCollection)
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(9, $paginator->getIterator());

        // Test with limit
        $query->setMaxResults(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(3, $paginator->getIterator());

        // Test with limit and offset
        $query->setMaxResults(3)->setFirstResult(4);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(3, $paginator->getIterator());
    }

    private function iterateWithOrderAsc($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField)
    {
        // Ascending
        $dql = "$baseDql ASC";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(9, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "0", $result[0]->$checkField);
    }

    private function iterateWithOrderAscWithLimit($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField)
    {
        // Ascending
        $dql = "$baseDql ASC";
        $query = $this->_em->createQuery($dql);

        // With limit
        $query->setMaxResults(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(3, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "0", $result[0]->$checkField);
    }

    private function iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField)
    {
        // Ascending
        $dql = "$baseDql ASC";
        $query = $this->_em->createQuery($dql);

        // With offset
        $query->setMaxResults(3)->setFirstResult(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(3, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "3", $result[0]->$checkField);
    }

    private function iterateWithOrderDesc($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField)
    {
        $dql = "$baseDql DESC";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(9, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "8", $result[0]->$checkField);
    }

    private function iterateWithOrderDescWithLimit($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField)
    {
        $dql = "$baseDql DESC";
        $query = $this->_em->createQuery($dql);

        // With limit
        $query->setMaxResults(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(3, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "8", $result[0]->$checkField);
    }

    private function iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField)
    {
        $dql = "$baseDql DESC";
        $query = $this->_em->createQuery($dql);

        // With offset
        $query->setMaxResults(3)->setFirstResult(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(3, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "5", $result[0]->$checkField);
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateSimpleWithoutJoinWithOrder($useOutputWalkers, $fetchJoinCollection)
    {
        // Ascending
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username';
        $this->iterateWithOrderAsc($useOutputWalkers, $fetchJoinCollection, $dql, "username");
        $this->iterateWithOrderDesc($useOutputWalkers, $fetchJoinCollection, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateSimpleWithoutJoinWithOrderAndLimit($useOutputWalkers, $fetchJoinCollection)
    {
        // Ascending
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, $fetchJoinCollection, $dql, "username");
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, $fetchJoinCollection, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateSimpleWithoutJoinWithOrderAndLimitAndOffset($useOutputWalkers, $fetchJoinCollection)
    {
        // Ascending
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $dql, "username");
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $dql, "username");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateSimpleWithOutputWalkerWithoutJoinWithComplexOrder($fetchJoinCollection)
    {
        // Ascending
        $dql = 'SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.image_width * l.image_height';
        $this->iterateWithOrderAsc(true, $fetchJoinCollection, $dql, "image");
        $this->iterateWithOrderDesc(true, $fetchJoinCollection, $dql, "image");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateSimpleWithOutputWalkerWithoutJoinWithComplexOrderAndLimit($fetchJoinCollection)
    {
        // Ascending
        $dql = 'SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.image_width * l.image_height';
        $this->iterateWithOrderAscWithLimit(true, $fetchJoinCollection, $dql, "image");
        $this->iterateWithOrderDescWithLimit(true, $fetchJoinCollection, $dql, "image");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateSimpleWithOutputWalkerWithoutJoinWithComplexOrderAndLimitAndOffset($fetchJoinCollection)
    {
        // Ascending
        $dql = 'SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.image_width * l.image_height';
        $this->iterateWithOrderAscWithLimitAndOffset(true, $fetchJoinCollection, $dql, "image");
        $this->iterateWithOrderDescWithLimitAndOffset(true, $fetchJoinCollection, $dql, "image");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoin($useOutputWalkers)
    {
        $dql = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(9, $paginator->getIterator());
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinWithOrder($useOutputWalkers)
    {
        $dql = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g ORDER BY u.username';
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dql, "username");
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinWithOrderAndLimit($useOutputWalkers)
    {
        $dql = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g ORDER BY u.username';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, true, $dql, "username");
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, true, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinWithOrderAndLimitAndOffset($useOutputWalkers)
    {
        $dql = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g ORDER BY u.username';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, true, $dql, "username");
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, true, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateWithRegularJoinWithOrderByColumnFromJoined($useOutputWalkers, $fetchJoinCollection)
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAsc($useOutputWalkers, $fetchJoinCollection, $dql, "username");
        $this->iterateWithOrderDesc($useOutputWalkers, $fetchJoinCollection, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateWithRegularJoinWithOrderByColumnFromJoinedWithLimit($useOutputWalkers, $fetchJoinCollection)
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, $fetchJoinCollection, $dql, "username");
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, $fetchJoinCollection, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateWithRegularJoinWithOrderByColumnFromJoinedWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection)
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $dql, "username");
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $dql, "username");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateWithOutputWalkersWithRegularJoinWithComplexOrderByReferencingJoined($fetchJoinCollection)
    {
        // long function name is loooooooooooong

        $dql = 'SELECT c FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.image_height * l.image_width';
        $this->iterateWithOrderAsc(true, $fetchJoinCollection, $dql, "name");
        $this->iterateWithOrderDesc(true, $fetchJoinCollection, $dql, "name");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateWithOutputWalkersWithRegularJoinWithComplexOrderByReferencingJoinedWithLimit($fetchJoinCollection)
    {
        // long function name is loooooooooooong

        $dql = 'SELECT c FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.image_height * l.image_width';
        $this->iterateWithOrderAscWithLimit(true, $fetchJoinCollection, $dql, "name");
        $this->iterateWithOrderDescWithLimit(true, $fetchJoinCollection, $dql, "name");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateWithOutputWalkersWithRegularJoinWithComplexOrderByReferencingJoinedWithLimitAndOffset($fetchJoinCollection)
    {
        // long function name is loooooooooooong

        $dql = 'SELECT c FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.image_height * l.image_width';
        $this->iterateWithOrderAscWithLimitAndOffset(true, $fetchJoinCollection, $dql, "name");
        $this->iterateWithOrderDescWithLimitAndOffset(true, $fetchJoinCollection, $dql, "name");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinWithOrderByColumnFromJoined($useOutputWalkers)
    {
        $dql = 'SELECT u,g,e FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dql, "username");
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinWithOrderByColumnFromJoinedWithLimit($useOutputWalkers)
    {
        $dql = 'SELECT u,g,e FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, true, $dql, "username");
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, true, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinWithOrderByColumnFromJoinedWithLimitAndOffset($useOutputWalkers)
    {
        $dql = 'SELECT u,g,e FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, true, $dql, "username");
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, true, $dql, "username");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateWithOutputWalkersWithFetchJoinWithComplexOrderByReferencingJoined($fetchJoinCollection)
    {
        $dql = 'SELECT c,l FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.image_width * l.image_height';
        $this->iterateWithOrderAsc(true, $fetchJoinCollection, $dql, "name");
        $this->iterateWithOrderDesc(true, $fetchJoinCollection, $dql, "name");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateWithOutputWalkersWithFetchJoinWithComplexOrderByReferencingJoinedWithLimit($fetchJoinCollection)
    {
        $dql = 'SELECT c,l FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.image_width * l.image_height';
        $this->iterateWithOrderAscWithLimit(true, $fetchJoinCollection, $dql, "name");
        $this->iterateWithOrderDescWithLimit(true, $fetchJoinCollection, $dql, "name");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateWithOutputWalkersWithFetchJoinWithComplexOrderByReferencingJoinedWithLimitAndOffset($fetchJoinCollection)
    {
        $dql = 'SELECT c,l FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.image_width * l.image_height';
        $this->iterateWithOrderAscWithLimitAndOffset(true, $fetchJoinCollection, $dql, "name");
        $this->iterateWithOrderDescWithLimitAndOffset(true, $fetchJoinCollection, $dql, "name");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateWithOutputWalkersWithFetchJoinWithComplexOrderByReferencingJoinedWithLimitAndOffsetWithInheritanceType($fetchJoinCollection)
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\Pagination\User u ORDER BY u.id';
        $this->iterateWithOrderAscWithLimit(true, $fetchJoinCollection, $dql, "name");
        $this->iterateWithOrderDescWithLimit(true, $fetchJoinCollection, $dql, "name");
    }

    public function testIterateComplexWithOutputWalker()
    {
        $dql = 'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(true);
        $this->assertCount(3, $paginator->getIterator());
    }

    public function testJoinedClassTableInheritance()
    {
        $dql = 'SELECT c FROM Doctrine\Tests\Models\Company\CompanyManager c ORDER BY c.startDate';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $this->assertCount(1, $paginator->getIterator());
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromBoth($useOutputWalkers)
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $dqlAsc = $dql . " ASC, d.name";
        $dqlDesc = $dql . " DESC, d.name";
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dqlAsc, "name");
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dqlDesc, "name");
    }

    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromBothWithLimitWithOutputWalker()
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $dqlAsc = $dql . " ASC, d.name";
        $dqlDesc = $dql . " DESC, d.name";
        $this->iterateWithOrderAscWithLimit(true, true, $dqlAsc, "name");
        $this->iterateWithOrderDescWithLimit(true, true, $dqlDesc, "name");
    }

    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromBothWithLimitWithoutOutputWalker()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a fetch joined to-many association. Use output walkers.');

        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $dqlAsc = $dql . " ASC, d.name";
        $dqlDesc = $dql . " DESC, d.name";
        $this->iterateWithOrderAscWithLimit(false, true, $dqlAsc, "name");
        $this->iterateWithOrderDescWithLimit(false, true, $dqlDesc, "name");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromRoot($useOutputWalkers)
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dql, "name");
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dql, "name");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromRootWithLimit($useOutputWalkers)
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, true, $dql, "name");
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, true, $dql, "name");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromRootWithLimitAndOffset($useOutputWalkers)
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, true, $dql, "name");
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, true, $dql, "name");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromJoined($useOutputWalkers)
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY d.name';
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dql, "name");
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dql, "name");
    }

    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromJoinedWithLimitWithOutputWalker()
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY d.name';
        $this->iterateWithOrderAscWithLimit(true, true, $dql, "name");
        $this->iterateWithOrderDescWithLimit(true, true, $dql, "name");
    }

    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromJoinedWithLimitWithoutOutputWalker()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a fetch joined to-many association. Use output walkers.');

        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY d.name';

        $this->iterateWithOrderAscWithLimit(false, true, $dql, "name");
        $this->iterateWithOrderDescWithLimit(false, true, $dql, "name");
    }

    public function testCountWithCountSubqueryInWhereClauseWithOutputWalker()
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((SELECT COUNT(s.id) FROM Doctrine\Tests\Models\CMS\CmsUser s) = 9) ORDER BY u.id desc";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(true);
        $this->assertCount(9, $paginator);
    }

    public function testIterateWithCountSubqueryInWhereClause()
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((SELECT COUNT(s.id) FROM Doctrine\Tests\Models\CMS\CmsUser s) = 9) ORDER BY u.id desc";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(true);

        $users = iterator_to_array($paginator->getIterator());
        $this->assertCount(9, $users);
        foreach ($users as $i => $user) {
            $this->assertEquals("username" . (8 - $i), $user->username);
        }
    }

    public function testDetectOutputWalker()
    {
        // This query works using the output walkers but causes an exception using the TreeWalker
        $dql = 'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0';
        $query = $this->_em->createQuery($dql);

        // If the Paginator detects the custom output walker it should fall back to using the
        // Tree walkers for pagination, which leads to an exception. If the query works, the output walkers were used
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, Query\SqlWalker::class);
        $paginator = new Paginator($query);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot count query that uses a HAVING clause. Use the output walkers for pagination');

        count($paginator);
    }

    /**
     * Test using a paginator when the entity attribute name and corresponding column name are not the same.
     */
    public function testPaginationWithColumnAttributeNameDifference()
    {
        $dql = 'SELECT c FROM Doctrine\Tests\Models\Pagination\Company c ORDER BY c.id';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->getIterator();

        $this->assertCount(9, $paginator->getIterator());
    }

    public function testCloneQuery()
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->getIterator();

        $this->assertTrue($query->getParameters()->isEmpty());
    }

    public function testQueryWalkerIsKept()
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u';
        $query = $this->_em->createQuery($dql);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CustomPaginationTestTreeWalker::class]);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(false);
        $this->assertCount(1, $paginator->getIterator());
        $this->assertEquals(1, $paginator->count());
    }

    /**
     * @group GH-7890
     */
    public function testCustomIdTypeWithoutOutputWalker()
    {
        $this->_em->persist(new CustomIdObjectTypeParent(new CustomIdObject('foo')));
        $this->_em->flush();

        $dql   = 'SELECT p FROM Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent p';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(false);

        $matchedItems = iterator_to_array($paginator->getIterator());

        self::assertCount(1, $matchedItems);
        self::assertInstanceOf(CustomIdObjectTypeParent::class, $matchedItems[0]);
        self::assertSame('foo', (string) $matchedItems[0]->id);
    }

    public function testCountQueryStripsParametersInSelect()
    {
        $query = $this->_em->createQuery(
            'SELECT u, (CASE WHEN u.id < :vipMaxId THEN 1 ELSE 0 END) AS hidden promotedFirst
            FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u
            WHERE u.id < :id or 1=1'
        );
        $query->setParameter('vipMaxId', 10);
        $query->setParameter('id', 100);
        $query->setFirstResult(null)->setMaxResults(null);

        $paginator = new Paginator($query);

        $getCountQuery = new ReflectionMethod($paginator, 'getCountQuery');

        $getCountQuery->setAccessible(true);

        $this->assertCount(2, $getCountQuery->invoke($paginator)->getParameters());
        $this->assertCount(9, $paginator);

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, Query\SqlWalker::class);

        $paginator = new Paginator($query);

        // if select part of query is replaced with count(...) paginator should remove
        // parameters from query object not used in new query.
        $this->assertCount(1, $getCountQuery->invoke($paginator)->getParameters());
        $this->assertCount(9, $paginator);
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testPaginationWithSubSelectOrderByExpression($useOutputWalker, $fetchJoinCollection)
    {
        $query = $this->_em->createQuery(
            "SELECT u, 
                (
                    SELECT MAX(a.version)
                    FROM Doctrine\\Tests\\Models\\CMS\\CmsArticle a
                    WHERE a.user = u
                ) AS HIDDEN max_version
            FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u
            ORDER BY max_version DESC"
        );

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalker);

        $this->assertCount(9, $paginator->getIterator());
    }

    public function populate()
    {
        $groups = [];
        for ($j = 0; $j < 3; $j++) {;
            $group = new CmsGroup();
            $group->name = "group$j";
            $groups[] = $group;
            $this->_em->persist($group);
        }

        for ($i = 0; $i < 9; $i++) {
            $user = new CmsUser();
            $user->name = "Name$i";
            $user->username = "username$i";
            $user->status = "active";
            $user->email = new CmsEmail();
            $user->email->user = $user;
            $user->email->email = "email$i";
            for ($j = 0; $j < 3; $j++) {
                $user->addGroup($groups[$j]);
            }
            $this->_em->persist($user);
            for ($j = 0; $j < $i + 1; $j++) {
                $article = new CmsArticle();
                $article->topic = "topic$i$j";
                $article->text = "text$i$j";
                $article->setAuthor($user);
                $article->version = 0;
                $this->_em->persist($article);
            }
        }

        for ($i = 0; $i < 9; $i++) {
            $company = new Company();
            $company->name = "name$i";
            $company->logo = new Logo();
            $company->logo->image = "image$i";
            $company->logo->image_width = 100 + $i;
            $company->logo->image_height = 100 + $i;
            $company->logo->company = $company;
            for($j=0;$j<3;$j++) {
                $department = new Department();
                $department->name = "name$i$j";
                $department->company = $company;
                $company->departments[] = $department;
            }
            $this->_em->persist($company);
        }

        for ($i = 0; $i < 9; $i++) {
            $user = new User1();
            $user->name = "name$i";
            $user->email = "email$i";
            $this->_em->persist($user);
        }

        $manager = new CompanyManager();
        $manager->setName('Roman B.');
        $manager->setTitle('Foo');
        $manager->setDepartment('IT');
        $manager->setSalary(100000);

        $this->_em->persist($manager);

        $this->_em->flush();
    }

    public function useOutputWalkers()
    {
        return [
            [true],
            [false],
        ];
    }

    public function fetchJoinCollection()
    {
        return [
            [true],
            [false],
        ];
    }

    public function useOutputWalkersAndFetchJoinCollection()
    {
        return [
            [true, false],
            [true, true],
            [false, false],
            [false, true],
        ];
    }
}

class CustomPaginationTestTreeWalker extends Query\TreeWalkerAdapter
{
    public function walkSelectStatement(Query\AST\SelectStatement $selectStatement)
    {
        $condition = new Query\AST\ConditionalPrimary();

        $path = new Query\AST\PathExpression(Query\AST\PathExpression::TYPE_STATE_FIELD, 'u', 'name');
        $path->type = Query\AST\PathExpression::TYPE_STATE_FIELD;

        $condition->simpleConditionalExpression = new Query\AST\ComparisonExpression(
            $path,
            '=',
            new Query\AST\Literal(Query\AST\Literal::STRING, 'Name1')
        );

        $selectStatement->whereClause = new Query\AST\WhereClause($condition);
    }
}
