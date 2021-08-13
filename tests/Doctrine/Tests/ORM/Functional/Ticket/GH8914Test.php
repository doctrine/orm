<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmTestCase;

use function assert;
use function count;

final class GH8914Test extends OrmTestCase
{
    /**
     * @group GH-8914
     * @doesNotPerformAssertions
     */
    public function testDiscriminatorMapWithSeveralLevelsIsSupported(): void
    {
        $entityManager = $this->getTestEntityManager();
        $entityManager->getClassMetadata(GH8914Person::class);
    }
}

/**
 * @MappedSuperclass
 */
abstract class GH8914BaseEntity
{
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"person" = "GH8914Person", "employee" = "GH8914Employee"})
 */
class GH8914Person extends GH8914BaseEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
}

/**
 * @Entity
 */
class GH8914Employee extends GH8914Person
{
}
