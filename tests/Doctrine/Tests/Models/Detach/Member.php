<?php

namespace Doctrine\Tests\Models\Detach;

/**
 * @Entity
 * @Table(name="member")
 */
class Member {

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(length=100)
     */
    public $name;

    /**
     * @param $name
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

}
