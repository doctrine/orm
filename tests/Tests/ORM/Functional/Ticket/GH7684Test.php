<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\Name\Identifier;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Tests\ORM\Functional\DatabaseDriverTestCase;

use function class_exists;

/**
 * Verifies that associations/columns with an inline '_id' get named properly
 *
 * Github issue: 7684
 */
class GH7684Test extends DatabaseDriverTestCase
{
    public function testIssue(): void
    {
        $table1 = new Table('GH7684_identity_test_table');
        $table1->addColumn('id', 'integer');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $table1->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $table1->setPrimaryKey(['id']);
        }

        $table2 = new Table('GH7684_identity_test_assoc_table');
        $table2->addColumn('id', 'integer');
        $table2->addColumn('gh7684_identity_test_id', 'integer');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $table2->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $table2->setPrimaryKey(['id']);
        }

        $table2->addForeignKeyConstraint('GH7684_identity_test', ['gh7684_identity_test_id'], ['id']);

        $metadatas = $this->convertToClassMetadata([$table1, $table2]);
        $metadata  = $metadatas['Gh7684IdentityTestAssocTable'];

        self::assertArrayHasKey('gh7684IdentityTest', $metadata->associationMappings);
    }
}
