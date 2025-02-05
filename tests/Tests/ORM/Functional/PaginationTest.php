<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\SqlOutputWalker;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TreeWalkerAdapter;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use RuntimeException;

use function count;
use function iterator_to_array;
use function sprintf;

#[Group('DDC-1613')]
class PaginationTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
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

    #[DataProvider('useOutputWalkers')]
    public function testCountSimpleWithoutJoin($useOutputWalkers): void
    {
        $dql   = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        self::assertCount(9, $paginator);
    }

    #[DataProvider('useOutputWalkers')]
    public function testCountWithFetchJoin($useOutputWalkers): void
    {
        $dql   = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        self::assertCount(9, $paginator);
    }

    public function testCountComplexWithOutputWalker(): void
    {
        $dql   = 'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(true);
        self::assertCount(3, $paginator);
    }

    public function testCountComplexWithoutOutputWalker(): void
    {
        $dql   = 'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot count query that uses a HAVING clause. Use the output walkers for pagination');

        self::assertCount(3, $paginator);
    }

    #[DataProvider('useOutputWalkers')]
    public function testCountWithComplexScalarOrderBy($useOutputWalkers): void
    {
        $dql   = 'SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.imageWidth * l.imageHeight DESC';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        self::assertCount(9, $paginator);
    }

    #[DataProvider('useOutputWalkersAndFetchJoinCollection')]
    public function testIterateSimpleWithoutJoin($useOutputWalkers, $fetchJoinCollection): void
    {
        $dql   = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        self::assertCount(9, $paginator->getIterator());

        // Test with limit
        $query->setMaxResults(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        self::assertCount(3, $paginator->getIterator());

        // Test with limit and offset
        $query->setMaxResults(3)->setFirstResult(4);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        self::assertCount(3, $paginator->getIterator());
    }

    private function iterateWithOrderAsc($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField): void
    {
        // Ascending
        $dql   = $baseDql . ' ASC';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        self::assertCount(9, $iter);
        $result = iterator_to_array($iter);
        self::assertEquals($checkField . '0', $result[0]->$checkField);
    }

    private function iterateWithOrderAscWithLimit($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField): void
    {
        // Ascending
        $dql   = $baseDql . ' ASC';
        $query = $this->_em->createQuery($dql);

        // With limit
        $query->setMaxResults(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        self::assertCount(3, $iter);
        $result = iterator_to_array($iter);
        self::assertEquals($checkField . '0', $result[0]->$checkField);
    }

    private function iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField): void
    {
        // Ascending
        $dql   = $baseDql . ' ASC';
        $query = $this->_em->createQuery($dql);

        // With offset
        $query->setMaxResults(3)->setFirstResult(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        self::assertCount(3, $iter);
        $result = iterator_to_array($iter);
        self::assertEquals($checkField . '3', $result[0]->$checkField);
    }

    private function iterateWithOrderDesc($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField): void
    {
        $dql   = $baseDql . ' DESC';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        self::assertCount(9, $iter);
        $result = iterator_to_array($iter);
        self::assertEquals($checkField . '8', $result[0]->$checkField);
    }

    private function iterateWithOrderDescWithLimit($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField): void
    {
        $dql   = $baseDql . ' DESC';
        $query = $this->_em->createQuery($dql);

        // With limit
        $query->setMaxResults(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        self::assertCount(3, $iter);
        $result = iterator_to_array($iter);
        self::assertEquals($checkField . '8', $result[0]->$checkField);
    }

    private function iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField): void
    {
        $dql   = $baseDql . ' DESC';
        $query = $this->_em->createQuery($dql);

        // With offset
        $query->setMaxResults(3)->setFirstResult(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        self::assertCount(3, $iter);
        $result = iterator_to_array($iter);
        self::assertEquals($checkField . '5', $result[0]->$checkField);
    }

    #[DataProvider('useOutputWalkersAndFetchJoinCollection')]
    public function testIterateSimpleWithoutJoinWithOrder($useOutputWalkers, $fetchJoinCollection): void
    {
        // Ascending
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username';
        $this->iterateWithOrderAsc($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
        $this->iterateWithOrderDesc($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
    }

    #[DataProvider('useOutputWalkersAndFetchJoinCollection')]
    public function testIterateSimpleWithoutJoinWithOrderAndLimit($useOutputWalkers, $fetchJoinCollection): void
    {
        // Ascending
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
    }

    #[DataProvider('useOutputWalkersAndFetchJoinCollection')]
    public function testIterateSimpleWithoutJoinWithOrderAndLimitAndOffset($useOutputWalkers, $fetchJoinCollection): void
    {
        // Ascending
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateSimpleWithOutputWalkerWithoutJoinWithComplexOrder($fetchJoinCollection): void
    {
        // Ascending
        $dql = 'SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.imageWidth * l.imageHeight';
        $this->iterateWithOrderAsc(true, $fetchJoinCollection, $dql, 'image');
        $this->iterateWithOrderDesc(true, $fetchJoinCollection, $dql, 'image');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateSimpleWithOutputWalkerWithoutJoinWithComplexOrderAndLimit($fetchJoinCollection): void
    {
        // Ascending
        $dql = 'SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.imageWidth * l.imageHeight';
        $this->iterateWithOrderAscWithLimit(true, $fetchJoinCollection, $dql, 'image');
        $this->iterateWithOrderDescWithLimit(true, $fetchJoinCollection, $dql, 'image');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateSimpleWithOutputWalkerWithoutJoinWithComplexOrderAndLimitAndOffset($fetchJoinCollection): void
    {
        // Ascending
        $dql = 'SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.imageWidth * l.imageHeight';
        $this->iterateWithOrderAscWithLimitAndOffset(true, $fetchJoinCollection, $dql, 'image');
        $this->iterateWithOrderDescWithLimitAndOffset(true, $fetchJoinCollection, $dql, 'image');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoin($useOutputWalkers): void
    {
        $dql   = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        self::assertCount(9, $paginator->getIterator());
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinWithOrder($useOutputWalkers): void
    {
        $dql = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g ORDER BY u.username';
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dql, 'username');
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dql, 'username');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinWithOrderAndLimit($useOutputWalkers): void
    {
        $dql = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g ORDER BY u.username';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, true, $dql, 'username');
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, true, $dql, 'username');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinWithOrderAndLimitAndOffset($useOutputWalkers): void
    {
        $dql = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g ORDER BY u.username';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, true, $dql, 'username');
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, true, $dql, 'username');
    }

    #[DataProvider('useOutputWalkersAndFetchJoinCollection')]
    public function testIterateWithRegularJoinWithOrderByColumnFromJoined($useOutputWalkers, $fetchJoinCollection): void
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAsc($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
        $this->iterateWithOrderDesc($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
    }

    #[DataProvider('useOutputWalkersAndFetchJoinCollection')]
    public function testIterateWithRegularJoinWithOrderByColumnFromJoinedWithLimit($useOutputWalkers, $fetchJoinCollection): void
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
    }

    #[DataProvider('useOutputWalkersAndFetchJoinCollection')]
    public function testIterateWithRegularJoinWithOrderByColumnFromJoinedWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection): void
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, $fetchJoinCollection, $dql, 'username');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateWithOutputWalkersWithRegularJoinWithComplexOrderByReferencingJoined($fetchJoinCollection): void
    {
        // long function name is loooooooooooong

        $dql = 'SELECT c FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.imageHeight * l.imageWidth';
        $this->iterateWithOrderAsc(true, $fetchJoinCollection, $dql, 'name');
        $this->iterateWithOrderDesc(true, $fetchJoinCollection, $dql, 'name');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateWithOutputWalkersWithRegularJoinWithComplexOrderByReferencingJoinedWithLimit($fetchJoinCollection): void
    {
        // long function name is loooooooooooong

        $dql = 'SELECT c FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.imageHeight * l.imageWidth';
        $this->iterateWithOrderAscWithLimit(true, $fetchJoinCollection, $dql, 'name');
        $this->iterateWithOrderDescWithLimit(true, $fetchJoinCollection, $dql, 'name');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateWithOutputWalkersWithRegularJoinWithComplexOrderByReferencingJoinedWithLimitAndOffset($fetchJoinCollection): void
    {
        // long function name is loooooooooooong

        $dql = 'SELECT c FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.imageHeight * l.imageWidth';
        $this->iterateWithOrderAscWithLimitAndOffset(true, $fetchJoinCollection, $dql, 'name');
        $this->iterateWithOrderDescWithLimitAndOffset(true, $fetchJoinCollection, $dql, 'name');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinWithOrderByColumnFromJoined($useOutputWalkers): void
    {
        $dql = 'SELECT u,g,e FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dql, 'username');
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dql, 'username');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinWithOrderByColumnFromJoinedWithLimit($useOutputWalkers): void
    {
        $dql = 'SELECT u,g,e FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, true, $dql, 'username');
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, true, $dql, 'username');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinWithOrderByColumnFromJoinedWithLimitAndOffset($useOutputWalkers): void
    {
        $dql = 'SELECT u,g,e FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, true, $dql, 'username');
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, true, $dql, 'username');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateWithOutputWalkersWithFetchJoinWithComplexOrderByReferencingJoined($fetchJoinCollection): void
    {
        $dql = 'SELECT c,l FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.imageWidth * l.imageHeight';
        $this->iterateWithOrderAsc(true, $fetchJoinCollection, $dql, 'name');
        $this->iterateWithOrderDesc(true, $fetchJoinCollection, $dql, 'name');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateWithOutputWalkersWithFetchJoinWithComplexOrderByReferencingJoinedWithLimit($fetchJoinCollection): void
    {
        $dql = 'SELECT c,l FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.imageWidth * l.imageHeight';
        $this->iterateWithOrderAscWithLimit(true, $fetchJoinCollection, $dql, 'name');
        $this->iterateWithOrderDescWithLimit(true, $fetchJoinCollection, $dql, 'name');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateWithOutputWalkersWithFetchJoinWithComplexOrderByReferencingJoinedWithLimitAndOffset($fetchJoinCollection): void
    {
        $dql = 'SELECT c,l FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.imageWidth * l.imageHeight';
        $this->iterateWithOrderAscWithLimitAndOffset(true, $fetchJoinCollection, $dql, 'name');
        $this->iterateWithOrderDescWithLimitAndOffset(true, $fetchJoinCollection, $dql, 'name');
    }

    #[DataProvider('fetchJoinCollection')]
    public function testIterateWithOutputWalkersWithFetchJoinWithComplexOrderByReferencingJoinedWithLimitAndOffsetWithInheritanceType($fetchJoinCollection): void
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\Pagination\User u ORDER BY u.id';
        $this->iterateWithOrderAscWithLimit(true, $fetchJoinCollection, $dql, 'name');
        $this->iterateWithOrderDescWithLimit(true, $fetchJoinCollection, $dql, 'name');
    }

    public function testIterateComplexWithOutputWalker(): void
    {
        $dql   = 'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(true);
        self::assertCount(3, $paginator->getIterator());
    }

    public function testJoinedClassTableInheritance(): void
    {
        $dql   = 'SELECT c FROM Doctrine\Tests\Models\Company\CompanyManager c ORDER BY c.startDate';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        self::assertCount(1, $paginator->getIterator());
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromBoth($useOutputWalkers): void
    {
        $dql     = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $dqlAsc  = $dql . ' ASC, d.name';
        $dqlDesc = $dql . ' DESC, d.name';
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dqlAsc, 'name');
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dqlDesc, 'name');
    }

    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromBothWithLimitWithOutputWalker(): void
    {
        $dql     = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $dqlAsc  = $dql . ' ASC, d.name';
        $dqlDesc = $dql . ' DESC, d.name';
        $this->iterateWithOrderAscWithLimit(true, true, $dqlAsc, 'name');
        $this->iterateWithOrderDescWithLimit(true, true, $dqlDesc, 'name');
    }

    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromBothWithLimitWithoutOutputWalker(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a fetch joined to-many association. Use output walkers.');

        $dql     = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $dqlAsc  = $dql . ' ASC, d.name';
        $dqlDesc = $dql . ' DESC, d.name';
        $this->iterateWithOrderAscWithLimit(false, true, $dqlAsc, 'name');
        $this->iterateWithOrderDescWithLimit(false, true, $dqlDesc, 'name');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromRoot($useOutputWalkers): void
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dql, 'name');
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dql, 'name');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromRootWithLimit($useOutputWalkers): void
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $this->iterateWithOrderAscWithLimit($useOutputWalkers, true, $dql, 'name');
        $this->iterateWithOrderDescWithLimit($useOutputWalkers, true, $dql, 'name');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromRootWithLimitAndOffset($useOutputWalkers): void
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY c.name';
        $this->iterateWithOrderAscWithLimitAndOffset($useOutputWalkers, true, $dql, 'name');
        $this->iterateWithOrderDescWithLimitAndOffset($useOutputWalkers, true, $dql, 'name');
    }

    #[DataProvider('useOutputWalkers')]
    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromJoined($useOutputWalkers): void
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY d.name';
        $this->iterateWithOrderAsc($useOutputWalkers, true, $dql, 'name');
        $this->iterateWithOrderDesc($useOutputWalkers, true, $dql, 'name');
    }

    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromJoinedWithLimitWithOutputWalker(): void
    {
        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY d.name';
        $this->iterateWithOrderAscWithLimit(true, true, $dql, 'name');
        $this->iterateWithOrderDescWithLimit(true, true, $dql, 'name');
    }

    public function testIterateWithFetchJoinOneToManyWithOrderByColumnFromJoinedWithLimitWithoutOutputWalker(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a fetch joined to-many association. Use output walkers.');

        $dql = 'SELECT c, d FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.departments d ORDER BY d.name';

        $this->iterateWithOrderAscWithLimit(false, true, $dql, 'name');
        $this->iterateWithOrderDescWithLimit(false, true, $dql, 'name');
    }

    public function testCountWithCountSubqueryInWhereClauseWithOutputWalker(): void
    {
        $dql   = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((SELECT COUNT(s.id) FROM Doctrine\Tests\Models\CMS\CmsUser s) = 9) ORDER BY u.id desc';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(true);
        self::assertCount(9, $paginator);
    }

    public function testIterateWithCountSubqueryInWhereClause(): void
    {
        $dql   = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((SELECT COUNT(s.id) FROM Doctrine\Tests\Models\CMS\CmsUser s) = 9) ORDER BY u.id desc';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(true);

        $users = iterator_to_array($paginator->getIterator());
        self::assertCount(9, $users);
        foreach ($users as $i => $user) {
            self::assertEquals('username' . (8 - $i), $user->username);
        }
    }

    public function testDetectOutputWalker(): void
    {
        // This query works using the output walkers but causes an exception using the TreeWalker
        $dql   = 'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0';
        $query = $this->_em->createQuery($dql);

        // If the Paginator detects the custom output walker it should fall back to using the
        // Tree walkers for pagination, which leads to an exception. If the query works, the output walkers were used
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class);
        $paginator = new Paginator($query);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot count query that uses a HAVING clause. Use the output walkers for pagination');

        count($paginator);
    }

    /**
     * Test using a paginator when the entity attribute name and corresponding column name are not the same.
     */
    public function testPaginationWithColumnAttributeNameDifference(): void
    {
        $dql   = 'SELECT c FROM Doctrine\Tests\Models\Pagination\Company c ORDER BY c.id';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->getIterator();

        self::assertCount(9, $paginator->getIterator());
    }

    public function testCloneQuery(): void
    {
        $dql   = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->getIterator();

        self::assertTrue($query->getParameters()->isEmpty());
    }

    public function testQueryWalkerIsKept(): void
    {
        $dql   = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u';
        $query = $this->_em->createQuery($dql);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CustomPaginationTestTreeWalker::class]);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(false);
        self::assertCount(1, $paginator->getIterator());
        self::assertEquals(1, $paginator->count());
    }

    #[Group('GH-7890')]
    public function testCustomIdTypeWithoutOutputWalker(): void
    {
        $this->_em->persist(new CustomIdObjectTypeParent(new CustomIdObject('foo')));
        $this->_em->flush();

        $dql   = 'SELECT p FROM Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent p';
        $query = $this->_em->createQuery($dql);
        $query->setMaxResults(1);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(false);

        $matchedItems = iterator_to_array($paginator->getIterator());

        self::assertCount(1, $matchedItems);
        self::assertInstanceOf(CustomIdObjectTypeParent::class, $matchedItems[0]);
        self::assertSame('foo', (string) $matchedItems[0]->id);
    }

    public function testCountQueryStripsParametersInSelect(): void
    {
        $query = $this->_em->createQuery(
            'SELECT u, (CASE WHEN u.id < :vipMaxId THEN 1 ELSE 0 END) AS hidden promotedFirst
            FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u
            WHERE u.id < :id or 1=1',
        );
        $query->setParameter('vipMaxId', 10);
        $query->setParameter('id', 100);
        $query->setFirstResult(0)->setMaxResults(null);

        $paginator = new Paginator($query);

        $getCountQuery = new ReflectionMethod($paginator, 'getCountQuery');

        self::assertCount(2, $getCountQuery->invoke($paginator)->getParameters());
        self::assertCount(9, $paginator);

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlOutputWalker::class);

        $paginator = new Paginator($query);

        // if select part of query is replaced with count(...) paginator should remove
        // parameters from query object not used in new query.
        self::assertCount(1, $getCountQuery->invoke($paginator)->getParameters());
        self::assertCount(9, $paginator);
    }

    #[DataProvider('useOutputWalkersAndFetchJoinCollection')]
    public function testPaginationWithSubSelectOrderByExpression($useOutputWalker, $fetchJoinCollection): void
    {
        $query = $this->_em->createQuery(
            <<<'SQL'
            SELECT u,
                (
                    SELECT MAX(a.version)
                    FROM Doctrine\Tests\Models\CMS\CmsArticle a
                    WHERE a.user = u
                ) AS HIDDEN max_version
            FROM Doctrine\Tests\Models\CMS\CmsUser u
            ORDER BY max_version DESC
SQL,
        );

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalker);

        self::assertCount(9, $paginator->getIterator());
    }

    public function testDifferentResultLengthsDoNotRequireExtraQueryCacheEntries(): void
    {
        $dql   = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id >= :id';
        $query = $this->_em->createQuery($dql);
        $query->setMaxResults(10);

        $query->setParameter('id', 1);
        $paginator     = new Paginator($query);
        $initialResult = iterator_to_array($paginator->getIterator()); // exercise the Paginator
        self::assertCount(9, $initialResult);

        $initialQueryCount = count(self::$queryCache->getValues());

        $query->setParameter('id', $initialResult[1]->id); // skip the first result element
        $paginator = new Paginator($query);
        self::assertCount(8, $paginator->getIterator()); // exercise the Paginator again, with a smaller result set

        $newCount = count(self::$queryCache->getValues());

        self::assertSame($initialQueryCount, $newCount);
    }

    public function populate(): void
    {
        $groups = [];
        for ($j = 0; $j < 3; $j++) {
            $group       = new CmsGroup();
            $group->name = sprintf('group%d', $j);
            $groups[]    = $group;
            $this->_em->persist($group);
        }

        for ($i = 0; $i < 9; $i++) {
            $user               = new CmsUser();
            $user->name         = 'Name' . $i;
            $user->username     = 'username' . $i;
            $user->status       = 'active';
            $user->email        = new CmsEmail();
            $user->email->user  = $user;
            $user->email->email = sprintf('email%d', $i);
            for ($j = 0; $j < 3; $j++) {
                $user->addGroup($groups[$j]);
            }

            $this->_em->persist($user);
            for ($j = 0; $j < $i + 1; $j++) {
                $article        = new CmsArticle();
                $article->topic = 'topic' . $i . $j;
                $article->text  = 'text' . $i . $j;
                $article->setAuthor($user);
                $article->version = 0;
                $this->_em->persist($article);
            }
        }

        for ($i = 0; $i < 9; $i++) {
            $company                    = new Company();
            $company->name              = 'name' . $i;
            $company->logo              = new Logo();
            $company->logo->image       = 'image' . $i;
            $company->logo->imageWidth  = 100 + $i;
            $company->logo->imageHeight = 100 + $i;
            $company->logo->company     = $company;
            for ($j = 0; $j < 3; $j++) {
                $department             = new Department();
                $department->name       = 'name' . $i . $j;
                $department->company    = $company;
                $company->departments[] = $department;
            }

            $this->_em->persist($company);
        }

        for ($i = 0; $i < 9; $i++) {
            $user        = new User1();
            $user->name  = 'name' . $i;
            $user->email = 'email' . $i;
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

    /** @phpstan-return list<array{bool}> */
    public static function useOutputWalkers(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /** @phpstan-return list<array{bool}> */
    public static function fetchJoinCollection(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /** @phpstan-return list<array{bool, bool}> */
    public static function useOutputWalkersAndFetchJoinCollection(): array
    {
        return [
            [true, false],
            [true, true],
            [false, false],
            [false, true],
        ];
    }
}

class CustomPaginationTestTreeWalker extends TreeWalkerAdapter
{
    public function walkSelectStatement(SelectStatement $selectStatement): void
    {
        $condition = new ConditionalPrimary();

        $path       = new PathExpression(PathExpression::TYPE_STATE_FIELD, 'u', 'name');
        $path->type = PathExpression::TYPE_STATE_FIELD;

        $condition->simpleConditionalExpression = new ComparisonExpression(
            $path,
            '=',
            new Literal(Literal::STRING, 'Name1'),
        );

        $selectStatement->whereClause = new WhereClause($condition);
    }
}
