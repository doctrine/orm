<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC869;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\MappedSuperclass(repositoryClass=DDC869PaymentRepository::class)
 */
class DDC869Payment
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="float") */
    protected $value;
}
