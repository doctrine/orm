<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @Entity
 * @Table(name="company_managers")
 * @DiscriminatorValue("manager")
 */
class CompanyManager extends CompanyEmployee
{
    /**
     * @Column(type="string", length="250")
     */
    private $title;

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }
}