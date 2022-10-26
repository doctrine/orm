<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table('cache_login')]
#[Entity]
class Login
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var Token */
    #[ManyToOne(targetEntity: 'Token', cascade: ['persist', 'remove'], inversedBy: 'logins')]
    #[JoinColumn(name: 'token_id', referencedColumnName: 'token')]
    public $token;

    public function __construct(
        #[Column]
        public string $name,
    ) {
    }

    public function getToken(): Token
    {
        return $this->token;
    }
}
