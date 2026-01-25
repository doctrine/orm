<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\QueryFactory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;

interface RuleSetInterface
{
    public function getEntityClass(): string;

    /**
     * @return array<Base|Join>
     */
    public function getRules(): array;
    public function getRootAlias(): string;

    /**
     * @return ArrayCollection<int, Parameter>
     */
    public function getParameters(): ArrayCollection;
}