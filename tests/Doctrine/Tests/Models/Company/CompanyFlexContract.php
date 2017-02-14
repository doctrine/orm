<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 *
 * @ORM\NamedNativeQueries({
 *      @ORM\NamedNativeQuery(
 *          name           = "all",
 *          resultClass    = "__CLASS__",
 *          query          = "SELECT id, hoursWorked, discr FROM company_contracts"
 *      ),
 *      @ORM\NamedNativeQuery(
 *          name           = "all-flex",
 *          resultClass    = "CompanyFlexContract",
 *          query          = "SELECT id, hoursWorked, discr FROM company_contracts"
 *      ),
 * })
 *
 * @ORM\SqlResultSetMappings({
 *      @ORM\SqlResultSetMapping(
 *          name    = "mapping-all-flex",
 *          entities= {
 *              @ORM\EntityResult(
 *                  entityClass         = "__CLASS__",
 *                  discriminatorColumn = "discr",
 *                  fields              = {
 *                      @ORM\FieldResult("id"),
 *                      @ORM\FieldResult("hoursWorked"),
 *                  }
 *              )
 *          }
 *      ),
 *      @ORM\SqlResultSetMapping(
 *          name    = "mapping-all",
 *          entities= {
 *              @ORM\EntityResult(
 *                  entityClass         = "CompanyFlexContract",
 *                  discriminatorColumn = "discr",
 *                  fields              = {
 *                      @ORM\FieldResult("id"),
 *                      @ORM\FieldResult("hoursWorked"),
 *                  }
 *              )
 *          }
 *      ),
 * })
 */
class CompanyFlexContract extends CompanyContract
{
    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $hoursWorked = 0;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $pricePerHour = 0;

    /**
     * @ORM\ManyToMany(targetEntity="CompanyManager", inversedBy="managedContracts", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="company_contract_managers",
     *    joinColumns={@ORM\JoinColumn(name="contract_id", referencedColumnName="id", onDelete="CASCADE")},
     *    inverseJoinColumns={@ORM\JoinColumn(name="employee_id", referencedColumnName="id")}
     * )
     */
    public $managers;

    public function calculatePrice()
    {
        return $this->hoursWorked * $this->pricePerHour;
    }

    public function getHoursWorked()
    {
        return $this->hoursWorked;
    }

    public function setHoursWorked($hoursWorked)
    {
        $this->hoursWorked = $hoursWorked;
    }

    public function getPricePerHour()
    {
        return $this->pricePerHour;
    }

    public function setPricePerHour($pricePerHour)
    {
        $this->pricePerHour = $pricePerHour;
    }
    public function getManagers()
    {
        return $this->managers;
    }

    public function addManager(CompanyManager $manager)
    {
        $this->managers[] = $manager;
    }

    public function removeManager(CompanyManager $manager)
    {
        $this->managers->removeElement($manager);
    }

    static public function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('hoursWorked');
        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setColumnName('hoursWorked');

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('pricePerHour');
        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setColumnName('pricePerHour');

        $metadata->addProperty($fieldMetadata);
    }
}
