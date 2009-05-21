<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="company_managers")
 * @DoctrineDiscriminatorValue("manager")
 */
class CompanyManager extends CompanyEmployee
{
    /**
     * @DoctrineColumn(type="string", length="250")
     */
    private $title;

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }
}