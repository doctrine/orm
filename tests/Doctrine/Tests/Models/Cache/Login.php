<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("cache_login")
 */
class Login
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column
     */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="Token", cascade={"persist", "remove"}, inversedBy="logins")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="token")
     */
    public $token;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
    }
}
