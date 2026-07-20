<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Encrypt;

use Doctrine\ORM\Encrypt\EncryptQuery;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Encrypt;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'encrypt_persons')]
#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['person' => EncryptPerson::class, 'employee' => EncryptEmployee::class])]
class EncryptPerson
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public int|null $id = null;

    #[Column(type: 'string', length: 255)]
    #[Encrypt(cipher: 'deterministic', queryType: EncryptQuery::Equality)]
    public string $secret;
}
