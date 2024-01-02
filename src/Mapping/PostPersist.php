<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class PostPersist implements MappingAttribute
{
}
