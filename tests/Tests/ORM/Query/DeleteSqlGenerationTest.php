<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test case for testing the saving and referencing of query identifiers.
 *
 * @link        http://www.phpdoctrine.org
 *
 * @todo        1) [romanb] We  might want to split the SQL generation tests into multiple
 *              testcases later since we'll have a lot of them and we might want to have special SQL
 *              generation tests for some dbms specific SQL syntaxes.
 */
class DeleteSqlGenerationTest extends OrmTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->getTestEntityManager();
    }

    public function assertSqlGeneration(string $dqlToBeTested, string $sqlToBeConfirmed): void
    {
        $query = $this->entityManager->createQuery($dqlToBeTested);

        parent::assertEquals($sqlToBeConfirmed, $query->getSql());

        $query->free();
    }

    #[Group('6939')]
    public function testSupportsDeleteWithoutWhereAndAlias(): void
    {
        $this->assertSqlGeneration(
            'DELETE FROM Doctrine\Tests\Models\CMS\CmsUser',
            'DELETE FROM cms_users',
        );
    }

    public function testSupportsDeleteWithoutWhereAndFrom(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u',
            'DELETE FROM cms_users',
        );
    }

    public function testSupportsDeleteWithoutWhere(): void
    {
        $this->assertSqlGeneration(
            'DELETE FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'DELETE FROM cms_users',
        );
    }

    public function testSupportsWhereClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1',
            'DELETE FROM cms_users WHERE id = ?',
        );
    }

    public function testSupportsWhereOrExpressions(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = ?1 OR u.name = ?2',
            'DELETE FROM cms_users WHERE username = ? OR name = ?',
        );
    }

    public function testSupportsWhereNestedConditionalExpressions(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1 OR ( u.username = ?2 OR u.name = ?3)',
            'DELETE FROM cms_users WHERE id = ? OR (username = ? OR name = ?)',
        );

        //$this->assertSqlGeneration(
        //    'DELETE FROM Doctrine\Tests\Models\CMS\CmsUser WHERE id = ?1',
        //    'DELETE FROM cms_users WHERE id = ?'
        //);
    }

    public function testIsCaseAgnostic(): void
    {
        $this->assertSqlGeneration(
            'delete from Doctrine\Tests\Models\CMS\CmsUser u where u.username = ?1',
            'DELETE FROM cms_users WHERE username = ?',
        );
    }

    public function testSupportsAndCondition(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = ?1 AND u.name = ?2',
            'DELETE FROM cms_users WHERE username = ? AND name = ?',
        );
    }

    public function testSupportsWhereNot(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT u.id != ?1',
            'DELETE FROM cms_users WHERE NOT id <> ?',
        );
    }

    public function testSupportsWhereNotWithParentheses(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT ( u.id != ?1 )',
            'DELETE FROM cms_users WHERE NOT (id <> ?)',
        );
    }

    public function testSupportsWhereNotWithAndExpression(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT ( u.id != ?1 AND u.username = ?2 )',
            'DELETE FROM cms_users WHERE NOT (id <> ? AND username = ?)',
        );
    }

    // ConditionalPrimary was already tested (see testSupportsWhereClause() and testSupportsWhereNot())

    public function testSupportsGreaterThanComparisonClause(): void
    {
        // id = ? was already tested (see testDeleteWithWhere())
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > ?1',
            'DELETE FROM cms_users WHERE id > ?',
        );
    }

    public function testSupportsGreaterThanOrEqualToComparisonClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id >= ?1',
            'DELETE FROM cms_users WHERE id >= ?',
        );
    }

    public function testSupportsLessThanComparisonClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id < ?1',
            'DELETE FROM cms_users WHERE id < ?',
        );
    }

    public function testSupportsLessThanOrEqualToComparisonClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id <= ?1',
            'DELETE FROM cms_users WHERE id <= ?',
        );
    }

    public function testSupportsNotEqualToComparisonClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id <> ?1',
            'DELETE FROM cms_users WHERE id <> ?',
        );
    }

    public function testSupportsNotEqualToComparisonClauseExpressedWithExclamationMark(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id != ?1',
            'DELETE FROM cms_users WHERE id <> ?',
        );
    }

    public function testSupportsNotBetweenClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT BETWEEN ?1 AND ?2',
            'DELETE FROM cms_users WHERE id NOT BETWEEN ? AND ?',
        );
    }

    public function testSupportsBetweenClauseUsedWithAndClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id BETWEEN ?1 AND ?2 AND u.username != ?3',
            'DELETE FROM cms_users WHERE id BETWEEN ? AND ? AND username <> ?',
        );
    }

    public function testSupportsNotLikeClause(): void
    {
        // "WHERE" Expression LikeExpression
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username NOT LIKE ?1',
            'DELETE FROM cms_users WHERE username NOT LIKE ?',
        );
    }

    public function testSupportsLikeClauseWithEscapeExpression(): void
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username LIKE ?1 ESCAPE '\\'",
            "DELETE FROM cms_users WHERE username LIKE ? ESCAPE '\\'",
        );
    }

    public function testSupportsIsNullClause(): void
    {
        // "WHERE" Expression NullComparisonExpression
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IS NULL',
            'DELETE FROM cms_users WHERE name IS NULL',
        );
    }

    public function testSupportsIsNotNullClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IS NOT NULL',
            'DELETE FROM cms_users WHERE name IS NOT NULL',
        );
    }

    public function testSupportsAtomExpressionAsClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE 1 = 1',
            'DELETE FROM cms_users WHERE 1 = 1',
        );
    }

    public function testSupportsParameterizedAtomExpression(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE ?1 = 1',
            'DELETE FROM cms_users WHERE ? = 1',
        );
    }

    public function testSupportsInClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN ( ?1, ?2, ?3, ?4 )',
            'DELETE FROM cms_users WHERE id IN (?, ?, ?, ?)',
        );
    }

    public function testSupportsNotInClause(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN ( ?1, ?2 )',
            'DELETE FROM cms_users WHERE id NOT IN (?, ?)',
        );
    }

    #[Group('DDC-980')]
    public function testSubselectTableAliasReferencing(): void
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE SIZE(u.groups) = 10',
            'DELETE FROM cms_users WHERE (SELECT COUNT(*) FROM cms_users_groups c0_ WHERE c0_.user_id = cms_users.id) = 10',
        );
    }
}
