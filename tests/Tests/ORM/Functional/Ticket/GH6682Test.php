<?php

declare(strict_types=1);

namespace Doctrine\Test\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH6682Test extends OrmFunctionalTestCase
{
    /** @group GH-6682 */
    public function testIssue(): void
    {
        $parsedDefinition = [
            'sequenceName'   => 'test_sequence',
            'allocationSize' => '',
            'initialValue'   => '',
        ];

        $classMetadata = new ClassMetadata('test_entity');
        $classMetadata->setSequenceGeneratorDefinition($parsedDefinition);

        self::assertSame(
            ['sequenceName' => 'test_sequence', 'allocationSize' => '1', 'initialValue' => '1'],
            $classMetadata->sequenceGeneratorDefinition
        );
    }
}
