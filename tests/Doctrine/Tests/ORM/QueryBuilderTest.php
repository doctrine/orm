<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for the QueryBuilder class used to build DQL query string in a
 * object oriented way.
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org
 * @since       2.0
 */
class QueryBuilderTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
    }

    protected function assertValidQueryBuilder(QueryBuilder $qb, $expectedDql)
    {
        $dql = $qb->getDQL();
        $q = $qb->getQuery();

        $this->assertEquals($expectedDql, $dql);
    }

    public function testSelectSetsType()
    {
        $qb = $this->_em->createQueryBuilder()
            ->delete(CmsUser::class, 'u')
            ->select('u.id', 'u.username');

        $this->assertEquals($qb->getType(), QueryBuilder::SELECT);
    }

    public function testEmptySelectSetsType()
    {
        $qb = $this->_em->createQueryBuilder()
            ->delete(CmsUser::class, 'u')
            ->select();

        $this->assertEquals($qb->getType(), QueryBuilder::SELECT);
    }

    public function testDeleteSetsType()
    {
        $qb = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->delete();

        $this->assertEquals($qb->getType(), QueryBuilder::DELETE);
    }

    public function testUpdateSetsType()
    {
        $qb = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->update();

        $this->assertEquals($qb->getType(), QueryBuilder::UPDATE);
    }

    public function testSimpleSelect()
    {
        $qb = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.id', 'u.username');

        $this->assertValidQueryBuilder($qb, 'SELECT u.id, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSimpleDelete()
    {
        $qb = $this->_em->createQueryBuilder()
            ->delete(CmsUser::class, 'u');

        $this->assertValidQueryBuilder($qb, 'DELETE Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSimpleSelectWithFromIndexBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u', 'u.id')
            ->select('u.id', 'u.username');

        $this->assertValidQueryBuilder($qb, 'SELECT u.id, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u INDEX BY u.id');
    }

    public function testSimpleSelectWithIndexBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->indexBy('u', 'u.id')
            ->select('u.id', 'u.username');

        $this->assertValidQueryBuilder($qb, 'SELECT u.id, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u INDEX BY u.id');
    }

    public function testSimpleUpdate()
    {
        $qb = $this->_em->createQueryBuilder()
            ->update(CmsUser::class, 'u')
            ->set('u.username', ':username');

        $this->assertValidQueryBuilder($qb, 'UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.username = :username');
    }

    public function testInnerJoin()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'a')
            ->from(CmsUser::class, 'u')
            ->innerJoin('u.articles', 'a');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a');
    }

    public function testComplexInnerJoin()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'a')
            ->from(CmsUser::class, 'u')
            ->innerJoin('u.articles', 'a', 'ON', 'u.id = a.author_id');

        $this->assertValidQueryBuilder(
            $qb,
            'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a ON u.id = a.author_id'
        );
    }

    public function testComplexInnerJoinWithIndexBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'a')
            ->from(CmsUser::class, 'u')
            ->innerJoin('u.articles', 'a', 'ON', 'u.id = a.author_id', 'a.name');

        $this->assertValidQueryBuilder(
            $qb,
            'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a INDEX BY a.name ON u.id = a.author_id'
        );
    }

    public function testLeftJoin()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'a')
            ->from(CmsUser::class, 'u')
            ->leftJoin('u.articles', 'a');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a');
    }

    public function testLeftJoinWithIndexBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'a')
            ->from(CmsUser::class, 'u')
            ->leftJoin('u.articles', 'a', null, null, 'a.name');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a INDEX BY a.name');
    }

    public function testMultipleFrom()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'g')
            ->from(CmsUser::class, 'u')
            ->from(CmsGroup::class, 'g');

        $this->assertValidQueryBuilder($qb, 'SELECT u, g FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsGroup g');
    }

    public function testMultipleFromWithIndexBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'g')
            ->from(CmsUser::class, 'u')
            ->from(CmsGroup::class, 'g')
            ->indexBy('g', 'g.id');

        $this->assertValidQueryBuilder($qb, 'SELECT u, g FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsGroup g INDEX BY g.id');
    }

    public function testMultipleFromWithJoin()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'g')
            ->from(CmsUser::class, 'u')
            ->from(CmsGroup::class, 'g')
            ->innerJoin('u.articles', 'a', 'ON', 'u.id = a.author_id');

        $this->assertValidQueryBuilder($qb, 'SELECT u, g FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a ON u.id = a.author_id, Doctrine\Tests\Models\CMS\CmsGroup g');
    }

    public function testMultipleFromWithMultipleJoin()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'g')
            ->from(CmsUser::class, 'u')
            ->from(CmsArticle::class, 'a')
            ->innerJoin('u.groups', 'g')
            ->leftJoin('u.address', 'ad')
            ->innerJoin('a.comments', 'c');

        $this->assertValidQueryBuilder($qb, 'SELECT u, g FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.groups g LEFT JOIN u.address ad, Doctrine\Tests\Models\CMS\CmsArticle a INNER JOIN a.comments c');
    }

    public function testWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.id = :uid');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid');
    }

    public function testComplexAndWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.id = :uid OR u.id = :uid2 OR u.id = :uid3')
            ->andWhere('u.name = :name');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid OR u.id = :uid2 OR u.id = :uid3) AND u.name = :name');
    }

    public function testAndWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.id = :uid')
            ->andWhere('u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid AND u.id = :uid2');
    }

    public function testOrWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.id = :uid')
            ->orWhere('u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid OR u.id = :uid2');
    }

    public function testComplexAndWhereOrWhereNesting()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where('u.id = :uid')
           ->orWhere('u.id = :uid2')
           ->andWhere('u.id = :uid3')
           ->orWhere('u.name = :name1', 'u.name = :name2')
           ->andWhere('u.name <> :noname');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (((u.id = :uid OR u.id = :uid2) AND u.id = :uid3) OR u.name = :name1 OR u.name = :name2) AND u.name <> :noname');
    }

    public function testAndWhereIn()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where('u.id = :uid')
           ->andWhere($qb->expr()->in('u.id', [1, 2, 3]));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid AND u.id IN(1, 2, 3)');
    }

    public function testOrWhereIn()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where('u.id = :uid')
           ->orWhere($qb->expr()->in('u.id', [1, 2, 3]));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid OR u.id IN(1, 2, 3)');
    }

    public function testAndWhereNotIn()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where('u.id = :uid')
           ->andWhere($qb->expr()->notIn('u.id', [1, 2, 3]));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid AND u.id NOT IN(1, 2, 3)');
    }

    public function testOrWhereNotIn()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where('u.id = :uid')
           ->orWhere($qb->expr()->notIn('u.id', [1, 2, 3]));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid OR u.id NOT IN(1, 2, 3)');
    }

    public function testGroupBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->groupBy('u.id')
            ->addGroupBy('u.username');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id, u.username');
    }

    public function testHaving()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1');
    }

    public function testAndHaving()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1')
            ->andHaving('COUNT(u.id) < 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1 AND COUNT(u.id) < 1');
    }

    public function testOrHaving()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1')
            ->andHaving('COUNT(u.id) < 1')
            ->orHaving('COUNT(u.id) > 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING (COUNT(u.id) > 1 AND COUNT(u.id) < 1) OR COUNT(u.id) > 1');
    }

    public function testOrderBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->orderBy('u.username', 'ASC');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC');
    }

    public function testOrderByWithExpression()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from(CmsUser::class, 'u')
            ->orderBy($qb->expr()->asc('u.username'));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC');
    }

    public function testAddOrderBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->orderBy('u.username', 'ASC')
            ->addOrderBy('u.username', 'DESC');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC, u.username DESC');
    }

    public function testAddOrderByWithExpression()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from(CmsUser::class, 'u')
            ->orderBy('u.username', 'ASC')
            ->addOrderBy($qb->expr()->desc('u.username'));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC, u.username DESC');
    }

    public function testAddCriteriaWhere()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from(CmsUser::class, 'u');

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('field', 'value'));

        $qb->addCriteria($criteria);

        $this->assertEquals('u.field = :field', (string) $qb->getDQLPart('where'));
        $this->assertNotNull($qb->getParameter('field'));
    }

    public function testAddMultipleSameCriteriaWhere()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('alias1')->from(CmsUser::class, 'alias1');

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->andX(
            $criteria->expr()->eq('field', 'value1'),
            $criteria->expr()->eq('field', 'value2')
        ));

        $qb->addCriteria($criteria);

        $this->assertEquals('alias1.field = :field AND alias1.field = :field_1', (string) $qb->getDQLPart('where'));
        $this->assertNotNull($qb->getParameter('field'));
        $this->assertNotNull($qb->getParameter('field_1'));
    }

    /**
     * @group DDC-2844
     */
    public function testAddCriteriaWhereWithMultipleParametersWithSameField()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('alias1')->from(CmsUser::class, 'alias1');

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('field', 'value1'));
        $criteria->andWhere($criteria->expr()->gt('field', 'value2'));

        $qb->addCriteria($criteria);

        $this->assertEquals('alias1.field = :field AND alias1.field > :field_1', (string) $qb->getDQLPart('where'));
        $this->assertSame('value1', $qb->getParameter('field')->getValue());
        $this->assertSame('value2', $qb->getParameter('field_1')->getValue());
    }

    /**
     * @group DDC-2844
     */
    public function testAddCriteriaWhereWithMultipleParametersWithDifferentFields()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('alias1')->from(CmsUser::class, 'alias1');

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('field1', 'value1'));
        $criteria->andWhere($criteria->expr()->gt('field2', 'value2'));

        $qb->addCriteria($criteria);

        $this->assertEquals('alias1.field1 = :field1 AND alias1.field2 > :field2', (string) $qb->getDQLPart('where'));
        $this->assertSame('value1', $qb->getParameter('field1')->getValue());
        $this->assertSame('value2', $qb->getParameter('field2')->getValue());
    }

    /**
     * @group DDC-2844
     */
    public function testAddCriteriaWhereWithMultipleParametersWithSubpathsAndDifferentProperties()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('alias1')->from(CmsUser::class, 'alias1');

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('field1', 'value1'));
        $criteria->andWhere($criteria->expr()->gt('field2', 'value2'));

        $qb->addCriteria($criteria);

        $this->assertEquals('alias1.field1 = :field1 AND alias1.field2 > :field2', (string) $qb->getDQLPart('where'));
        $this->assertSame('value1', $qb->getParameter('field1')->getValue());
        $this->assertSame('value2', $qb->getParameter('field2')->getValue());
    }

    /**
     * @group DDC-2844
     */
    public function testAddCriteriaWhereWithMultipleParametersWithSubpathsAndSameProperty()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('alias1')->from(CmsUser::class, 'alias1');

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('field1', 'value1'));
        $criteria->andWhere($criteria->expr()->gt('field1', 'value2'));

        $qb->addCriteria($criteria);

        $this->assertEquals('alias1.field1 = :field1 AND alias1.field1 > :field1_1', (string) $qb->getDQLPart('where'));
        $this->assertSame('value1', $qb->getParameter('field1')->getValue());
        $this->assertSame('value2', $qb->getParameter('field1_1')->getValue());
    }

    public function testAddCriteriaOrder()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from(CmsUser::class, 'u');

        $criteria = new Criteria();
        $criteria->orderBy(['field' => Criteria::DESC]);

        $qb->addCriteria($criteria);

        $this->assertCount(1, $orderBy = $qb->getDQLPart('orderBy'));
        $this->assertEquals('u.field DESC', (string) $orderBy[0]);
    }

    /**
     * @group DDC-3108
     */
    public function testAddCriteriaOrderOnJoinAlias()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from(CmsUser::class, 'u')
            ->join('u.article','a');

        $criteria = new Criteria();
        $criteria->orderBy(['a.field' => Criteria::DESC]);

        $qb->addCriteria($criteria);

        $this->assertCount(1, $orderBy = $qb->getDQLPart('orderBy'));
        $this->assertEquals('a.field DESC', (string) $orderBy[0]);
    }

    public function testAddCriteriaLimit()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from(CmsUser::class, 'u');

        $criteria = new Criteria();
        $criteria->setFirstResult(2);
        $criteria->setMaxResults(10);

        $qb->addCriteria($criteria);

        $this->assertEquals(2, $qb->getFirstResult());
        $this->assertEquals(10, $qb->getMaxResults());
    }

    public function testAddCriteriaUndefinedLimit()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from(CmsUser::class, 'u')
            ->setFirstResult(2)
            ->setMaxResults(10);

        $criteria = new Criteria();

        $qb->addCriteria($criteria);

        $this->assertEquals(2, $qb->getFirstResult());
        $this->assertEquals(10, $qb->getMaxResults());
    }

    public function testGetQuery()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u');
        $q = $qb->getQuery();

        $this->assertEquals(Query::class, get_class($q));
    }

    public function testSetParameter()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', 1);

        $parameter = new Parameter('id', 1, ParameterTypeInferer::inferType(1));
        $inferred  = $qb->getParameter('id');

        self::assertSame($parameter->getValue(), $inferred->getValue());
        self::assertSame($parameter->getType(), $inferred->getType());
        self::assertFalse($inferred->typeWasSpecified());
    }

    public function testSetParameters()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where($qb->expr()->orX('u.username = :username', 'u.username = :username2'));

        $parameters = new ArrayCollection();
        $parameters->add(new Parameter('username', 'jwage'));
        $parameters->add(new Parameter('username2', 'jonwage'));

        $qb->setParameters($parameters);

        $this->assertEquals($parameters, $qb->getQuery()->getParameters());
    }


    public function testGetParameters()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where('u.id = :id');

        $parameters = new ArrayCollection();
        $parameters->add(new Parameter('id', 1));

        $qb->setParameters($parameters);

        $this->assertEquals($parameters, $qb->getParameters());
    }

    public function testGetParameter()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.id = :id');

        $parameters = new ArrayCollection();
        $parameters->add(new Parameter('id', 1));

        $qb->setParameters($parameters);

        $this->assertEquals($parameters->first(), $qb->getParameter('id'));
    }

    public function testMultipleWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.id = :uid', 'u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid AND u.id = :uid2');
    }

    public function testMultipleAndWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->andWhere('u.id = :uid', 'u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid AND u.id = :uid2');
    }

    public function testMultipleOrWhere()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->orWhere('u.id = :uid', $qb->expr()->eq('u.id', ':uid2'));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid OR u.id = :uid2');
    }

    public function testComplexWhere()
    {
        $qb = $this->_em->createQueryBuilder();
        $orExpr = $qb->expr()->orX();
        $orExpr->add($qb->expr()->eq('u.id', ':uid3'));
        $orExpr->add($qb->expr()->in('u.id', [1]));

        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where($orExpr);

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid3 OR u.id IN(1)');
    }

    public function testWhereInWithStringLiterals()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where($qb->expr()->in('u.name', ['one', 'two', 'three']));

        $this->assertValidQueryBuilder($qb, "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IN('one', 'two', 'three')");

        $qb->where($qb->expr()->in('u.name', ["O'Reilly", "O'Neil", 'Smith']));

        $this->assertValidQueryBuilder($qb, "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IN('O''Reilly', 'O''Neil', 'Smith')");
    }

    public function testWhereInWithObjectLiterals()
    {
        $qb = $this->_em->createQueryBuilder();
        $expr = $this->_em->getExpressionBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where($expr->in('u.name', [$expr->literal('one'), $expr->literal('two'), $expr->literal('three')]));

        $this->assertValidQueryBuilder($qb, "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IN('one', 'two', 'three')");

        $qb->where($expr->in('u.name', [$expr->literal("O'Reilly"), $expr->literal("O'Neil"), $expr->literal('Smith')]));

        $this->assertValidQueryBuilder($qb, "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IN('O''Reilly', 'O''Neil', 'Smith')");
    }

    public function testNegation()
    {
        $expr = $this->_em->getExpressionBuilder();
        $orExpr = $expr->orX();
        $orExpr->add($expr->eq('u.id', ':uid3'));
        $orExpr->add($expr->not($expr->in('u.id', [1])));

        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where($orExpr);

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid3 OR NOT(u.id IN(1))');
    }

    public function testSomeAllAny()
    {
        $qb = $this->_em->createQueryBuilder();
        $expr = $this->_em->getExpressionBuilder();

        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->where($expr->gt('u.id', $expr->all('select a.id from Doctrine\Tests\Models\CMS\CmsArticle a')));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > ALL(select a.id from Doctrine\Tests\Models\CMS\CmsArticle a)');

    }

    public function testMultipleIsolatedQueryConstruction()
    {
        $qb = $this->_em->createQueryBuilder();
        $expr = $this->_em->getExpressionBuilder();

        $qb->select('u')->from(CmsUser::class, 'u');
        $qb->where($expr->eq('u.name', ':name'));
        $qb->setParameter('name', 'romanb');

        $q1 = $qb->getQuery();

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = :name', $q1->getDQL());
        $this->assertEquals(1, count($q1->getParameters()));

        // add another condition and construct a second query
        $qb->andWhere($expr->eq('u.id', ':id'));
        $qb->setParameter('id', 42);

        $q2 = $qb->getQuery();

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = :name AND u.id = :id', $q2->getDQL());
        $this->assertTrue($q1 !== $q2); // two different, independent queries
        $this->assertEquals(2, count($q2->getParameters()));
        $this->assertEquals(1, count($q1->getParameters())); // $q1 unaffected
    }

    public function testGetEntityManager()
    {
        $qb = $this->_em->createQueryBuilder();
        $this->assertEquals($this->_em, $qb->getEntityManager());
    }

    public function testInitialStateIsClean()
    {
        $qb = $this->_em->createQueryBuilder();
        $this->assertEquals(QueryBuilder::STATE_CLEAN, $qb->getState());
    }

    public function testAlteringQueryChangesStateToDirty()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u');

        $this->assertEquals(QueryBuilder::STATE_DIRTY, $qb->getState());
    }

    public function testSelectWithFuncExpression()
    {
        $qb = $this->_em->createQueryBuilder();
        $expr = $qb->expr();
        $qb->select($expr->count('e.id'));

        $this->assertValidQueryBuilder($qb, 'SELECT COUNT(e.id)');
    }

    public function testResetDQLPart()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.username = ?1')->orderBy('u.username');

        $this->assertEquals('u.username = ?1', (string)$qb->getDQLPart('where'));
        $this->assertEquals(1, count($qb->getDQLPart('orderBy')));

        $qb->resetDQLPart('where')->resetDQLPart('orderBy');

        $this->assertNull($qb->getDQLPart('where'));
        $this->assertEquals(0, count($qb->getDQLPart('orderBy')));
    }

    public function testResetDQLParts()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.username = ?1')->orderBy('u.username');

        $qb->resetDQLParts(['where', 'orderBy']);

        $this->assertEquals(1, count($qb->getDQLPart('select')));
        $this->assertNull($qb->getDQLPart('where'));
        $this->assertEquals(0, count($qb->getDQLPart('orderBy')));
    }

    public function testResetAllDQLParts()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where('u.username = ?1')->orderBy('u.username');

        $qb->resetDQLParts();

        $this->assertEquals(0, count($qb->getDQLPart('select')));
        $this->assertNull($qb->getDQLPart('where'));
        $this->assertEquals(0, count($qb->getDQLPart('orderBy')));
    }

    /**
     * @group DDC-867
     */
    public function testDeepClone()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->andWhere('u.username = ?1')
            ->andWhere('u.status = ?2');

        $expr = $qb->getDQLPart('where');
        $this->assertEquals(2, $expr->count(), "Modifying the second query should affect the first one.");

        $qb2 = clone $qb;
        $qb2->andWhere('u.name = ?3');

        $this->assertEquals(2, $expr->count(), "Modifying the second query should affect the first one.");
    }

    /**
     * @group DDC-3108
     */
    public function testAddCriteriaWhereWithJoinAlias()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('alias1')->from(CmsUser::class, 'alias1');
        $qb->join('alias1.articles','alias2');

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('field', 'value1'));
        $criteria->andWhere($criteria->expr()->gt('alias2.field', 'value2'));

        $qb->addCriteria($criteria);

        $this->assertEquals('alias1.field = :field AND alias2.field > :alias2_field', (string) $qb->getDQLPart('where'));
        $this->assertSame('value1', $qb->getParameter('field')->getValue());
        $this->assertSame('value2', $qb->getParameter('alias2_field')->getValue());
    }

    /**
     * @group DDC-3108
     */
    public function testAddCriteriaWhereWithDefaultAndJoinAlias()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('alias1')->from(CmsUser::class, 'alias1');
        $qb->join('alias1.articles','alias2');

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('alias1.field', 'value1'));
        $criteria->andWhere($criteria->expr()->gt('alias2.field', 'value2'));

        $qb->addCriteria($criteria);

        $this->assertEquals('alias1.field = :alias1_field AND alias2.field > :alias2_field', (string) $qb->getDQLPart('where'));
        $this->assertSame('value1', $qb->getParameter('alias1_field')->getValue());
        $this->assertSame('value2', $qb->getParameter('alias2_field')->getValue());
    }

    /**
     * @group DDC-3108
     */
    public function testAddCriteriaWhereOnJoinAliasWithDuplicateFields()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('alias1')->from(CmsUser::class, 'alias1');
        $qb->join('alias1.articles','alias2');

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('alias1.field', 'value1'));
        $criteria->andWhere($criteria->expr()->gt('alias2.field', 'value2'));
        $criteria->andWhere($criteria->expr()->lt('alias2.field', 'value3'));

        $qb->addCriteria($criteria);

        $this->assertEquals('(alias1.field = :alias1_field AND alias2.field > :alias2_field) AND alias2.field < :alias2_field_2', (string) $qb->getDQLPart('where'));
        $this->assertSame('value1', $qb->getParameter('alias1_field')->getValue());
        $this->assertSame('value2', $qb->getParameter('alias2_field')->getValue());
        $this->assertSame('value3', $qb->getParameter('alias2_field_2')->getValue());
    }


    /**
     * @group DDC-1933
     */
    public function testParametersAreCloned()
    {
        $originalQb = new QueryBuilder($this->_em);

        $originalQb->setParameter('parameter1', 'value1');

        $copy = clone $originalQb;
        $copy->setParameter('parameter2', 'value2');

        $this->assertCount(1, $originalQb->getParameters());
        $this->assertSame('value1', $copy->getParameter('parameter1')->getValue());
        $this->assertSame('value2', $copy->getParameter('parameter2')->getValue());
    }

    public function testGetRootAlias()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u');

        $this->assertEquals('u', $qb->getRootAlias());
    }

    public function testGetRootAliases()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u');

        $this->assertEquals(['u'], $qb->getRootAliases());
    }

    public function testGetRootEntities()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u');

        $this->assertEquals([CmsUser::class], $qb->getRootEntities());
    }

    public function testGetSeveralRootAliases()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->from(CmsUser::class, 'u2');

        $this->assertEquals(['u', 'u2'], $qb->getRootAliases());
        $this->assertEquals('u', $qb->getRootAlias());
    }

    public function testBCAddJoinWithoutRootAlias()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->add('join', ['INNER JOIN u.groups g'], true);

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.groups g', $qb->getDQL());
    }

    /**
     * @group DDC-1211
     */
    public function testEmptyStringLiteral()
    {
        $expr = $this->_em->getExpressionBuilder();
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where($expr->eq('u.username', $expr->literal("")));

        $this->assertEquals("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = ''", $qb->getDQL());
    }

    /**
     * @group DDC-1211
     */
    public function testEmptyNumericLiteral()
    {
        $expr = $this->_em->getExpressionBuilder();
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->where($expr->eq('u.username', $expr->literal(0)));

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = 0', $qb->getDQL());
    }

    /**
     * @group DDC-1227
     */
    public function testAddFromString()
    {
        $qb = $this->_em->createQueryBuilder()
            ->add('select', 'u')
            ->add('from', CmsUser::class . ' u');

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $qb->getDQL());
    }

    /**
     * @group DDC-1619
     */
    public function testAddDistinct()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->distinct()
            ->from(CmsUser::class, 'u');

        $this->assertEquals('SELECT DISTINCT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $qb->getDQL());
    }

    /**
     * @group DDC-2192
     */
    public function testWhereAppend()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Using \$append = true does not have an effect with 'where' or 'having' parts. See QueryBuilder#andWhere() for an example for correct usage.");

        $qb = $this->_em->createQueryBuilder()
            ->add('where', 'u.foo = ?1')
            ->add('where', 'u.bar = ?2', true)
        ;
    }

    public function testSecondLevelCacheQueryBuilderOptions()
    {
        $defaultQueryBuilder = $this->_em->createQueryBuilder()
            ->select('s')
            ->from(State::class, 's');

        $this->assertFalse($defaultQueryBuilder->isCacheable());
        $this->assertEquals(0, $defaultQueryBuilder->getLifetime());
        $this->assertNull($defaultQueryBuilder->getCacheRegion());
        $this->assertNull($defaultQueryBuilder->getCacheMode());

        $defaultQuery = $defaultQueryBuilder->getQuery();

        $this->assertFalse($defaultQuery->isCacheable());
        $this->assertEquals(0, $defaultQuery->getLifetime());
        $this->assertNull($defaultQuery->getCacheRegion());
        $this->assertNull($defaultQuery->getCacheMode());

        $builder = $this->_em->createQueryBuilder()
            ->select('s')
            ->setLifetime(123)
            ->setCacheable(true)
            ->setCacheRegion('foo_reg')
            ->setCacheMode(Cache::MODE_REFRESH)
            ->from(State::class, 's');

        $this->assertTrue($builder->isCacheable());
        $this->assertEquals(123, $builder->getLifetime());
        $this->assertEquals('foo_reg', $builder->getCacheRegion());
        $this->assertEquals(Cache::MODE_REFRESH, $builder->getCacheMode());

        $query = $builder->getQuery();

        $this->assertTrue($query->isCacheable());
        $this->assertEquals(123, $query->getLifetime());
        $this->assertEquals('foo_reg', $query->getCacheRegion());
        $this->assertEquals(Cache::MODE_REFRESH, $query->getCacheMode());
    }

    /**
     * @group DDC-2253
     */
    public function testRebuildsFromParts()
    {
        $qb = $this->_em->createQueryBuilder()
          ->select('u')
          ->from(CmsUser::class, 'u')
          ->join('u.article', 'a');

        $dqlParts = $qb->getDQLParts();
        $dql = $qb->getDQL();

        $qb2 = $this->_em->createQueryBuilder();
        foreach (array_filter($dqlParts) as $name => $part) {
            $qb2->add($name, $part);
        }
        $dql2 = $qb2->getDQL();

        $this->assertEquals($dql, $dql2);
    }

    public function testGetAllAliasesWithNoJoins()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')->from(CmsUser::class, 'u');

        $aliases = $qb->getAllAliases();

        $this->assertEquals(['u'], $aliases);
    }

    public function testGetAllAliasesWithJoins()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(CmsUser::class, 'u')
            ->join('u.groups', 'g');

        $aliases = $qb->getAllAliases();

        $this->assertEquals(['u', 'g'], $aliases);
    }

    /**
     * @group 6699
     */
    public function testGetParameterTypeJuggling() : void
    {
        $builder = $this->_em->createQueryBuilder()
                             ->select('u')
                             ->from(CmsUser::class, 'u')
                             ->where('u.id = ?0');

        $builder->setParameter(0, 0);

        self::assertCount(1, $builder->getParameters());
        self::assertSame(0, $builder->getParameter(0)->getValue());
        self::assertSame(0, $builder->getParameter('0')->getValue());
    }

    /**
     * @group 6699
     */
    public function testSetParameterWithNameZeroIsNotOverridden() : void
    {
        $builder = $this->_em->createQueryBuilder()
                             ->select('u')
                             ->from(CmsUser::class, 'u')
                             ->where('u.id != ?0')
                             ->andWhere('u.username = :name');

        $builder->setParameter(0, 0);
        $builder->setParameter('name', 'Doctrine');

        self::assertCount(2, $builder->getParameters());
        self::assertSame(0, $builder->getParameter('0')->getValue());
        self::assertSame('Doctrine', $builder->getParameter('name')->getValue());
    }

    /**
     * @group 6699
     */
    public function testSetParameterWithNameZeroDoesNotOverrideAnotherParameter() : void
    {
        $builder = $this->_em->createQueryBuilder()
                             ->select('u')
                             ->from(CmsUser::class, 'u')
                             ->where('u.id != ?0')
                             ->andWhere('u.username = :name');

        $builder->setParameter('name', 'Doctrine');
        $builder->setParameter(0, 0);

        self::assertCount(2, $builder->getParameters());
        self::assertSame(0, $builder->getParameter(0)->getValue());
        self::assertSame('Doctrine', $builder->getParameter('name')->getValue());
    }

    /**
     * @group 6699
     */
    public function testSetParameterWithTypeJugglingWorks() : void
    {
        $builder = $this->_em->createQueryBuilder()
                             ->select('u')
                             ->from(CmsUser::class, 'u')
                             ->where('u.id != ?0')
                             ->andWhere('u.username = :name');

        $builder->setParameter('0', 1);
        $builder->setParameter('name', 'Doctrine');
        $builder->setParameter(0, 2);
        $builder->setParameter('0', 3);

        self::assertCount(2, $builder->getParameters());
        self::assertSame(3, $builder->getParameter(0)->getValue());
        self::assertSame(3, $builder->getParameter('0')->getValue());
        self::assertSame('Doctrine', $builder->getParameter('name')->getValue());
    }
}
