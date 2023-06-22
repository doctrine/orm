<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH9378;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *      name="doctrine_person",
 * )
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 * 		"employee" = "Doctrine\Tests\Models\GH9378\GH9378Employee",
 * })
 */
abstract class GH9378Person
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    public string $firstName = '';

    /**
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    public string $lastName = '';

}
