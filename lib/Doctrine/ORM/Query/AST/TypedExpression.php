<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\DBAL\Types\Type;

interface TypedExpression
{
    public function getReturnType() : Type;
}
