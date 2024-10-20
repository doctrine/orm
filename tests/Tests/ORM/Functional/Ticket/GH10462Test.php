<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

use function method_exists;

class GH10462Test extends OrmFunctionalTestCase
{
    public function testCharsetAndCollationOptionsOnDiscriminatedColumn(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof MySQLPlatform) {
            self::markTestSkipped('This test is useful for all databases, but designed only for mysql.');
        }

        if (method_exists(AbstractPlatform::class, 'getGuidExpression')) {
            self::markTestSkipped('Test valid for doctrine/dbal:3.x only.');
        }

        $this->createSchemaForModels(GH10462Person::class, GH10462Employee::class);
        $schemaManager = $this->createSchemaManager();
        $personOptions = $schemaManager->introspectTable('gh10462_person')->getColumn('discr')->toArray();
        self::assertSame('ascii', $personOptions['charset']);
        self::assertSame('ascii_general_ci', $personOptions['collation']);
    }
}

#[Entity]
#[Table(name: 'gh10462_person')]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string', options: ['charset' => 'ascii', 'collation' => 'ascii_general_ci'])]
#[DiscriminatorMap(['person' => GH10462Person::class, 'employee' => GH10462Employee::class])]
class GH10462Person
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}

#[Entity]
class GH10462Employee extends GH10462Person
{
}
