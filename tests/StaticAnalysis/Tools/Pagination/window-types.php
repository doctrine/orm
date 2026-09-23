<?php

declare(strict_types=1);

namespace Doctrine\StaticAnalysis\Tools\Pagination;

use Doctrine\ORM\Tools\Pagination\Window;

/** @param int<0, max> $offset */
function acceptsOffset(int $offset): void
{
}

/** @param int<1, max> $limit */
function acceptsLimit(int $limit): void
{
}

function test(Window $window): void
{
    acceptsOffset($window->getFirstResult());
    acceptsLimit($window->getMaxResults());
}
