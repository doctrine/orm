<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

enum QueryType
{
    case Select;
    case Delete;
    case Update;
}
