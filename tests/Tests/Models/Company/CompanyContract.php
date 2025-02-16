<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'company_contracts')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['fix' => 'CompanyFixContract', 'flexible' => 'CompanyFlexContract', 'flexultra' => 'CompanyFlexUltraContract'])]
#[ORM\EntityListeners(['CompanyContractListener'])]
abstract class CompanyContract
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ManyToOne(targetEntity: 'CompanyEmployee', inversedBy: 'soldContracts')]
    private CompanyEmployee|null $salesPerson = null;

    #[Column(type: 'boolean')]
    private bool $completed = false;

    /** @phpstan-var Collection<int, CompanyEmployee> */
    #[JoinTable(name: 'company_contract_employees')]
    #[JoinColumn(name: 'contract_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[InverseJoinColumn(name: 'employee_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'CompanyEmployee', inversedBy: 'contracts')]
    private $engineers;

    public function __construct()
    {
        $this->engineers = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function markCompleted(): void
    {
        $this->completed = true;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function getSalesPerson(): CompanyEmployee
    {
        return $this->salesPerson;
    }

    public function setSalesPerson(CompanyEmployee $salesPerson): void
    {
        $this->salesPerson = $salesPerson;
    }

    /** @phpstan-return Collection<int, CompanyEmployee> */
    public function getEngineers(): Collection
    {
        return $this->engineers;
    }

    public function addEngineer(CompanyEmployee $engineer): void
    {
        $this->engineers[] = $engineer;
    }

    public function removeEngineer(CompanyEmployee $engineer): void
    {
        $this->engineers->removeElement($engineer);
    }

    abstract public function calculatePrice(): int;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_JOINED);
        $metadata->setTableName('company_contracts');
        $metadata->setDiscriminatorColumn(
            [
                'name' => 'discr',
                'type' => 'string',
            ],
        );

        $metadata->mapField(
            [
                'id'        => true,
                'name'      => 'id',
                'fieldName' => 'id',
            ],
        );

        $metadata->mapField(
            [
                'type'      => 'boolean',
                'name'      => 'completed',
                'fieldName' => 'completed',
            ],
        );

        $metadata->setDiscriminatorMap(
            [
                'fix'       => 'CompanyFixContract',
                'flexible'  => 'CompanyFlexContract',
                'flexultra' => 'CompanyFlexUltraContract',
            ],
        );

        $metadata->addEntityListener(Events::postPersist, 'CompanyContractListener', 'postPersistHandler');
        $metadata->addEntityListener(Events::prePersist, 'CompanyContractListener', 'prePersistHandler');

        $metadata->addEntityListener(Events::postUpdate, 'CompanyContractListener', 'postUpdateHandler');
        $metadata->addEntityListener(Events::preUpdate, 'CompanyContractListener', 'preUpdateHandler');

        $metadata->addEntityListener(Events::postRemove, 'CompanyContractListener', 'postRemoveHandler');
        $metadata->addEntityListener(Events::preRemove, 'CompanyContractListener', 'preRemoveHandler');

        $metadata->addEntityListener(Events::preFlush, 'CompanyContractListener', 'preFlushHandler');
        $metadata->addEntityListener(Events::postLoad, 'CompanyContractListener', 'postLoadHandler');
    }
}
