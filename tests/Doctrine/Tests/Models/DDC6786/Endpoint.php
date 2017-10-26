<?php
namespace Doctrine\Tests\Models\DDC6786;

use ReflectionClass;

/**
 * Endpoint
 *
 * @Table(name="endpoint")
 * @Entity
 */
class Endpoint
{

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @Column(name="role", type="string", nullable=true)
     */
    protected $role;

    /**
     * @var string
     *
     * @Column(name="server_place", type="string", nullable=true)
     *
     * @Groups({"export"})
     */
    protected $serverPlace;

    /**
     * @var string
     *
     * @Column(name="contact_person", type="string", length=500, nullable=true)
     *
     * @Groups({"export"})
     */
    protected $contactPerson;

    /**
     * @var \DateTime
     *
     * @Column(name="created", type="datetime", nullable=false)
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @Column(name="updated", type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * @var EndpointServerConfig
     *
     * @OneToOne(targetEntity="EndpointServerConfig", inversedBy="endpoint", cascade={"persist"})
     * @JoinColumns({
     *   @JoinColumn(name="endpoint_server_config_id", referencedColumnName="id")
     * })
     *
     * @Groups({"export"})
     */
    protected $endpointServerConfig;

    /**
     * @param integer $id
     *
     * @return Endpoint
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $role
     *
     * @return Endpoint
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $type
     *
     * @return Endpoint
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     *
     * @Groups({"export"})
     */
    public function getType()
    {
        return str_replace(
            'Endpoint',
            '',
            (new ReflectionClass($this))->getShortName()
        );
    }

    /**
     * @param string $serverPlace
     *
     * @return Endpoint
     */
    public function setServerPlace($serverPlace)
    {
        $this->serverPlace = $serverPlace;

        return $this;
    }

    /**
     * @return string
     */
    public function getServerPlace()
    {
        return $this->serverPlace;
    }

    /**
     * @param string $contactPerson
     *
     * @return Endpoint
     */
    public function setContactPerson($contactPerson)
    {
        $this->contactPerson = $contactPerson;

        return $this;
    }

    /**
     * @return string
     */
    public function getContactPerson()
    {
        return $this->contactPerson;
    }

    /**
     * @param \DateTime $created
     *
     * @return Endpoint
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $updated
     *
     * @return Endpoint
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param EndpointServerConfig $endpointServerConfig
     *
     * @return Endpoint
     */
    public function setEndpointServerConfig(EndpointServerConfig $endpointServerConfig = null)
    {
        $this->endpointServerConfig = $endpointServerConfig;

        return $this;
    }

    /**
     * @return EndpointServerConfig
     */
    public function getEndpointServerConfig()
    {
        return $this->endpointServerConfig;
    }

}
