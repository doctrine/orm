<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10288;

enum GH10288People: string
{
    case BOSS     = 'boss';
    case EMPLOYEE = 'employee';
}
