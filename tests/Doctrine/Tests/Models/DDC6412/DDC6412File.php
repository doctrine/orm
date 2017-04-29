<?php

namespace Doctrine\Tests\Models\DDC6412;

/**
 * @Entity
 */
class DDC6412File
{
    /**
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
    
    /**
     * @Column(length=50, name="file_name")
     */
    public $name;

    public function getId() {
        return $this->id;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }
}

