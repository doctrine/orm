<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'cms_phonenumbers')]
#[Entity]
class CmsPhonenumber
{
    /** @var string */
    #[Id]
    #[Column(length: 50)]
    public $phonenumber;

    /** @var CmsUser */
    #[ManyToOne(targetEntity: 'CmsUser', inversedBy: 'phonenumbers', cascade: [])]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    public $user;

    public function setUser(CmsUser $user): void
    {
        $this->user = $user;
    }

    public function getUser(): CmsUser|null
    {
        return $this->user;
    }
}
