<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table("cache_complex_action")
 */
class ComplexAction
{
    const CLASSNAME = __CLASS__;

    /**
     * @Column
     */
    public $name;

    /**
     * @Id
     * @OneToOne(targetEntity="Action", cascade={"persist", "remove"})
     * @JoinColumn(name="action1_id", referencedColumnName="id")
     */
    public $action1;

    /**
     * @Id
     * @OneToOne(targetEntity="Action", cascade={"persist", "remove"})
     * @JoinColumn(name="action2_id", referencedColumnName="id")
     */
    public $action2;

    /**
     * @OneToMany(targetEntity="Token", cascade={"persist", "remove"}, mappedBy="complexAction")
     */
    public $tokens;

    public function __construct(Action $action1, Action $action2, $name)
    {
        $this->name = $name;
        $this->action1 = $action1;
        $this->action2 = $action2;
        $this->tokens = new ArrayCollection();
    }

    public function addToken(Token $token)
    {
        $this->tokens[] = $token;
        $token->complexAction = $this;
    }

    /**
     * @return Action
     */
    public function getAction1()
    {
        return $this->action1;
    }

    /**
     * @return Action
     */
    public function getAction2()
    {
        return $this->action2;
    }
}
