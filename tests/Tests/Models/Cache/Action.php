<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table('cache_action')]
#[Entity]
class Action
{
    /** @phpstan-var Collection<int, Token> */
    #[OneToMany(targetEntity: 'Token', cascade: ['persist', 'remove'], mappedBy: 'action')]
    public $tokens;

    public function __construct(
        #[Id]
        #[Column(type: 'string', length: 255)]
        #[GeneratedValue(strategy: 'NONE')]
        public string $name,
    ) {
        $this->tokens = new ArrayCollection();
    }

    public function addToken(Token $token): void
    {
        $this->tokens[] = $token;
        $token->action  = $this;
    }
}
