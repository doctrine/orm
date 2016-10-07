<?php

namespace Doctrine\Tests\Models\DDC6071;

/**
 * @MappedSuperclass
 */
class DDC6071User
{

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="user_id", length=150)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="DDC6071_SQ", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="user_name", nullable=true, unique=false, length=250)
     */
    protected $name;


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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}