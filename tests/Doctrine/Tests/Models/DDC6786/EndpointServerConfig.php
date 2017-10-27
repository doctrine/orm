<?php
namespace Doctrine\Tests\Models\DDC6786;

/**
 * EndpointServerConfig
 *
 * @Table(name="endpoint_server_config", indexes={
 *     @Index(name="fk_endpoint_server_config_server_idx", columns={"server_name"})}
 * )
 * @Entity
 */
class EndpointServerConfig
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
     * @Column(name="dns_address", type="string", length=253, nullable=true)
     */
    protected $dnsAddress;

    /**
     * @var Server
     *
     * @ManyToOne(targetEntity="Server")
     * @JoinColumns({
     *   @JoinColumn(name="server_name", referencedColumnName="name")
     * })
     */
    protected $server;

    /**
     * @var Endpoint
     *
     * @OneToOne(targetEntity="Endpoint", mappedBy="endpointServerConfig")
     */
    protected $endpoint;

    /**
     * @param integer $id
     *
     * @return EndpointServerConfig
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
     * @param string $dnsAddress
     *
     * @return EndpointServerConfig
     */
    public function setDnsAddress($dnsAddress)
    {
        $this->dnsAddress = $dnsAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getDnsAddress()
    {
        return $this->dnsAddress;
    }

    /**
     * @param Server $server
     *
     * @return EndpointServerConfig
     */
    public function setServer(Server $server = null)
    {

        if( ! $server || ! $server->getName()) {
            $server = null;
        }
        $this->server = $server;

        return $this;
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param Endpoint $endpoint
     *
     * @return EndpointServerConfig
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * @return Endpoint
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

}
