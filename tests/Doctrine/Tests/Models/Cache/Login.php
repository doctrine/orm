<?php

namespace Doctrine\Tests\Models\Cache;

/**
 * @Entity
 * @Table("cache_login")
 */
class Login
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
     * @ManyToOne(targetEntity="Token", cascade={"persist", "remove"}, inversedBy="logins")
     * @JoinColumn(name="token_id", referencedColumnName="token")
     */
    private $token;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param Token $token
     */
    public function setToken(Token $token)
    {
        $this->token = $token;
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
