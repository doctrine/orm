<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CollectionWithInheritance;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;

/** @Entity */
class Dog extends Pet
{
    /**
     * @ORM\ManyToOne(targetEntity="PetStore", inversedBy="dogs")
     *
     * @var PetStore
     */
    private $petStore;

    public function __construct(PetStore $petStore)
    {
        $this->petStore = $petStore;
    }
}
