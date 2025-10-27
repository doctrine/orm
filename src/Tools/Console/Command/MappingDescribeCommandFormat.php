<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

enum MappingDescribeCommandFormat: string
{
    case TEXT = 'text';
    case JSON = 'json';
}
