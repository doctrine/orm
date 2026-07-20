<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Encrypt;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Encrypt\EncryptQuery;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Encrypt;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'encrypt_customers')]
#[Entity]
class EncryptCustomer
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public int|null $id = null;

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'string', length: 255)]
    #[Encrypt(cipher: 'deterministic', queryType: EncryptQuery::Equality)]
    public string $ssn;

    #[Column(type: 'string', length: 255)]
    #[Encrypt]
    public string $note;

    /** @var Collection<int, EncryptGroup> */
    #[ManyToMany(targetEntity: EncryptGroup::class, fetch: 'EXTRA_LAZY')]
    #[JoinTable(name: 'encrypt_customers_groups')]
    public Collection $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
}
