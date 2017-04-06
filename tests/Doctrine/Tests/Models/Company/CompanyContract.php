<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 * @ORM\Table(name="company_contracts")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\EntityListeners({"CompanyContractListener"})
 * @ORM\DiscriminatorMap({
 *     "fix"       = "CompanyFixContract",
 *     "flexible"  = "CompanyFlexContract",
 *     "flexultra" = "CompanyFlexUltraContract"
 * })
 *
 * @ORM\NamedNativeQueries({
 *      @ORM\NamedNativeQuery(
 *          name           = "all-contracts",
 *          resultClass    = "__CLASS__",
 *          query          = "SELECT id, completed, discr FROM company_contracts"
 *      ),
 *      @ORM\NamedNativeQuery(
 *          name           = "all",
 *          resultClass    = "__CLASS__",
 *          query          = "SELECT id, completed, discr FROM company_contracts"
 *      ),
 * })
 *
 * @ORM\SqlResultSetMappings({
 *      @ORM\SqlResultSetMapping(
 *          name    = "mapping-all-contracts",
 *          entities= {
 *              @ORM\EntityResult(
 *                  entityClass         = "__CLASS__",
 *                  discriminatorColumn = "discr",
 *                  fields              = {
 *                      @ORM\FieldResult("id"),
 *                      @ORM\FieldResult("completed"),
 *                  }
 *              )
 *          }
 *      ),
 *      @ORM\SqlResultSetMapping(
 *          name    = "mapping-all",
 *          entities= {
 *              @ORM\EntityResult(
 *                  entityClass         = "__CLASS__",
 *                  discriminatorColumn = "discr",
 *                  fields              = {
 *                      @ORM\FieldResult("id"),
 *                      @ORM\FieldResult("completed"),
 *                  }
 *              )
 *          }
 *      ),
 * })
 */
abstract class CompanyContract
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="CompanyEmployee", inversedBy="soldContracts")
     */
    private $salesPerson;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $completed = false;

    /**
     * @ORM\ManyToMany(targetEntity="CompanyEmployee", inversedBy="contracts")
     * @ORM\JoinTable(name="company_contract_employees",
     *    joinColumns={@ORM\JoinColumn(name="contract_id", referencedColumnName="id", onDelete="CASCADE")},
     *    inverseJoinColumns={@ORM\JoinColumn(name="employee_id", referencedColumnName="id")}
     * )
     */
    private $engineers;

    public function __construct()
    {
        $this->engineers = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function markCompleted()
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

    public function setSalesPerson(CompanyEmployee $salesPerson)
    {
        $this->salesPerson = $salesPerson;
    }

    public function getEngineers()
    {
        return $this->engineers;
    }

    public function addEngineer(CompanyEmployee $engineer)
    {
        $this->engineers[] = $engineer;
    }

    public function removeEngineer(CompanyEmployee $engineer)
    {
        $this->engineers->removeElement($engineer);
    }

    abstract public function calculatePrice();

    static public function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $tableMetadata = new Mapping\TableMetadata();
        $tableMetadata->setName('company_contracts');

        $metadata->setTable($tableMetadata);
        $metadata->setInheritanceType(Mapping\InheritanceType::JOINED);

        $discrColumn = new Mapping\DiscriminatorColumnMetadata();

        $discrColumn->setTableName($metadata->getTableName());
        $discrColumn->setColumnName('discr');
        $discrColumn->setType(Type::getType('string'));

        $metadata->setDiscriminatorColumn($discrColumn);

        $fieldMetadata = new Mapping\FieldMetadata('id');
        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('completed');
        $fieldMetadata->setType(Type::getType('boolean'));
        $fieldMetadata->setColumnName('completed');

        $metadata->addProperty($fieldMetadata);

        $metadata->setDiscriminatorMap(
            [
                "fix"       => "CompanyFixContract",
                "flexible"  => "CompanyFlexContract",
                "flexultra" => "CompanyFlexUltraContract"
            ]
        );

        $metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, 'CompanyContractListener', 'postPersistHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'CompanyContractListener', 'prePersistHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, 'CompanyContractListener', 'postUpdateHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, 'CompanyContractListener', 'preUpdateHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, 'CompanyContractListener', 'postRemoveHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, 'CompanyContractListener', 'preRemoveHandler');

        $metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, 'CompanyContractListener', 'preFlushHandler');
        $metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, 'CompanyContractListener', 'postLoadHandler');
    }
}
