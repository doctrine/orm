<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for custom AST walking and adding new joins.
 *
 * @author      Lukasz Cybula <lukasz.cybula@fsi.pl>
 * @license     MIT
 * @link        http://www.doctrine-project.org
 */
class CustomTreeWalkersJoinTest extends OrmTestCase
{
    private $em;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
    }

    public function assertSqlGeneration($dqlToBeTested, $sqlToBeConfirmed)
    {
        try {
            $query = $this->em->createQuery($dqlToBeTested);
            $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CustomTreeWalkerJoin::class])
                  ->useQueryCache(false);

            $sqlGenerated = $query->getSql();

            $query->free();
        } catch (\Exception $e) {
            $this->fail($e->getMessage() . ' at "' . $e->getFile() . '" on line ' . $e->getLine());
        }

        self::assertEquals($sqlToBeConfirmed, $sqlGenerated);
    }

    public function testAddsJoin()
    {
        self::assertSqlGeneration(
            'select u from Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT t0."id" AS c0, t0."status" AS c1, t0."username" AS c2, t0."name" AS c3, t1."id" AS c4, t1."country" AS c5, t1."zip" AS c6, t1."city" AS c7, t0."email_id" AS c8, t1."user_id" AS c9 FROM "cms_users" t0 LEFT JOIN "cms_addresses" t1 ON t0."id" = t1."user_id"'
        );
    }

    public function testDoesNotAddJoin()
    {
        self::assertSqlGeneration(
            'select a from Doctrine\Tests\Models\CMS\CmsAddress a',
            'SELECT t0."id" AS c0, t0."country" AS c1, t0."zip" AS c2, t0."city" AS c3, t0."user_id" AS c4 FROM "cms_addresses" t0'
        );
    }
}

class CustomTreeWalkerJoin extends Query\TreeWalkerAdapter
{
    public function walkSelectStatement(Query\AST\SelectStatement $selectStatement)
    {
        foreach ($selectStatement->fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $rangeVariableDecl = $identificationVariableDeclaration->rangeVariableDeclaration;

            if ($rangeVariableDecl->abstractSchemaName !== CmsUser::class) {
                continue;
            }

            $this->modifySelectStatement($selectStatement, $identificationVariableDeclaration);
        }
    }

    private function modifySelectStatement(Query\AST\SelectStatement $selectStatement, $identificationVariableDecl)
    {
        $rangeVariableDecl       = $identificationVariableDecl->rangeVariableDeclaration;
        $joinAssocPathExpression = new Query\AST\JoinAssociationPathExpression($rangeVariableDecl->aliasIdentificationVariable, 'address');
        $joinAssocDeclaration    = new Query\AST\JoinAssociationDeclaration($joinAssocPathExpression, $rangeVariableDecl->aliasIdentificationVariable . 'a', null);
        $join                    = new Query\AST\Join(Query\AST\Join::JOIN_TYPE_LEFT, $joinAssocDeclaration);
        $selectExpression        = new Query\AST\SelectExpression($rangeVariableDecl->aliasIdentificationVariable . 'a', null, false);

        $identificationVariableDecl->joins[]                = $join;
        $selectStatement->selectClause->selectExpressions[] = $selectExpression;

        $entityManager   = $this->getQuery()->getEntityManager();
        $userMetadata    = $entityManager->getClassMetadata(CmsUser::class);
        $addressMetadata = $entityManager->getClassMetadata(CmsAddress::class);

        $this->setQueryComponent($rangeVariableDecl->aliasIdentificationVariable . 'a',
            [
                'metadata'     => $addressMetadata,
                'parent'       => $rangeVariableDecl->aliasIdentificationVariable,
                'relation'     => $userMetadata->associationMappings['address'],
                'map'          => null,
                'nestingLevel' => 0,
                'token'        => null,
            ]
        );
    }
}
