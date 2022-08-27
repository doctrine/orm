<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

/** @internal To be used inside the QueryBuilder only. */
enum QueryType
{
    case Select;
    case Delete;
    case Update;
}
