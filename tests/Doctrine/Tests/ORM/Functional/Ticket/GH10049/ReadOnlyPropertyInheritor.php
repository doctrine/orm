<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10049;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ReadOnlyPropertyInheritor extends ReadOnlyPropertyOwner
{
}
