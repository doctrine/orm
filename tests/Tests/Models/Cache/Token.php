<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

use function date;
use function strtotime;

#[Table('cache_token')]
#[Entity]
#[Cache('READ_ONLY')]
class Token
{
    /** @var DateTime */
    #[Column(type: 'date')]
    public $expiresAt;

    /** @phpstan-var Collection<int, Login> */
    #[OneToMany(targetEntity: 'Login', cascade: ['persist', 'remove'], mappedBy: 'token')]
    public $logins;

    /** @var Action */
    #[ManyToOne(targetEntity: 'Action', cascade: ['persist', 'remove'], inversedBy: 'tokens')]
    #[JoinColumn(name: 'action_name', referencedColumnName: 'name')]
    public $action;

    /** @var ComplexAction */
    #[JoinColumn(name: 'complex_action1_name', referencedColumnName: 'action1_name')]
    #[JoinColumn(name: 'complex_action2_name', referencedColumnName: 'action2_name')]
    #[ManyToOne(targetEntity: 'ComplexAction', cascade: ['persist', 'remove'], inversedBy: 'tokens')]
    public $complexAction;

    public function __construct(
        #[Id]
        #[Column(type: 'string', length: 255)]
        public string $token,
        #[OneToOne(targetEntity: 'Client')]
        public Client|null $client = null,
    ) {
        $this->logins    = new ArrayCollection();
        $this->expiresAt = new DateTime(date('Y-m-d H:i:s', strtotime('+7 day')));
    }

    public function addLogin(Login $login): void
    {
        $this->logins[] = $login;
        $login->token   = $this;
    }

    public function getClient(): Client|null
    {
        return $this->client;
    }

    public function getAction(): Action
    {
        return $this->action;
    }

    public function getComplexAction(): ComplexAction
    {
        return $this->complexAction;
    }
}
