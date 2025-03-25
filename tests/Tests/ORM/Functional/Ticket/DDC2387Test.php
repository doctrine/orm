<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\Name\Identifier;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Functional\DatabaseDriverTestCase;
use PHPUnit\Framework\Attributes\Group;

use function class_exists;

class DDC2387Test extends DatabaseDriverTestCase
{
    #[Group('DDC-2387')]
    public function testCompositeAssociationKeyDetection(): void
    {
        $product = new Table('ddc2387_product');
        $product->addColumn('id', 'integer');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $product->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $product->setPrimaryKey(['id']);
        }

        $attributes = new Table('ddc2387_attributes');
        $attributes->addColumn('product_id', 'integer');
        $attributes->addColumn('attribute_name', 'string');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $attributes->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('product_id')), new UnqualifiedName(Identifier::unquoted('attribute_name'))], true));
        } else {
            $attributes->setPrimaryKey(['product_id', 'attribute_name']);
        }

        $attributes->addForeignKeyConstraint('ddc2387_product', ['product_id'], ['product_id']);

        $metadata = $this->convertToClassMetadata([$product, $attributes], []);

        self::assertEquals(ClassMetadata::GENERATOR_TYPE_NONE, $metadata['Ddc2387Attributes']->generatorType);
        self::assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $metadata['Ddc2387Product']->generatorType);
    }
}
