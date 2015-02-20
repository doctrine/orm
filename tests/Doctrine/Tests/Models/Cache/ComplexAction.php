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
    private $name;

    /**
     * @Id
     * @OneToOne(targetEntity="Action", cascade={"persist", "remove"})
     * @JoinColumn(name="action1_id", referencedColumnName="id")
     */
    private $action1;

    /**
     * @Id
     * @OneToOne(targetEntity="Action", cascade={"persist", "remove"})
     * @JoinColumn(name="action2_id", referencedColumnName="id")
     */
    private $action2;

    /**
     * @OneToMany(targetEntity="Token", cascade={"persist", "remove"}, mappedBy="complexAction")
     */
    private $tokens;

    public function __construct(Action $action1, Action $action2, $name)
    {
        $this->name = $name;
        $this->action1 = $action1;
        $this->action2 = $action2;
        $this->tokens = new ArrayCollection();
    }

    public function getAction1()
    {
        return $this->action1;
    }

    public function getAction2()
    {
        return $this->action2;
    }

    public function addToken(Token $token)
    {
        $this->tokens[] = $token;
        $token->setComplexAction($this);
    }

    public function getTokens()
    {
        return $this->tokens;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}
