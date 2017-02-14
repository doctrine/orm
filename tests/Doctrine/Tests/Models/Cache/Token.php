<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Cache("READ_ONLY")
 * @ORM\Table("cache_token")
 */
class Token
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    public $token;

    /**
     * @ORM\Column(type="date")
     */
    public $expiresAt;

    /**
     * @ORM\OneToOne(targetEntity="Client")
     */
    public $client;

    /**
     * @ORM\OneToMany(targetEntity="Login", cascade={"persist", "remove"}, mappedBy="token")
     *
     * @var array
     */
    public $logins;

    /**
     * @ORM\ManyToOne(targetEntity="Action", cascade={"persist", "remove"}, inversedBy="tokens")
     * @ORM\JoinColumn(name="action_name", referencedColumnName="name")
     *
     * @var array
     */
    public $action;

    /**
     * @ORM\ManyToOne(targetEntity="ComplexAction", cascade={"persist", "remove"}, inversedBy="tokens")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="complex_action1_name", referencedColumnName="action1_name"),
     *   @ORM\JoinColumn(name="complex_action2_name", referencedColumnName="action2_name")
     * })
     *
     * @var ComplexAction
     */
    public $complexAction;

    public function __construct($token, Client $client = null)
    {
        $this->logins    = new ArrayCollection();
        $this->token     = $token;
        $this->client    = $client;
        $this->expiresAt = new \DateTime(date('Y-m-d H:i:s', strtotime("+7 day")));
    }

    /**
     * @param Login $login
     */
    public function addLogin(Login $login)
    {
        $this->logins[] = $login;
        $login->token = $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return ComplexAction
     */
    public function getComplexAction()
    {
        return $this->complexAction;
    }

}
