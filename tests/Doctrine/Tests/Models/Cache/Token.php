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

    /**
     * @ManyToOne(targetEntity="Action", cascade={"persist", "remove"}, inversedBy="tokens")
     * @JoinColumn(name="action_id", referencedColumnName="id")
     * @var array
     */
    protected $action;

    /**
     * @ManyToOne(targetEntity="ComplexAction", cascade={"persist", "remove"}, inversedBy="tokens")
     * @JoinColumns({
     *   @JoinColumn(name="complex_action1_id", referencedColumnName="action1_id"),
     *   @JoinColumn(name="complex_action2_id", referencedColumnName="action2_id")
     * })
     * @var ComplexAction
     */
    protected $complexAction;

    public function __construct($token, Client $client = null)
    {
        $this->logins    = new ArrayCollection();
        $this->token     = $token;
        $this->client    = $client;
        $this->expiresAt = new \DateTime(date('Y-m-d H:i:s', strtotime("+7 day")));
    }

    /**
     * @return ComplexAction
     */
    public function getComplexAction()
    {
        return $this->complexAction;
    }

    public function setComplexAction(ComplexAction $complexAction)
    {
        $this->complexAction = $complexAction;
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

    public function getAction()
    {
        return $this->action;
    }

    public function setAction(Action $action)
    {
        $this->action = $action;
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
}
