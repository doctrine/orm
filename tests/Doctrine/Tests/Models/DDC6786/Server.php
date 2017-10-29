<?php
namespace Doctrine\Tests\Models\DDC6786;

/**
 * Server
 *
 * @Table(name="server")
 * @Entity
 *
 * @author automatix
 */
class Server
{

    /**
     * @var string
     *
     * @Column(name="name", type="string", length=32, nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    protected $name;

    /**
     * @var boolean
     *
     * @Column(name="active", type="boolean", nullable=true)
     */
    protected $active;

    /**
     * @var \DateTime
     *
     * @Column(name="updated", type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * @var string
     *
     * @Column(name="node_name", type="string", length=50, nullable=true)
     */
    protected $nodeName;

    /**
     * @var string
     *
     * @Column(name="virtual_node_name", type="string", length=50, nullable=true)
     */
    protected $virtualNodeName;

    /**
     * Not relevant for ORM. Should become obsolete after and be removed after the migration to Doctrine.
     *
     * @var EndpointServerConfig[]
     */
    protected $endpointServerConfigs;

    /**
     * @param string $name
     *
     * @return Server
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param boolean $active
     *
     * @return Server
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param \DateTime $updated
     *
     * @return Server
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
     * @param string $nodeName
     *
     * @return Server
     */
    public function setNodeName($nodeName)
    {
        $this->nodeName = $nodeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }

    /**
     * @param string $virtualNodeName
     *
     * @return Server
     */
    public function setVirtualNodeName($virtualNodeName)
    {
        $this->virtualNodeName = $virtualNodeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getVirtualNodeName()
    {
        return $this->virtualNodeName;
    }

    /**
     * @param EndpointServerConfig[] $endpointServerConfigs
     *
     * @return Server
     */
    public function setEndpointServerConfigs($endpointServerConfigs)
    {
        $this->endpointServerConfigs = $endpointServerConfigs;
        return $this;
    }

    /**
     * @return EndpointServerConfig[] $endpointServerConfigs
     */
    public function getEndpointServerConfigs()
    {
        return $this->endpointServerConfigs;
    }

}
