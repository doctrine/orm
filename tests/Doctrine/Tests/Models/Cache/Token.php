<?php

namespace Doctrine\Tests\Models\Cache;

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

    public function __construct($token, Client $client = null)
    {
        $this->token     = $token;
        $this->client    = $client;
        $this->expiresAt = new \DateTime(date('Y-m-d H:i:s', strtotime("+7 day")));
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
