<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

/** @Entity @Table(name="company_raffles") */
class CompanyRaffle extends CompanyEvent
{
    /**
     * @var string
     * @Column
     */
    private $data;

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
