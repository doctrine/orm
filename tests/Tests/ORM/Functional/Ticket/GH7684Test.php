<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Name\Identifier;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableEditor;
use Doctrine\DBAL\Types\Type;
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
        $table1 = new Table(
            'GH7684_identity_test_table',
            [new Column('id', Type::getType('integer'))],
        );

        $table2 = new Table(
            'GH7684_identity_test_assoc_table',
            [
                new Column('id', Type::getType('integer')),
                new Column('gh7684_identity_test_id', Type::getType('integer')),
            ],
        );

        if (class_exists(TableEditor::class)) {
            $table1 = $table1->edit()
                ->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true))
                ->create();
            $table2 = $table2->edit()
                ->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true))
                ->addForeignKeyConstraint(new ForeignKeyConstraint(
                    ['gh7684_identity_test_id'],
                    'GH7684_identity_test_table',
                    ['id'],
                ))
                ->create();
        } else {
            $table1->setPrimaryKey(['id']);
            $table2->setPrimaryKey(['id']);
            $table2->addForeignKeyConstraint('GH7684_identity_test', ['gh7684_identity_test_id'], ['id']);
        }

        $metadatas = $this->convertToClassMetadata([$table1, $table2]);
        $metadata  = $metadatas['Gh7684IdentityTestAssocTable'];

        self::assertArrayHasKey('gh7684IdentityTest', $metadata->associationMappings);
    }
}
