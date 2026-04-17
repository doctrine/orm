<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

use function getenv;

use const PHP_VERSION_ID;

class GH12403Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        if (PHP_VERSION_ID < 80400 || getenv('ENABLE_NATIVE_LAZY_OBJECTS') === '0') {
            self::markTestSkipped('Native lazy objects are required.');
        }

        parent::setUp();

        $this->createSchemaForModels(
            GH12403Owner::class,
            GH12403Scalar::class,
            GH12403Target::class,
        );
    }

    public function testManagedOneToOneTargetIsEitherUninitializedOrSnapshotted(): void
    {
        $this->requireNativeLazyObjects();

        $target = new GH12403Target(10);
        $owner  = new GH12403Owner(10, $target);

        $this->_em->persist($owner);
        $this->_em->flush();
        $this->_em->clear();

        $owner = $this->_em->find(GH12403Owner::class, 10);
        self::assertInstanceOf(GH12403Owner::class, $owner);

        $target = $owner->target();
        self::assertTrue($this->_em->contains($target));

        $unitOfWork = $this->_em->getUnitOfWork();

        self::assertTrue(
            $unitOfWork->isUninitializedObject($target) || $unitOfWork->getOriginalEntityData($target) !== [],
            'A managed initialized association target must have a UnitOfWork original-data snapshot.',
        );
    }

    public function testScalarChangeIsPersistedAfterLazyReferenceInitialization(): void
    {
        $this->requireNativeLazyObjects();

        $scalar = new GH12403Scalar(1, 'old name');

        $this->_em->persist($scalar);
        $this->_em->flush();
        $this->_em->clear();

        $scalar     = $this->_em->getReference(GH12403Scalar::class, 1);
        $unitOfWork = $this->_em->getUnitOfWork();

        self::assertTrue($unitOfWork->isUninitializedObject($scalar));
        self::assertSame([], $unitOfWork->getOriginalEntityData($scalar));

        $this->_em->getClassMetadata(GH12403Scalar::class)->reflClass->markLazyObjectAsInitialized($scalar);

        self::assertFalse($unitOfWork->isUninitializedObject($scalar));
        self::assertSame([], $unitOfWork->getOriginalEntityData($scalar));

        $scalar->rename('new name');

        $this->_em->flush();
        $this->_em->clear();

        self::assertSame(
            'new name',
            $this->_em->getConnection()->fetchOne('SELECT name FROM gh12403_scalars WHERE id = 1'),
        );
    }

    private function requireNativeLazyObjects(): void
    {
        if (! $this->_em->getConfiguration()->isNativeLazyObjectsEnabled()) {
            self::markTestSkipped('Native lazy objects are required.');
        }
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'gh12403_owners')]
class GH12403Owner
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\OneToOne(targetEntity: GH12403Target::class, inversedBy: 'owner', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'target_id', referencedColumnName: 'id', nullable: false)]
    private GH12403Target $target;

    public function __construct(int $id, GH12403Target $target)
    {
        $this->id = $id;
        $this->setTarget($target);
    }

    public function target(): GH12403Target
    {
        return $this->target;
    }

    public function setTarget(GH12403Target $target): void
    {
        if (isset($this->target)) {
            $this->target->clearOwner();
        }

        $this->target = $target;
        $target->setOwner($this);
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'gh12403_targets')]
class GH12403Target
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\OneToOne(targetEntity: GH12403Owner::class, mappedBy: 'target')]
    private GH12403Owner|null $owner = null;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function setOwner(GH12403Owner $owner): void
    {
        $this->owner = $owner;
    }

    public function clearOwner(): void
    {
        $this->owner = null;
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'gh12403_scalars')]
class GH12403Scalar
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    public function __construct(int $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }
}
