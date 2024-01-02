<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC2372;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'admins')]
#[Entity]
class DDC2372Admin extends DDC2372User
{
}
