<?php
namespace Doctrine\Tests\Models\CompositeKeyInheritance;

/**
 * @Entity
 */
class SingleChildClass extends SingleRootClass
{
    /**
     * @var string
     * @Column(type="string")
     */
    public $extension = 'ext';

    /**
     * @var string
     * @Column(type="string")
     * @Id
     */
    private $additionalId = 'additional';
}
