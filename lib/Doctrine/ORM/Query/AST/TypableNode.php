<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

interface TypableNode
{
    public function getReturnType() : string;
}
