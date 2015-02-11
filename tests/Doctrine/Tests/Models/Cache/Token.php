<?php

namespace Doctrine\Tests\Models\Cache;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Cache("READ_ONLY")
 * @Table("cache_token")
 */
class Token
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="string")
     */
    protected $token;

    /**
     * @Column(type="date")
     */
    protected $expiresAt;

    /**
     * @OneToOne(targetEntity="Client")
     */
    protected $client;

    /**
     * @OneToMany(targetEntity="Login", cascade={"persist", "remove"}, mappedBy="token")
     * @var array
     */
    protected $logins;

    public function __construct($token, Client $client = null)
    {
        $this->token     = $token;
        $this->logins    = new ArrayCollection();
        $this->client    = $client;
        $this->expiresAt = new \DateTime(date('Y-m-d H:i:s', strtotime("+7 day")));
    }

    /**
     * @return array
     */
    public function getLogins()
    {
        return $this->logins;
    }

    /**
     * @param Login $login
     */
    public function addLogin(Login $login)
    {
        $this->logins[] = $login;
        $login->setToken($this);
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function setExpiresAt(DateTime $expiresAt)
    {
        $this->expiresAt = $expiresAt;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }
}
