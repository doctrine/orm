<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table("cache_complex_action")
 */
class ComplexAction
{
    /** @Column */
    public $name;

    /**
     * @Id
     * @OneToOne(targetEntity="Action", cascade={"persist", "remove"})
     * @JoinColumn(name="action1_name", referencedColumnName="name")
     */
    public $action1;

    /**
     * @Id
     * @OneToOne(targetEntity="Action", cascade={"persist", "remove"})
     * @JoinColumn(name="action2_name", referencedColumnName="name")
     */
    public $action2;

    /** @OneToMany(targetEntity="Token", cascade={"persist", "remove"}, mappedBy="complexAction") */
    public $tokens;

    public function __construct(Action $action1, Action $action2, $name)
    {
        $this->name    = $name;
        $this->action1 = $action1;
        $this->action2 = $action2;
        $this->tokens  = new ArrayCollection();
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
