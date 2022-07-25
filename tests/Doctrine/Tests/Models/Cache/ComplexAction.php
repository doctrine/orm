<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table("cache_complex_action")
 */
class ComplexAction
{
    /**
     * @psalm-var Collection<int, Token>
     * @OneToMany(targetEntity="Token", cascade={"persist", "remove"}, mappedBy="complexAction")
     */
    public $tokens;

    public function __construct(
        /**
         * @Id
         * @OneToOne(targetEntity="Action", cascade={"persist", "remove"})
         * @JoinColumn(name="action1_name", referencedColumnName="name")
         */
        public Action $action1,
        /**
         * @Id
         * @OneToOne(targetEntity="Action", cascade={"persist", "remove"})
         * @JoinColumn(name="action2_name", referencedColumnName="name")
         */
        public Action $action2,
        /** @Column */
        public string $name,
    ) {
        $this->tokens = new ArrayCollection();
    }

    public function addToken(Token $token): void
    {
        $this->tokens[]       = $token;
        $token->complexAction = $this;
    }

    public function getAction1(): Action
    {
        return $this->action1;
    }

    public function getAction2(): Action
    {
        return $this->action2;
    }
}
