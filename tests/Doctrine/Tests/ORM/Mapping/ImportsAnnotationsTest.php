<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmTestCase;
use ReflectionClass;

class ImportsAnnotationsTest extends OrmTestCase
{
    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        // If using non-default annotation reader, everything working.
        //$this->annotationReader = $this->createAnnotationDriver()->getReader();

        // ... but if use a default annotation reader is there a problem.
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * If exception was thrown so is there a error during parsing something with class.
     *
     * @dataProvider dataProviderReflectionClass
     *
     * @param ReflectionClass $reflectionClass
     *
     * @return void
     */
    public function testClassShouldValidImportedAnnotations($reflectionClass)
    {
        $this->assertIsArray($this->annotationReader->getClassAnnotations($reflectionClass));

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $this->assertIsArray($this->annotationReader->getMethodAnnotations($reflectionMethod));
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $this->assertIsArray($this->annotationReader->getPropertyAnnotations($reflectionProperty));
        }
    }

    /**
     * @throws \ReflectionException
     * @return ReflectionClass[]
     */
    public function dataProviderReflectionClass()
    {
        return [
            [new ReflectionClass(AssociationOverride::class)],
            [new ReflectionClass(Cache::class)],
            [new ReflectionClass(ChangeTrackingPolicy::class)],
            [new ReflectionClass(GeneratedValue::class)],
            [new ReflectionClass(InheritanceType::class)],
            [new ReflectionClass(ManyToMany::class)],
            [new ReflectionClass(ManyToOne::class)],
            [new ReflectionClass(OneToMany::class)],
            [new ReflectionClass(OneToOne::class)],
        ];
    }
}
