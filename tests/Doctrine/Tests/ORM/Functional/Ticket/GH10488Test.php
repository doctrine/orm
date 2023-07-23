<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmTestCase;

class GH10488Test extends OrmTestCase
{
    public function testSchemaToolRejectsDuplicateColumn(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'The column value in table root is already defined and cannot be reused for the Doctrine\Tests\ORM\Functional\Ticket\GH10488B#value field. Define a separate column name for this field.'
        );

        $schemaTool->getSchemaFromMetadata([
            $em->getClassMetadata(GH10488Root::class),
            $em->getClassMetadata(GH10488A::class),
            $em->getClassMetadata(GH10488B::class),
        ]);
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="root")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({ "A": "GH10488A", "B": "GH10488B" })
 */
abstract class GH10488Root
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class GH10488A extends GH10488Root
{
    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $value;
}

/**
 * @ORM\Entity
 */
class GH10488B extends GH10488Root
{
    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $value;
}
