<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Tests\OrmFunctionalTestCase;

class CustomRepositoryTest extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9533');
        $this->createSchemaForModels(MinimalEntity::class, OtherMinimalEntity::class);
    }

    public function testMinimalRepository(): void
    {
        $this->_em->persist(new MinimalEntity('foo'));
        $this->_em->persist(new MinimalEntity('bar'));
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(MinimalEntity::class);
        self::assertInstanceOf(MinimalRepository::class, $repository);
        self::assertSame('foo', $repository->find('foo')->id);
    }

    public function testMinimalDefaultRepository(): void
    {
        $this->_em->getConfiguration()->setDefaultRepositoryClassName(MinimalRepository::class);

        $this->_em->persist(new OtherMinimalEntity('foo'));
        $this->_em->persist(new OtherMinimalEntity('bar'));
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(OtherMinimalEntity::class);
        self::assertInstanceOf(MinimalRepository::class, $repository);
        self::assertSame('foo', $repository->find('foo')->id);
    }
}

/** @ORM\Entity(repositoryClass="MinimalRepository") */
class MinimalEntity
{
    /**
     * @ORM\Column
     * @ORM\Id
     *
     * @var string
     */
    public $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}

/** @ORM\Entity */
class OtherMinimalEntity
{
    /**
     * @ORM\Column
     * @ORM\Id
     *
     * @var string
     */
    public $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}

/**
 * @template TEntity of object
 * @implements ObjectRepository<TEntity>
 */
class MinimalRepository implements ObjectRepository
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var ClassMetadata<TEntity> */
    private $class;

    /** @psalm-param ClassMetadata<TEntity> $class */
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        $this->em    = $em;
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->em->find($this->class->name, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->class->name);

        return $persister->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->class->name);

        return $persister->load($criteria, null, null, [], null, 1);
    }

    public function getClassName(): string
    {
        return $this->class->name;
    }
}
