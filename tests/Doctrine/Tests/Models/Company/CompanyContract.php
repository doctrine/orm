<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @Entity
 * @Table(name="company_contracts")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @EntityListeners({"CompanyContractListener"})
 * @DiscriminatorMap({
 *     "fix"       = "CompanyFixContract",
 *     "flexible"  = "CompanyFlexContract",
 *     "flexultra" = "CompanyFlexUltraContract"
 * })
 * @NamedNativeQueries({
 *      @NamedNativeQuery(
 *          name           = "all-contracts",
 *          resultClass    = "__CLASS__",
 *          query          = "SELECT id, completed, discr FROM company_contracts"
 *      ),
 *      @NamedNativeQuery(
 *          name           = "all",
 *          resultClass    = "__CLASS__",
 *          query          = "SELECT id, completed, discr FROM company_contracts"
 *      ),
 * })
 * @SqlResultSetMappings({
 *      @SqlResultSetMapping(
 *          name    = "mapping-all-contracts",
 *          entities= {
 *              @EntityResult(
 *                  entityClass         = "__CLASS__",
 *                  discriminatorColumn = "discr",
 *                  fields              = {
 *                      @FieldResult("id"),
 *                      @FieldResult("completed"),
 *                  }
 *              )
 *          }
 *      ),
 *      @SqlResultSetMapping(
 *          name    = "mapping-all",
 *          entities= {
 *              @EntityResult(
 *                  entityClass         = "__CLASS__",
 *                  discriminatorColumn = "discr",
 *                  fields              = {
 *                      @FieldResult("id"),
 *                      @FieldResult("completed"),
 *                  }
 *              )
 *          }
 *      ),
 * })
 */
abstract class CompanyContract
{
    /** @Id @column(type="integer") @GeneratedValue */
    private $id;

    /** @ManyToOne(targetEntity="CompanyEmployee", inversedBy="soldContracts") */
    private $salesPerson;

    /**
     * @Column(type="boolean")
     * @var bool
     */
    private $completed = false;

    /**
     * @ManyToMany(targetEntity="CompanyEmployee", inversedBy="contracts")
     * @JoinTable(name="company_contract_employees",
     *    joinColumns={@JoinColumn(name="contract_id", referencedColumnName="id", onDelete="CASCADE")},
     *    inverseJoinColumns={@JoinColumn(name="employee_id", referencedColumnName="id")}
     * )
     */
    private $engineers;

    public function __construct()
    {
        $this->engineers = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function markCompleted(): void
    {
        $this->completed = true;
    }

    public function isCompleted()
    {
        return $this->completed;
    }

    public function getSalesPerson()
    {
        return $this->salesPerson;
    }

    public function setSalesPerson(CompanyEmployee $salesPerson): void
    {
        $this->salesPerson = $salesPerson;
    }

    public function getEngineers()
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

    public static function loadMetadata(ClassMetadataInfo $metadata): void
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_JOINED);
        $metadata->setTableName('company_contracts');
        $metadata->setDiscriminatorColumn(
            [
                'name' => 'discr',
                'type' => 'string',
            ]
        );

        $metadata->mapField(
            [
                'id'        => true,
                'name'      => 'id',
                'fieldName' => 'id',
            ]
        );

        $metadata->mapField(
            [
                'type'      => 'boolean',
                'name'      => 'completed',
                'fieldName' => 'completed',
            ]
        );

        $metadata->setDiscriminatorMap(
            [
                'fix'       => 'CompanyFixContract',
                'flexible'  => 'CompanyFlexContract',
                'flexultra' => 'CompanyFlexUltraContract',
            ]
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
