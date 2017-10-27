<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC6786\Endpoint;
use Doctrine\Tests\Models\DDC6786\EndpointServerConfig;
use Doctrine\Tests\Models\DDC6786\Server;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC6786Test extends OrmFunctionalTestCase
{

    public function setUp()
    {
        parent::setUp();
        try {
            $this->useModelSet('DDC6786');
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(Server::class),
                    $this->_em->getClassMetadata(EndpointServerConfig::class),
                    $this->_em->getClassMetadata(Endpoint::class),
                ]
            );
        } catch (\Exception $e) {

        }

        $this->createFixtures();
    }

    public function testSettingEndpointServerConfigDirectly()
    {
        $endpointServerConfigRepo = $this->_em->getRepository(EndpointServerConfig::class);
        /** @var EndpointServerConfig $endpointServerConfig */
        $endpointServerConfig = $endpointServerConfigRepo->find(1);

        $endpointServerConfig->setServer(null);

        $this->_em->persist($endpointServerConfig);
        $this->_em->flush();

        /** @var EndpointServerConfig $endpointServerConfigUpdated */
        $endpointServerConfigUpdated = $endpointServerConfigRepo->find(1);
        $this->assertNull($endpointServerConfigUpdated->getServer());
    }

    public function testSettingEndpointServerConfigViaEndpoint()
    {
        $endpointRepo = $this->_em->getRepository(Endpoint::class);
        /** @var Endpoint $endpoint */
        $endpoint = $endpointRepo->find(1);

        $endpoint->getEndpointServerConfig()->setServer(null);

        $this->_em->persist($endpoint);
        $this->_em->flush();

        $endpointServerConfigRepo = $this->_em->getRepository(EndpointServerConfig::class);
        /** @var EndpointServerConfig $endpointServerConfigUpdated */
        $endpointServerConfigUpdated = $endpointServerConfigRepo->find(1);
        $this->assertNull($endpointServerConfigUpdated->getServer());
    }

    private function createFixtures()
    {
        $fooServer = new Server();
        $fooServer->setName('foo');
        $this->_em->persist($fooServer);

        $barEndpointServerConfig = new EndpointServerConfig();
        $barEndpointServerConfig->setServer($fooServer);
        $this->_em->persist($barEndpointServerConfig);

        $buzEndpoint = new Endpoint();
        $buzEndpoint->setCreated(new \DateTime());
        $buzEndpoint->setEndpointServerConfig($barEndpointServerConfig);
        $this->_em->persist($buzEndpoint);

        $this->_em->flush();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema(
            [
                $this->_em->getClassMetadata(Server::class),
                $this->_em->getClassMetadata(EndpointServerConfig::class),
                $this->_em->getClassMetadata(Endpoint::class),
            ]
        );
    }

}
