<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Encrypt;

use Doctrine\ORM\Encrypt\EncryptQuery;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Encrypt;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'encrypt_groups')]
#[Entity]
class EncryptGroup
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public int|null $id = null;

    #[Column(type: 'string', length: 255)]
    #[Encrypt(cipher: 'deterministic', queryType: EncryptQuery::Equality)]
    public string $name;

    #[Column(type: 'string', length: 255, nullable: true)]
    #[Encrypt]
    public string|null $motto = null;
}
