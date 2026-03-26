<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH12225;

use Doctrine\ORM\Mapping\Entity;

/**
 * @Entity
 */
#[Entity]
class ConcreteDirectory extends AbstractDirectory
{
}
