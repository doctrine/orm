<?php
/**
 * This file is part of orm package
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\FieldMetadata;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class FieldMetadataTest extends TestCase
{

    public function testSerialization()
    {
        $name = 'property';
        $expected = 'property value';
        $subject = new FieldMetadata($name);
        $subject->setReflectionProperty(new ReflectionProperty(DummyEntityClass::class, $name));
        try {
            $serialized = serialize($subject);
            /** @var FieldMetadata $recovered */
            $recovered = unserialize($serialized);
            $this->assertInstanceOf(FieldMetadata::class, $recovered);
            $this->assertEquals($expected, $recovered->getValue(new DummyEntityClass($expected)));
        } catch (Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }
}

class DummyEntityClass
{
    /** @var string */
    public $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }
}
