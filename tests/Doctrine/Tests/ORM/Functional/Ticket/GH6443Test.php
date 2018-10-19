<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Tests\DbalTypes\Rot13Type;
use Doctrine\Tests\OrmFunctionalTestCase;
use function count;

/**
 * @group 6443
 */
class GH6443Test extends OrmFunctionalTestCase
{
    /** @var Rot13Type */
    private $rot13Type;

    /** @var DebugStack */
    private $sqlLogger;

    /**
     * when having an entity, that has a non scalar identifier, the type will not be guessed / converted correctly
     */
    public function testIssue() : void
    {
        $entity     = new GH6443Post();
        $entity->id = 'Foo';

        $dql = 'SELECT p FROM ' . GH6443Post::class . ' p WHERE p = ?1';
        $query = $this->em->createQuery($dql);

        // we do not know that the internal type is a rot13, so we can not add the type parameter here
        $query->setParameter(1, $entity);

        // we do not need the result, but we need to execute it to log the SQL-Statement
        $query->getResult();

        $lastSql = $this->sqlLogger->queries[count($this->sqlLogger->queries)];

        // the entity's identifier is of type "rot13" so the query parameter needs to be this type too
        $this->assertSame(
            $this->rot13Type->getName(),
            $lastSql['types'][0],
            "asserting that the entity's identifier type is correctly inferred"
        );
    }

    /**
     * when having an entity, that has a non scalar identifier, the type will not be guessed / converted correctly
     */
    public function testIssueWithProxyClass()
    {
        $metadata    = $this->em->getClassMetadata(GH6443Post::class);
        $entityProxy = $this->em->getProxyFactory()->getProxy($metadata, ['id' => 'Foo']);

        $dql = 'SELECT p FROM ' . GH6443Post::class . ' p WHERE p = ?1';
        $query = $this->em->createQuery($dql);

        // we do not know that the internal type is a rot13, so we can not add the type parameter here
        $query->setParameter(1, $entityProxy);

        // we do not need the result, but we need to execute it to log the SQL-Statement
        $query->getResult();

        $lastSql = $this->sqlLogger->queries[count($this->sqlLogger->queries)];

        // the entity's identifier is of type "rot13" so the query parameter needs to be this type too
        $this->assertSame(
            $this->rot13Type->getName(),
            $lastSql['types'][0],
            "asserting that the entity's identifier type is correctly inferred"
        );
    }

    /**
     * when having an entity, that has a composite identifier, we throw an exception because this is not supported
     */
    public function testIssueWithCompositeIdentifier()
    {
        self::expectException(ORMInvalidArgumentException::class);

        $entity           = new GH6443CombinedIdentityEntity();
        $entity->id       = 'Foo';
        $entity->secondId = 'Bar';

        $dql = 'SELECT entity FROM ' . GH6443CombinedIdentityEntity::class . ' entity WHERE entity = ?1';
        $query = $this->em->createQuery($dql);

        // we set the entity as arameter
        $query->setParameter(1, $entity);

        // this is when the exception should be thrown
        $query->getResult();

    }

    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->sqlLogger = new DebugStack();
        $this->em->getConnection()->getConfiguration()->setSQLLogger($this->sqlLogger);

        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(GH6443Post::class),
            $this->em->getClassMetadata(GH6443CombinedIdentityEntity::class),
        ]);

        $this->rot13Type = Type::getType('rot13');
    }

    protected function tearDown() : void
    {
        parent::tearDown();

        $this->schemaTool->dropSchema([
                $this->em->getClassMetadata(GH6443Post::class),
                $this->em->getClassMetadata(GH6443CombinedIdentityEntity::class),
        ]);
    }
}

/**
 * @ORM\Entity
 */
class GH6443Post
{
    /**
     * @ORM\Id
     * @ORM\Column(type="rot13")
     */
    public $id;
}


/**
 * @ORM\Entity
 */
class GH6443CombinedIdentityEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="rot13")
     */
    public $id;

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    public $secondId;
}
