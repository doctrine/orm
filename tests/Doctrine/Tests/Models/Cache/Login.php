<?php

namespace Doctrine\Tests\Models\Cache;

/**
 * @Entity
 * @Table("cache_login")
 */
class Login
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column
     */
    public $name;

    /**
     * @ManyToOne(targetEntity="Token", cascade={"persist", "remove"}, inversedBy="logins")
     * @JoinColumn(name="token_id", referencedColumnName="token")
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
