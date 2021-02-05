<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

use function date;
use function strtotime;

/**
 * @Entity
 * @Cache("READ_ONLY")
 * @Table("cache_token")
 */
class Token
{
    /**
     * @Id
     * @Column(type="string")
     */
    public $token;

    /** @Column(type="date") */
    public $expiresAt;

    /** @OneToOne(targetEntity="Client") */
    public $client;

    /**
     * @OneToMany(targetEntity="Login", cascade={"persist", "remove"}, mappedBy="token")
     * @var array
     */
    public $logins;

    /**
     * @ManyToOne(targetEntity="Action", cascade={"persist", "remove"}, inversedBy="tokens")
     * @JoinColumn(name="action_name", referencedColumnName="name")
     * @var array
     */
    public $action;

    /**
     * @ManyToOne(targetEntity="ComplexAction", cascade={"persist", "remove"}, inversedBy="tokens")
     * @JoinColumns({
     *   @JoinColumn(name="complex_action1_name", referencedColumnName="action1_name"),
     *   @JoinColumn(name="complex_action2_name", referencedColumnName="action2_name")
     * })
     * @var ComplexAction
     */
    public $complexAction;

    public function __construct($token, ?Client $client = null)
    {
        $this->logins    = new ArrayCollection();
        $this->token     = $token;
        $this->client    = $client;
        $this->expiresAt = new DateTime(date('Y-m-d H:i:s', strtotime('+7 day')));
    }

    public function addLogin(Login $login): void
    {
        $this->logins[] = $login;
        $login->token   = $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getAction(): Action
    {
        return $this->action;
    }

    public function getComplexAction(): ComplexAction
    {
        return $this->complexAction;
    }
}
