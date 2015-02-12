<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table("cache_action")
 */
class Action
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column
     */
    private $name;

    /**
     * @OneToMany(targetEntity="Token", cascade={"persist", "remove"}, mappedBy="action")
     */
    private $tokens;

    public function __construct($name)
    {
        $this->name = $name;
        $this->tokens = new ArrayCollection();
    }

    public function addToken(Token $token)
    {
        $this->tokens[] = $token;
        $token->setAction($this);
    }

    public function getTokens()
    {
        return $this->tokens;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($nae)
    {
        $this->name = $nae;
    }
}
