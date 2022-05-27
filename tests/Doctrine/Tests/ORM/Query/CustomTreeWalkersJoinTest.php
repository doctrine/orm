<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\Tests\Mocks\CustomTreeWalkerJoin;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for custom AST walking and adding new joins.
 *
 * @link        http://www.doctrine-project.org
 */
class CustomTreeWalkersJoinTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
    }

    public function assertSqlGeneration(string $dqlToBeTested, string $sqlToBeConfirmed): void
    {
        $query = $this->em->createQuery($dqlToBeTested);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CustomTreeWalkerJoin::class])
              ->useQueryCache(false);

        self::assertEquals($sqlToBeConfirmed, $query->getSql());
        $query->free();
    }

    public function testAddsJoin(): void
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0_.id AS id_0, c0_.status AS status_1, c0_.username AS username_2, c0_.name AS name_3, c1_.id AS id_4, c1_.country AS country_5, c1_.zip AS zip_6, c1_.city AS city_7, c0_.email_id AS email_id_8, c1_.user_id AS user_id_9 FROM cms_users c0_ LEFT JOIN cms_addresses c1_ ON c0_.id = c1_.user_id'
        );
    }

    public function testDoesNotAddJoin(): void
    {
        $this->assertSqlGeneration(
            'select a from Doctrine\Tests\Models\CMS\CmsAddress a',
            'SELECT c0_.id AS id_0, c0_.country AS country_1, c0_.zip AS zip_2, c0_.city AS city_3, c0_.user_id AS user_id_4 FROM cms_addresses c0_'
        );
    }
}
