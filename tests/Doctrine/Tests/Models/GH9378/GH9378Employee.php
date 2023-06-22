<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH9378;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="doctrine_employee")
 */
final class GH9378Employee extends GH9378Person
{
    /**
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    public string $employeeId = '';
    
    /**
     * @ORM\Version()
     * @ORM\Column(type="integer")
     */
    private int $version;
}
