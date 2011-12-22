<?php

namespace Doctrine\Tests\Models\Company;

/** @Entity @Table(name="company_auctions") */
class CompanyAuction extends CompanyEvent {
    /** @Column(type="string") */
    private $data;

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }
}
