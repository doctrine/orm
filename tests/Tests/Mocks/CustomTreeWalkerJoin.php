<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\JoinAssociationDeclaration;
use Doctrine\ORM\Query\AST\JoinAssociationPathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;

class CustomTreeWalkerJoin extends TreeWalkerAdapter
{
    public function walkSelectStatement(SelectStatement $selectStatement): void
    {
        foreach ($selectStatement->fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $rangeVariableDecl = $identificationVariableDeclaration->rangeVariableDeclaration;

            if ($rangeVariableDecl->abstractSchemaName !== CmsUser::class) {
                continue;
            }

            $this->modifySelectStatement($selectStatement, $identificationVariableDeclaration);
        }
    }

    private function modifySelectStatement(SelectStatement $selectStatement, IdentificationVariableDeclaration $identificationVariableDecl): void
    {
        $rangeVariableDecl       = $identificationVariableDecl->rangeVariableDeclaration;
        $joinAssocPathExpression = new JoinAssociationPathExpression($rangeVariableDecl->aliasIdentificationVariable, 'address');
        $joinAssocDeclaration    = new JoinAssociationDeclaration($joinAssocPathExpression, $rangeVariableDecl->aliasIdentificationVariable . 'a', null);
        $join                    = new Join(Join::JOIN_TYPE_LEFT, $joinAssocDeclaration);
        $selectExpression        = new SelectExpression($rangeVariableDecl->aliasIdentificationVariable . 'a', null, false);

        $identificationVariableDecl->joins[]                = $join;
        $selectStatement->selectClause->selectExpressions[] = $selectExpression;

        $entityManager   = $this->_getQuery()->getEntityManager();
        $userMetadata    = $entityManager->getClassMetadata(CmsUser::class);
        $addressMetadata = $entityManager->getClassMetadata(CmsAddress::class);

        $this->setQueryComponent(
            $rangeVariableDecl->aliasIdentificationVariable . 'a',
            [
                'metadata'     => $addressMetadata,
                'parent'       => $rangeVariableDecl->aliasIdentificationVariable,
                'relation'     => $userMetadata->getAssociationMapping('address'),
                'map'          => null,
                'nestingLevel' => 0,
                'token'        => null,
            ]
        );
    }
}
