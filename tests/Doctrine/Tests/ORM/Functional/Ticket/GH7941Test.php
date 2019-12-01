<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTimeImmutable;
use Doctrine\Tests\OrmFunctionalTestCase;
use function ltrim;
use function strlen;

/** @group GH7941 */
final class GH7941Test extends OrmFunctionalTestCase
{
    private const PRODUCTS = [
        ['name' => 'Test 1', 'price' => '100', 'square_root' => '/^10(\.0+)?$/'],
        ['name' => 'Test 2', 'price' => '100', 'square_root' => '/^10(\.0+)?$/'],
        ['name' => 'Test 3', 'price' => '100', 'square_root' => '/^10(\.0+)?$/'],
        ['name' => 'Test 4', 'price' => '25', 'square_root' => '/^5(\.0+)?$/'],
        ['name' => 'Test 5', 'price' => '25', 'square_root' => '/^5(\.0+)?$/'],
        ['name' => 'Test 6', 'price' => '-25', 'square_root' => '/^5(\.0+)?$/'],
    ];

    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH7941Product::class]);

        foreach (self::PRODUCTS as $product) {
            $this->_em->persist(new GH7941Product($product['name'], $product['price']));
        }

        $this->_em->flush();
        $this->_em->clear();
    }

    /** @test */
    public function typesShouldBeConvertedForDQLFunctions() : void
    {
        $query = $this->_em->createQuery(
            'SELECT
                 COUNT(product.id) as count,
                 SUM(product.price) as sales,
                 AVG(product.price) as average
             FROM ' . GH7941Product::class . ' product'
        );

        $result = $query->getSingleResult();

        self::assertSame(6, $result['count']);
        self::assertSame('325', $result['sales']);
        self::assertRegExp('/^54\.16+7$/', $result['average']);

        $query = $this->_em->createQuery(
            'SELECT
                 ABS(product.price) as absolute,
                 SQRT(ABS(product.price)) as square_root,
                 LENGTH(product.name) as length
             FROM ' . GH7941Product::class . ' product'
        );

        foreach ($query->getResult() as $i => $item) {
            $product = self::PRODUCTS[$i];

            self::assertSame(ltrim($product['price'], '-'), $item['absolute']);
            self::assertSame(strlen($product['name']), $item['length']);
            self::assertRegExp($product['square_root'], $item['square_root']);
        }
    }
}

/**
 * @Entity
 * @Table()
 */
class GH7941Product
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @Column(type="decimal") */
    public $price;

    /** @Column(type="datetime_immutable") */
    public $createdAt;

    public function __construct(string $name, string $price)
    {
        $this->name      = $name;
        $this->price     = $price;
        $this->createdAt = new DateTimeImmutable();
    }
}
