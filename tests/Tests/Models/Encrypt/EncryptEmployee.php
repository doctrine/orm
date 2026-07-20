<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Encrypt;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'encrypt_employees')]
#[Entity]
class EncryptEmployee extends EncryptPerson
{
    #[Column(type: 'string', length: 255)]
    public string $department;
}
