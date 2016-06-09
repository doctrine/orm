<?php
namespace Doctrine\Tests\Models\DDC3303;

/**
 * @MappedSuperclass
 */
abstract class DDC3303Person
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @Column(type="string")
     *
     * @var string
     */
    private $name;

    /**
     * @Embedded(class="DDC3303Address")
     *
     * @var DDC3303Address
     */
    private $address;

    public function __construct($name, DDC3303Address $address)
    {
        $this->name = $name;
        $this->address = $address;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return DDC3303Address
     */
    public function getAddress()
    {
        return $this->address;
    }
}
