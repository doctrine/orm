<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\QueryFactory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr as Expr;
use Doctrine\ORM\QueryBuilder;

class QueryFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function createQuery(RuleSetInterface $ruleSet): Query
    {
        return $this->createQueryBuilder($ruleSet)->getQuery();
    }

    public function createQueryBuilder(RuleSetInterface $ruleSet): QueryBuilder
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->from($ruleSet->getEntityClass(), $ruleSet->getRootAlias())
            ->select($ruleSet->getRootAlias());

        foreach ($ruleSet->getRules() as $key => $rule) {
            match($rule::class) {
                Expr\Andx::class, Expr\Orx::class => $qb->add('where', $rule),
                Expr\OrderBy::class => $qb->add('orderBy', $rule),
                Expr\GroupBy::class => $qb->add('groupBy', $rule),
                Expr\Join::class => $this->processJoins($qb, $rule),
                Expr\Select::class => $qb->add('select', $rule),
                default => throw new QueryFactoryUnexpectedValueException("Wrong rule on $key"),
            };
        }

        $qb->setParameters($ruleSet->getParameters());

        return $qb;
    }

    private function processJoins(QueryBuilder $qb, Expr\Join $join): void
    {
        $fn = strtolower($join->getJoinType()) . 'Join';
        $qb->$fn(
            $join->getJoin(),
            $join->getAlias(),
            $join->getConditionType(),
            $join->getCondition(),
            $join->getIndexBy()
        );
    }
}