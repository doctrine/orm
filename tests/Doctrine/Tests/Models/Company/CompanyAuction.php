<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="company_auctions")
 */
class CompanyAuction extends CompanyEvent
{
    /**
     * @ORM\Column(type="string")
     */
    private $data;

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }
}
