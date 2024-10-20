<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10334;

enum GH10334ProductTypeId: string
{
    case Jean  = 'jean';
    case Short = 'short';
}
