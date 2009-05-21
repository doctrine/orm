<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\Tests\Models\Company;

/**
 * Description of CompanyPerson
 *
 * @author robo
 * @DoctrineEntity
 * @DoctrineTable(name="company_persons")
 * @DoctrineDiscriminatorValue("person")
 * @DoctrineInheritanceType("joined")
 * @DoctrineDiscriminatorColumn(name="discr", type="string")
 * @DoctrineSubClasses({"Doctrine\Tests\Models\Company\CompanyEmployee",
        "Doctrine\Tests\Models\Company\CompanyManager"})
 */
class CompanyPerson
{
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineGeneratedValue(strategy="auto")
     */
    private $id;
    /**
     * @DoctrineColumn(type="string")
     */
    private $name;
    /**
     * @DoctrineOneToOne(targetEntity="CompanyPerson")
     * @DoctrineJoinColumn(name="spouse_id", referencedColumnName="id")
     */
    private $spouse;

    public function getId() {
        return  $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getSpouse() {
        return $this->spouse;
    }

    public function setSpouse(CompanyPerson $spouse) {
        if ($spouse !== $this->spouse) {
            $this->spouse = $spouse;
            $this->spouse->setSpouse($this);
        }
    }
}

