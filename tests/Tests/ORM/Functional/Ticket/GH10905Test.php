<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-10905')]
class GH10905Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->setUpEntitySchema([GH10905Product::class, GH10905Machine::class, GH10905ProductMachine::class, GH10905DefaultMachine::class]);
        foreach ([GH10905Product::class, GH10905Machine::class, GH10905ProductMachine::class, GH10905DefaultMachine::class] as $class) {
            $this->_em->getCache()->evictEntityRegion($class);
        }

        $this->_em->getCache()->evictCollectionRegion(GH10905Product::class, 'defaults');
        $connection = $this->_em->getConnection();
        $connection->insert('GH10905Product', ['name' => 'teacup']);
        $connection->insert('GH10905Machine', ['name' => 'printer']);
        $connection->insert('GH10905ProductMachine', ['product' => 'teacup', 'machine' => 'printer', 'description' => 'Available printer']);
        $connection->insert('GH10905DefaultMachine', ['product' => 'teacup', 'machine' => 'printer', 'location' => 'office']);
    }

    protected function tearDown(): void
    {
        $connection = $this->_em->getConnection();
        $connection->executeStatement('DELETE FROM GH10905DefaultMachine');
        $connection->executeStatement('DELETE FROM GH10905ProductMachine');
        $connection->executeStatement('DELETE FROM GH10905Product');
        $connection->executeStatement('DELETE FROM GH10905Machine');

        parent::tearDown();
    }

    /** @return iterable<string, array{bool}> */
    public static function cacheStates(): iterable
    {
        yield 'cold target' => [false];
        yield 'warm target' => [true];
    }

    #[DataProvider('cacheStates')]
    public function testProxyWithEntityValuedIdentifiersCanUseL2(bool $warm): void
    {
        if ($warm) {
            $this->_em->find(GH10905ProductMachine::class, ['productEntity' => 'teacup', 'machineEntity' => 'printer']);
            $this->_em->clear();
        }

        for ($i = 0; $i < 2; ++$i) {
            $product = $this->_em->find(GH10905Product::class, 'teacup');
            self::assertCount(1, $product->defaults);
            $default = $product->defaults->first();
            self::assertSame('office', $default->location);
            self::assertSame($product, $default->product);

            $target = $default->productMachine;
            $this->getQueryLog()->reset()->enable();
            self::assertSame('Available printer', $target->description);
            $this->assertQueryCount($warm || $i > 0 ? 0 : 1);
            self::assertSame($product, $target->productEntity);
            self::assertSame('printer', $target->machineEntity->name);
            self::assertSame($target, $this->_em->find(GH10905ProductMachine::class, ['productEntity' => 'teacup', 'machineEntity' => 'printer']));
            self::assertTrue($this->_em->getCache()->containsEntity(GH10905ProductMachine::class, ['productEntity' => 'teacup', 'machineEntity' => 'printer']));
            $this->_em->clear();
        }
    }

    #[DataProvider('cacheStates')]
    public function testReferenceWithAssociationAndScalarIdentifier(bool $warm): void
    {
        $id = ['product' => 'teacup', 'location' => 'office'];
        if ($warm) {
            $this->_em->find(GH10905DefaultMachine::class, $id);
            $this->_em->clear();
        }

        $this->getQueryLog()->reset()->enable();
        $reference = $this->_em->getReference(GH10905DefaultMachine::class, $id);
        $this->assertQueryCount(0);
        self::assertTrue($this->_em->isUninitializedObject($reference));
        $this->_em->initializeObject($reference);
        $this->assertQueryCount($warm ? 0 : 1);
        self::assertSame('office', $reference->location);
        self::assertSame('teacup', $reference->product->name);
        self::assertSame($reference, $this->_em->find(GH10905DefaultMachine::class, $id));
        self::assertTrue($this->_em->getCache()->containsEntity(GH10905DefaultMachine::class, $id));
    }

    /** @return iterable<string, array{bool}> */
    public static function identifierObjects(): iterable
    {
        yield 'managed identifiers' => [false];
        yield 'detached identifiers' => [true];
    }

    #[DataProvider('identifierObjects')]
    public function testPersisterAcceptsManagedAndDetachedIdentifierObjects(bool $detach): void
    {
        $product = $this->_em->getReference(GH10905Product::class, 'teacup');
        $machine = $this->_em->getReference(GH10905Machine::class, 'printer');
        if ($detach) {
            $this->_em->clear();
        }

        $id        = ['productEntity' => $product, 'machineEntity' => $machine];
        $persister = $this->_em->getUnitOfWork()->getEntityPersister(GH10905ProductMachine::class);
        $this->getQueryLog()->reset()->enable();
        $loaded = $persister->loadById($id);
        $this->assertQueryCount(1);
        self::assertSame('Available printer', $loaded->description);
        self::assertTrue($this->_em->isUninitializedObject($product));
        self::assertSame('printer', $machine->name);

        $this->getQueryLog()->reset()->enable();
        self::assertTrue($persister->exists($loaded));
        $this->assertQueryCount(0);
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();
        $reloaded = $persister->loadById($id);
        $this->assertQueryCount(0);
        self::assertSame('Available printer', $reloaded->description);
        self::assertSame($reloaded, $this->_em->find(GH10905ProductMachine::class, ['productEntity' => 'teacup', 'machineEntity' => 'printer']));
    }
}

#[ORM\Entity(readOnly: true)]
#[ORM\Cache]
class GH10905Product
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    public string $name;

    /** @var Collection<int, GH10905DefaultMachine> */
    #[ORM\OneToMany(targetEntity: GH10905DefaultMachine::class, mappedBy: 'product')]
    #[ORM\Cache]
    public Collection $defaults;

    public function __construct()
    {
        $this->defaults = new ArrayCollection();
    }
}

#[ORM\Entity(readOnly: true)]
#[ORM\Cache]
class GH10905Machine
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    public string $name;
}

#[ORM\Entity(readOnly: true)]
#[ORM\Cache]
class GH10905ProductMachine
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: GH10905Product::class)]
    #[ORM\JoinColumn(name: 'product', referencedColumnName: 'name')]
    #[ORM\Cache]
    public GH10905Product $productEntity;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: GH10905Machine::class)]
    #[ORM\JoinColumn(name: 'machine', referencedColumnName: 'name')]
    #[ORM\Cache]
    public GH10905Machine $machineEntity;

    #[ORM\Column(length: 100)]
    public string $description;
}

#[ORM\Entity(readOnly: true)]
#[ORM\Cache]
class GH10905DefaultMachine
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: GH10905Product::class, inversedBy: 'defaults')]
    #[ORM\JoinColumn(name: 'product', referencedColumnName: 'name')]
    #[ORM\Cache]
    public GH10905Product $product;

    #[ORM\Id]
    #[ORM\Column(length: 50)]
    public string $location;

    #[ORM\ManyToOne(targetEntity: GH10905ProductMachine::class)]
    #[ORM\JoinColumn(name: 'product', referencedColumnName: 'product', nullable: false)]
    #[ORM\JoinColumn(name: 'machine', referencedColumnName: 'machine', nullable: false)]
    #[ORM\Cache]
    public GH10905ProductMachine $productMachine;
}
