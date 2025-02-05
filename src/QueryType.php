<?php

declare(strict_types=1);

namespace Doctrine\ORM;

enum QueryType
{
    case Select;
    case Delete;
    case Update;
}
