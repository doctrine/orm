<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("cache_action")
 */
class Action
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $name;

    /**
     * @ORM\OneToMany(targetEntity="Token", cascade={"persist", "remove"}, mappedBy="action")
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
