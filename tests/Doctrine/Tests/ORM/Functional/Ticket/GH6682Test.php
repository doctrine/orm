<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH6682Test extends OrmFunctionalTestCase
{
    /**
     * @group 6682
     */
    public function testIssue() : void
    {
        self::markTestIncomplete(
            '@guilhermeblanco, in #6683 we added allocationSize/initialValue as to the sequence definition but with the'
            . ' changes you have made I am not sure if we should rather test this relying on the mapping drivers instead'
        );

        $parsedDefinition = [
            'sequenceName'   => 'test_sequence',
            'allocationSize' => '',
            'initialValue'   => '',
        ];

        $classMetadataInfo = new ClassMetadataInfo('test_entity');
        $classMetadataInfo->setSequenceGeneratorDefinition($parsedDefinition);

        self::assertSame(
            ['sequenceName' => 'test_sequence', 'allocationSize' => '1', 'initialValue' => '1'],
            $classMetadataInfo->sequenceGeneratorDefinition
        );
    }
}
