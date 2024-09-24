<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11608;

use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\OrmFunctionalTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

use function var_dump;

class GH11608Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->useModelSet(self::class);
        $this->_em->getConfiguration()->setNamingStrategy(new UnderscoreNamingStrategy());
    }

    public function testOneToManyIndexByErrorDetected(): void
    {
        $validator = new SchemaValidator($this->_em);

        $errors = $validator->validateClass($this->_em->getClassMetadata(LeftSideEntity::class));

        self::assertCount(2, $errors);

        self::assertStringContainsStringIgnoringCase('invalidRightSideEntities is indexed by a field right_side_id_that_doesnt_exist', $errors[0]);
        self::assertStringContainsStringIgnoringCase('invalidConnections is indexed by a field arbitrary_value_that_doesnt_exist', $errors[1]);
    }

    #[DataProvider('provideValidEntitiesCauseNoErrors')]
    public function testValidEntitiesCauseNoErrors(string $className): void
    {
        $validator = new SchemaValidator($this->_em);

        $errors = $validator->validateClass($this->_em->getClassMetadata($className));

        var_dump($errors);
        self::assertEmpty($errors);
    }

    public static function provideValidEntitiesCauseNoErrors(): Generator
    {
        yield [ConnectingEntity::class];
        yield [RightSideEntity::class];
    }
}
