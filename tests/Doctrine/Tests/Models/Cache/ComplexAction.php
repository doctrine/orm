<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("cache_complex_action")
 */
class ComplexAction
{
    /**
     * @ORM\Column
     */
    public $name;

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Action", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="action1_name", referencedColumnName="name")
     */
    public $action1;

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Action", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="action2_name", referencedColumnName="name")
     */
    public $action2;

    /**
     * @ORM\OneToMany(targetEntity="Token", cascade={"persist", "remove"}, mappedBy="complexAction")
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
