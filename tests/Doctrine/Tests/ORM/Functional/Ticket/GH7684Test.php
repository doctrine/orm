<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\Table;
use Doctrine\Tests\ORM\Functional\DatabaseDriverTestCase;

/**
 * Verifies that associations/columns with an inline '_id' get named properly
 *
 * Github issue: 7684
 */
class GH7684 extends DatabaseDriverTestCase
{
    public function testIssue() : void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Platform does not support foreign keys.');
        }

        $table1 = new Table('GH7684_identity_test_table');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = new Table('GH7684_identity_test_assoc_table');
        $table2->addColumn('id', 'integer');
        $table2->addColumn('gh7684_identity_test_id', 'integer');
        $table2->setPrimaryKey(['id']);
        $table2->addForeignKeyConstraint('GH7684_identity_test', ['gh7684_identity_test_id'], ['id']);

        $metadatas = $this->convertToClassMetadata([$table1, $table2]);
        $metadata  = $metadatas['Gh7684IdentityTestAssocTable'];

        $this->assertArrayHasKey('gh7684IdentityTest', $metadata->associationMappings);
    }
}
