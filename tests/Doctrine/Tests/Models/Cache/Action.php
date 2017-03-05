<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table("cache_action")
 */
class Action
{
    /**
     * @Id
     * @Column(type="string")
     * @GeneratedValue(strategy="NONE")
     */
    public $name;

    /**
     * @OneToMany(targetEntity="Token", cascade={"persist", "remove"}, mappedBy="action")
     */
    public $tokens;

    public function __construct($name)
    {
        $this->name = $name;
        $this->tokens = new ArrayCollection();
    }

    public function addToken(Token $token)
    {
        $this->tokens[] = $token;
        $token->action = $this;
    }
}
