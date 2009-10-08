<?php

namespace Entities;

/** @Entity @Table(name="users", indexes={@Index(name="name_idx", columns={"name", "test"})}) */
class User {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /** @Column(type="string", length=50) */
    private $name;
    /** @Column(type="string", length=50) */
    private $test;
    /**
     * @OneToOne(targetEntity="Address")
     * @JoinColumns({
     *   @JoinColumn(name="address_id", referencedColumnName="id"),
     *   @JoinColumn(name="address2_id", referencedColumnName="id")
     * })
     */
    private $address;
    /**
     * @ManyToMany(targetEntity="Group")
     * @JoinTable(name="user_group",
     *   joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *   inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")
     * })
     */
    private $groups;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getAddress() {
        return $this->address;
    }

    public function setAddress(Address $address) {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }
}
