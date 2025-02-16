<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;

#[ORM\Entity]
#[ORM\Table(name: 'cache_city')]
#[ORM\Cache]
class City
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    /** @var Collection<int, Travel> */
    #[ORM\ManyToMany(targetEntity: 'Travel', mappedBy: 'visitedCities')]
    public $travels;

    /** @phpstan-var Collection<int, Attraction> */
    #[ORM\Cache]
    #[ORM\OrderBy(['name' => 'ASC'])]
    #[ORM\OneToMany(targetEntity: 'Attraction', mappedBy: 'city')]
    public $attractions;

    public function __construct(
        #[ORM\Column(unique: true)]
        protected string $name,
        #[ORM\Cache]
        #[ORM\ManyToOne(targetEntity: 'State', inversedBy: 'cities')]
        #[ORM\JoinColumn(name: 'state_id', referencedColumnName: 'id')]
        protected State|null $state = null,
    ) {
        $this->travels     = new ArrayCollection();
        $this->attractions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getState(): State|null
    {
        return $this->state;
    }

    public function setState(State $state): void
    {
        $this->state = $state;
    }

    public function addTravel(Travel $travel): void
    {
        $this->travels[] = $travel;
    }

    /** @phpstan-return Collection<int, Travel> */
    public function getTravels(): Collection
    {
        return $this->travels;
    }

    public function addAttraction(Attraction $attraction): void
    {
        $this->attractions[] = $attraction;
    }

    /** @phpstan-return Collection<int, Attraction> */
    public function getAttractions(): Collection
    {
        return $this->attractions;
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
        $metadata->setPrimaryTable(['name' => 'cache_city']);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

        $metadata->enableCache(
            [
                'usage' => ClassMetadata::CACHE_USAGE_READ_ONLY,
            ],
        );

        $metadata->mapField(
            [
                'fieldName' => 'id',
                'type' => 'integer',
                'id' => true,
            ],
        );

        $metadata->mapField(
            [
                'fieldName' => 'name',
                'type' => 'string',
            ],
        );

        $metadata->mapOneToOne(
            [
                'fieldName'      => 'state',
                'targetEntity'   => State::class,
                'inversedBy'     => 'cities',
                'joinColumns'    =>
                    [
                        [
                            'name' => 'state_id',
                            'referencedColumnName' => 'id',
                        ],
                    ],
            ],
        );
        $metadata->enableAssociationCache('state', [
            'usage' => ClassMetadata::CACHE_USAGE_READ_ONLY,
        ]);

        $metadata->mapManyToMany(
            [
                'fieldName' => 'travels',
                'targetEntity' => Travel::class,
                'mappedBy' => 'visitedCities',
            ],
        );

        $metadata->mapOneToMany(
            [
                'fieldName' => 'attractions',
                'targetEntity' => Attraction::class,
                'mappedBy' => 'city',
                'orderBy' => ['name' => 'ASC'],
            ],
        );
        $metadata->enableAssociationCache('attractions', [
            'usage' => ClassMetadata::CACHE_USAGE_READ_ONLY,
        ]);
    }
}
