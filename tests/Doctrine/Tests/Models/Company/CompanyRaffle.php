<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

/** @Entity @Table(name="company_raffles") */
class CompanyRaffle extends CompanyEvent
{
    /** @Column */
    private $data;

    public function setData($data): void
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
