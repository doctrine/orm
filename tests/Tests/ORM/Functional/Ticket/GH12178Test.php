<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
        use PHPUnit\Framework\Attributes\Group;

/**
 * Test cases for DQL expressions involving CASE, COALESCE, NULLIF, and arithmetic
 * especially when used with IN / NOT IN operators.
 */
#[Group('GH-12178')]
class GH12178Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    /**
     * CASE WHEN expression as left operand with IN operator
     */
    public function testCaseWhenWithInOperator(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . ' u 
                WHERE CASE 
                    WHEN u.id = 1 THEN 0 
                    WHEN u.id = 2 THEN 1 
                    ELSE 3 
                END IN (:values)';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('values', [0, 1]);

        $sql = $query->getSQL();
        self::assertNotEmpty($sql);
    }

    /**
     * Simple CASE WHEN with IN operator
     */
    public function testSimpleCaseWhenWithIn(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . ' u 
                WHERE CASE WHEN u.status = :status THEN u.id ELSE 0 END IN (:ids)';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('status', 'active');
        $query->setParameter('ids', [1, 2, 3]);

        $sql = $query->getSQL();
        self::assertNotEmpty($sql);
    }

    /**
     * CASE WHEN with NOT IN
     */
    public function testCaseWhenWithNotIn(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . ' u 
                WHERE CASE WHEN u.status = :status THEN 1 ELSE 0 END NOT IN (:values)';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('status', 'active');
        $query->setParameter('values', [0]);

        $sql = $query->getSQL();
        self::assertNotEmpty($sql);
    }

    /**
     * Nested CASE with IN
     */
    public function testNestedCaseWithIn(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . ' u 
                WHERE CASE 
                    WHEN u.id = 1 THEN 
                        CASE WHEN u.status = :status THEN 1 ELSE 2 END
                    ELSE 3 
                END IN (:values)';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('status', 'active');
        $query->setParameter('values', [1, 2, 3]);

        $sql = $query->getSQL();
        self::assertNotEmpty($sql);
    }

    /**
     * COALESCE with IN
     */
    public function testCoalesceWithIn(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . ' u 
                WHERE COALESCE(u.id, 0) IN (:ids)';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('ids', [1, 2, 3]);

        $sql = $query->getSQL();
        self::assertNotEmpty($sql);
    }

    /**
     * Arithmetic expression with IN
     */
    public function testArithmeticExpressionWithIn(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . ' u 
                WHERE (u.id + 1) IN (:ids)';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('ids', [1, 2, 3]);

        $sql = $query->getSQL();
        self::assertNotEmpty($sql);
    }

    /**
     * Parenthesized arithmetic expression with NOT IN (T_NOT handling)
     */
    public function testParenthesizedExpressionWithNotIn(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . ' u 
                WHERE (u.id + 1) NOT IN (:ids)';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('ids', [2, 3, 4]);

        $sql = $query->getSQL();
        self::assertNotEmpty($sql);
    }

    /**
     * NULLIF with IN operator
     */
    public function testNullIfWithIn(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . ' u 
                WHERE NULLIF(u.id, 0) IN (:ids)';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('ids', [1, 2]);

        $sql = $query->getSQL();
        self::assertNotEmpty($sql);
    }

    /**
     * Nested COALESCE with IN
     */
    public function testNestedCoalesceWithIn(): void
    {
        $dql = 'SELECT u FROM ' . CmsUser::class . ' u 
                WHERE COALESCE(u.id, COALESCE(u.status, 0)) IN (:ids)';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('ids', [0, 1, 2]);

        $sql = $query->getSQL();
        self::assertNotEmpty($sql);
    }
}
