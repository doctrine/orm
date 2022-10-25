<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table('cache_client_address')]
#[Entity]
class Address
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var Person */
    #[JoinColumn(name: 'person_id', referencedColumnName: 'id')]
    #[OneToOne(targetEntity: 'Person', inversedBy: 'address')]
    public $person;

    public function __construct(
        #[Column]
        public string $location,
    ) {
    }
}
