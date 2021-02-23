<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\Tests\ORM\Functional\DatabaseDriverTestCase;

class DDC2387Test extends DatabaseDriverTestCase
{
    /**
     * @group DDC-2387
     */
    public function testCompositeAssociationKeyDetection() : void
    {
        $product = new Table('ddc2387_product');
        $product->addColumn('id', 'integer');
        $product->setPrimaryKey(['id']);

        $attributes = new Table('ddc2387_attributes');
        $attributes->addColumn('product_id', 'integer');
        $attributes->addColumn('attribute_name', 'string');
        $attributes->setPrimaryKey(['product_id', 'attribute_name']);
        $attributes->addForeignKeyConstraint('ddc2387_product', ['product_id'], ['product_id']);

        $metadata = $this->convertToClassMetadata([$product, $attributes], []);

        self::assertFalse($metadata['Ddc2387Attributes']->getProperty('productId')->hasValueGenerator());
        self::assertEquals(GeneratorType::IDENTITY, $metadata['Ddc2387Product']->getProperty('id')->getValueGenerator()->getType());
    }
}
