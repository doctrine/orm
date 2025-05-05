<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity;

/**
 * @ORM\Entity()
 */
class PrimaryPatInsurance extends Entity\PatientInsurance
{
}
